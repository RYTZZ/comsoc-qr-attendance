<?php

namespace App\Console\Commands;

use App\Support\MailFailureClassifier;
use Illuminate\Console\Command;
use Throwable;

/**
 * Staged, read-only SMTP diagnostic. Sends NO message and prints NO secrets.
 *
 * Run on the production host:  php artisan mail:diagnose
 */
class MailDiagnose extends Command
{
    protected $signature = 'mail:diagnose {--timeout=10 : Socket timeout in seconds}';

    protected $description = 'Check DNS, TCP, TLS and SMTP authentication for the configured mailer (no message is sent).';

    public function handle(): int
    {
        $ctx = MailFailureClassifier::transportContext();
        $this->line('Environment : ' . app()->environment());
        $this->line('Mailer      : ' . $ctx['mailer']);

        if ($ctx['mailer'] !== 'smtp') {
            $this->error('Default mailer is not "smtp"; real delivery is not configured.');

            return self::FAILURE;
        }

        $host = (string) $ctx['mail_host'];
        $port = (int) $ctx['mail_port'];
        $timeout = (int) $this->option('timeout');

        $this->line("SMTP target : {$host}:{$port} scheme=" . var_export($ctx['mail_scheme'], true));
        $this->line('Credentials : username ' . ($ctx['username_configured'] ? 'set' : 'MISSING')
            . ', password ' . ($ctx['password_configured'] ? 'set' : 'MISSING')
            . ', from ' . ($ctx['from_configured'] ? 'set' : 'MISSING'));

        // 1. DNS
        $ip = gethostbyname($host);
        if ($ip === $host) {
            $this->error('[FAIL] dns_resolution: host does not resolve.');

            return self::FAILURE;
        }
        $this->info('[ OK ] dns_resolution');

        // 2. TCP
        $errno = 0;
        $errstr = '';
        $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if (! $socket) {
            $this->error("[FAIL] tcp_connection: cannot reach port {$port} (errno {$errno}). "
                . 'Outbound SMTP is likely blocked by the host/firewall.');

            return self::FAILURE;
        }
        fclose($socket);
        $this->info('[ OK ] tcp_connection');

        // 3. TLS + AUTH via the real transport (certificate validation stays enabled)
        try {
            app('mail.manager')->mailer('smtp')->getSymfonyTransport()->start();
            $this->info('[ OK ] tls_negotiation + smtp_authentication');
        } catch (Throwable $e) {
            $d = MailFailureClassifier::classify($e);
            $this->error("[FAIL] {$d['stage']}: " . $d['detail']);

            return self::FAILURE;
        }

        $this->info('SMTP transport is reachable and authenticated.');

        return self::SUCCESS;
    }
}
