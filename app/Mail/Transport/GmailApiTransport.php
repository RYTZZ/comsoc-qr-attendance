<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

class GmailApiTransport extends AbstractTransport
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $refreshToken;

    public function __construct(string $clientId, string $clientSecret, string $refreshToken)
    {
        parent::__construct();

        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->refreshToken = $refreshToken;
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        $accessToken = $this->getAccessToken();

        // Gmail REST API expects base64url-encoded raw RFC 2822 email payload
        $rawMessage = $email->toString();
        $base64UrlMessage = strtr(base64_encode($rawMessage), ['+' => '-', '/' => '_', '=' => '']);

        $response = Http::withToken($accessToken)
            ->timeout(15)
            ->post('https://gmail.googleapis.com/gmail/v1/users/me/messages/send', [
                'raw' => $base64UrlMessage,
            ]);

        if ($response->failed()) {
            $errorJson = $response->json('error.message') ?? $response->body();
            throw new TransportException('Gmail API error: ' . $errorJson, $response->status());
        }
    }

    protected function getAccessToken(): string
    {
        if (empty($this->clientId) || empty($this->clientSecret) || empty($this->refreshToken)) {
            throw new TransportException('Gmail API credentials incomplete: GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, and GOOGLE_REFRESH_TOKEN are required.');
        }

        $response = Http::asForm()
            ->timeout(10)
            ->post('https://oauth2.googleapis.com/token', [
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $this->refreshToken,
                'grant_type'    => 'refresh_token',
            ]);

        if ($response->failed()) {
            $error = $response->json('error_description') ?? $response->json('error') ?? $response->body();
            throw new TransportException('Gmail OAuth2 token refresh failed: ' . $error, $response->status());
        }

        $token = $response->json('access_token');
        if (empty($token)) {
            throw new TransportException('Gmail OAuth2 response did not contain an access_token.');
        }

        return $token;
    }

    public function __toString(): string
    {
        return 'gmail-api';
    }
}
