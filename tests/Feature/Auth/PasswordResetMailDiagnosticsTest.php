<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\MailFailureClassifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class PasswordResetMailDiagnosticsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider stageProvider
     */
    public function test_classifier_identifies_failing_smtp_stage(string $message, string $expectedStage): void
    {
        $e = new TransportException($message);

        $this->assertSame($expectedStage, MailFailureClassifier::classify($e)['stage']);
    }

    public static function stageProvider(): array
    {
        return [
            'dns' => ['php_network_getaddresses: getaddrinfo failed: Name or service not known', MailFailureClassifier::STAGE_DNS],
            'connect' => ['Connection could not be established with host "smtp.gmail.com:587": timed out', MailFailureClassifier::STAGE_CONNECT],
            'tls' => ['Unable to connect with STARTTLS: certificate verify failed', MailFailureClassifier::STAGE_TLS],
            'auth' => ['Failed to authenticate on SMTP server. Expected response code "235" but got code "535"', MailFailureClassifier::STAGE_AUTH],
            'send' => ['Expected response code "250" but got code "550", with message "550 mailbox unavailable"', MailFailureClassifier::STAGE_SEND],
            'unknown' => ['Something odd', MailFailureClassifier::STAGE_UNKNOWN],
        ];
    }

    public function test_classifier_follows_previous_exception_chain(): void
    {
        $e = new TransportException('Unable to send', 0, new \RuntimeException('getaddrinfo failed'));

        $this->assertSame(MailFailureClassifier::STAGE_DNS, MailFailureClassifier::classify($e)['stage']);
        $this->assertContains(\RuntimeException::class, MailFailureClassifier::classify($e)['exception_chain']);
    }

    public function test_sanitizer_redacts_emails_urls_and_tokens(): void
    {
        $text = 'fail for user@example.com at https://comsochub.tech/reset-password/abcdef?token=1 key ' . str_repeat('a', 40);

        $clean = MailFailureClassifier::sanitize($text);

        $this->assertStringNotContainsString('user@example.com', $clean);
        $this->assertStringNotContainsString('comsochub.tech/reset-password', $clean);
        $this->assertStringNotContainsString(str_repeat('a', 40), $clean);
    }

    public function test_transport_context_never_contains_secret_values(): void
    {
        config(['mail.mailers.smtp.password' => 'super-secret-app-password']);

        $context = MailFailureClassifier::transportContext();

        $this->assertStringNotContainsString('super-secret-app-password', json_encode($context));
        $this->assertTrue($context['password_configured']);
    }

    public function test_transport_failure_logs_stage_and_correlation_without_secrets_or_leaks_to_user(): void
    {
        config(['mail.mailers.smtp.password' => 'super-secret-app-password']);

        Log::spy();
        Notification::shouldReceive('sendNow')
            ->andThrow(new TransportException('Failed to authenticate on SMTP server with username "x@gmail.com". Expected response code "235" but got code "535"'));

        $user = User::factory()->create();

        $response = $this->post('/forgot-password', ['email' => $user->email]);

        $response->assertSessionHasErrors(['error']);
        $error = session('errors')->first('error');
        $this->assertStringContainsString('Ref:', $error);
        $this->assertStringNotContainsString('535', $error);
        $this->assertStringNotContainsString('x@gmail.com', $error);

        Log::shouldHaveReceived('error')->withArgs(function ($message, $context) use ($error) {
            if (! str_contains($message, 'MAIL_TRANSPORT')) {
                return false;
            }
            $serialized = json_encode($context);

            return $context['failed_stage'] === MailFailureClassifier::STAGE_AUTH
                && str_contains($error, $context['correlation_id'])
                && ! str_contains($serialized, 'super-secret-app-password')
                && ! str_contains($serialized, 'x@gmail.com');
        })->once();

        \Mockery::close();
    }
}
