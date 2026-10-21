<?php

namespace App\Support;

use Throwable;

/**
 * Classifies a mail transport failure into the SMTP stage at which it occurred
 * and produces a sanitized description that is safe to write to logs.
 *
 * Never returns credentials, tokens, full URLs, or e-mail addresses.
 */
class MailFailureClassifier
{
    public const STAGE_DNS = 'dns_resolution';
    public const STAGE_CONNECT = 'tcp_connection';
    public const STAGE_TLS = 'tls_negotiation';
    public const STAGE_AUTH = 'smtp_authentication';
    public const STAGE_SEND = 'message_transmission';
    public const STAGE_UNKNOWN = 'unknown';

    /**
     * @return array{stage: string, exception_chain: array<int,string>, detail: string}
     */
    public static function classify(Throwable $e): array
    {
        $chain = [];
        $messages = [];

        for ($current = $e; $current !== null; $current = $current->getPrevious()) {
            $chain[] = get_class($current);
            $messages[] = $current->getMessage();
        }

        $haystack = strtolower(implode(' | ', $messages));

        $stage = match (true) {
            str_contains($haystack, 'getaddrinfo')
                || str_contains($haystack, 'php_network_getaddresses')
                || str_contains($haystack, 'name or service not known')
                || str_contains($haystack, 'could not resolve') => self::STAGE_DNS,

            str_contains($haystack, 'starttls')
                || str_contains($haystack, 'certificate')
                || str_contains($haystack, 'ssl')
                || str_contains($haystack, 'tls') => self::STAGE_TLS,

            str_contains($haystack, 'failed to authenticate')
                || str_contains($haystack, 'username and password not accepted')
                || preg_match('/\b(530|534|535)\b/', $haystack) === 1 => self::STAGE_AUTH,

            str_contains($haystack, 'connection could not be established')
                || str_contains($haystack, 'connection refused')
                || str_contains($haystack, 'timed out')
                || str_contains($haystack, 'network is unreachable')
                || str_contains($haystack, 'connection to') => self::STAGE_CONNECT,

            str_contains($haystack, 'expected response code')
                || preg_match('/\b(421|450|451|452|550|551|552|553|554)\b/', $haystack) === 1 => self::STAGE_SEND,

            default => self::STAGE_UNKNOWN,
        };

        return [
            'stage' => $stage,
            'exception_chain' => $chain,
            'detail' => self::sanitize(implode(' | ', $messages)),
        ];
    }

    /**
     * Remove e-mail addresses, URLs and long opaque strings (possible tokens/secrets).
     */
    public static function sanitize(string $text): string
    {
        $text = preg_replace('/https?:\/\/\S+/i', '[url-redacted]', $text) ?? $text;
        $text = preg_replace('/[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}/', '[email-redacted]', $text) ?? $text;
        $text = preg_replace('/\b[A-Za-z0-9_\-]{32,}\b/', '[token-redacted]', $text) ?? $text;

        return mb_substr($text, 0, 500);
    }

    /**
     * Non-secret description of the active SMTP configuration.
     *
     * @return array<string,mixed>
     */
    public static function transportContext(): array
    {
        $mailer = (string) config('mail.default');
        $smtp = (array) config('mail.mailers.smtp', []);

        return [
            'mailer' => $mailer,
            'mail_host' => $smtp['host'] ?? null,
            'mail_port' => $smtp['port'] ?? null,
            'mail_scheme' => $smtp['scheme'] ?? null,
            'mail_encryption' => $smtp['encryption'] ?? null,
            'username_configured' => ! empty($smtp['username']),
            'password_configured' => ! empty($smtp['password']),
            'from_configured' => ! empty(config('mail.from.address')),
        ];
    }
}
