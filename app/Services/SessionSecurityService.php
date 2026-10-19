<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SessionSecurityService
{
    /**
     * Parse User Agent into privacy-conscious device info.
     */
    public static function parseUserAgent(?string $userAgent): array
    {
        $ua = $userAgent ?? '';

        // Device type
        $deviceType = 'Desktop';
        if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i', $ua)) {
            $deviceType = 'Tablet';
        } elseif (preg_match('/(iphone|ipod|android.*mobile|blackberry|bb10|mini|windows\sce|palm)/i', $ua)) {
            $deviceType = 'Mobile';
        }

        // Platform / OS
        $platform = 'Unknown OS';
        if (preg_match('/iphone|ipad|ipod/i', $ua)) {
            $platform = 'iOS';
        } elseif (preg_match('/android/i', $ua)) {
            $platform = 'Android';
        } elseif (preg_match('/windows nt 10/i', $ua)) {
            $platform = 'Windows 10/11';
        } elseif (preg_match('/windows nt 6\.3/i', $ua)) {
            $platform = 'Windows 8.1';
        } elseif (preg_match('/windows nt 6\.2/i', $ua)) {
            $platform = 'Windows 8';
        } elseif (preg_match('/windows nt 6\.1/i', $ua)) {
            $platform = 'Windows 7';
        } elseif (preg_match('/macintosh|mac os x/i', $ua)) {
            $platform = 'macOS';
        } elseif (preg_match('/linux/i', $ua)) {
            $platform = 'Linux';
        } elseif (preg_match('/cros/i', $ua)) {
            $platform = 'Chrome OS';
        }

        // Browser
        $browser = 'Unknown Browser';
        if (preg_match('/edg\//i', $ua)) {
            $browser = 'Microsoft Edge';
        } elseif (preg_match('/opr\/|opera/i', $ua)) {
            $browser = 'Opera';
        } elseif (preg_match('/chrome|crios/i', $ua) && !preg_match('/edg/i', $ua)) {
            $browser = 'Chrome';
        } elseif (preg_match('/firefox|fxios/i', $ua)) {
            $browser = 'Firefox';
        } elseif (preg_match('/safari/i', $ua) && !preg_match('/chrome|crios|android/i', $ua)) {
            $browser = 'Safari';
        } elseif (preg_match('/msie|trident/i', $ua)) {
            $browser = 'Internet Explorer';
        }

        // Device signature (stable across sessions on same client)
        $signature = hash('sha256', "{$deviceType}|{$platform}|{$browser}");

        return [
            'device_type' => $deviceType,
            'platform' => $platform,
            'browser' => $browser,
            'device_signature' => $signature,
        ];
    }

    /**
     * Track a successful login and check if it represents a newly detected client.
     */
    public function handleLogin(User $user, Request $request): UserSession
    {
        $sessionId = $request->session()->getId();
        $sessionIdHash = hash('sha256', $sessionId);
        $userAgent = $request->userAgent() ?? '';
        $clientInfo = self::parseUserAgent($userAgent);
        $ip = $request->ip();

        // Check user's history of devices
        $hasAnyPriorSessions = UserSession::where('user_id', $user->id)->exists();
        $isRecognizedDevice = UserSession::where('user_id', $user->id)
            ->where('device_signature', $clientInfo['device_signature'])
            ->exists();

        // Upsert/Create the current session record
        $userSession = UserSession::updateOrCreate(
            ['session_id_hash' => $sessionIdHash],
            [
                'user_id' => $user->id,
                'device_type' => $clientInfo['device_type'],
                'platform' => $clientInfo['platform'],
                'browser' => $clientInfo['browser'],
                'device_signature' => $clientInfo['device_signature'],
                'ip_address' => $ip,
                'user_agent' => Str::limit($userAgent, 500, ''),
                'first_authenticated_at' => now(),
                'last_active_at' => now(),
                'revoked_at' => null,
            ]
        );

        // Alert Policy:
        // Baseline: If this is the user's very first session, record as baseline without an alert.
        // Subsequent: If the user has prior sessions and this device signature is NOT recognized, send an alert!
        if ($hasAnyPriorSessions && !$isRecognizedDevice) {
            $this->createSecurityAlert($user, $userSession);
        }

        return $userSession;
    }

    /**
     * Dispatch an in-app security alert notification.
     */
    protected function createSecurityAlert(User $user, UserSession $session): Notification
    {
        $loginTime = $session->first_authenticated_at->format('M d, Y h:i A');
        $deviceLabel = "{$session->browser} on {$session->platform} ({$session->device_type})";

        return Notification::create([
            'user_id' => $user->id,
            'type' => 'security_alert',
            'title' => 'New login detected',
            'body' => "Your ComSoc Hub account was accessed from a new device: {$deviceLabel} on {$loginTime}.",
            'target_url' => route('profile.sessions'),
            'read_at' => null,
        ]);
    }

    /**
     * Update activity timestamp on active session.
     */
    public function touchActivity(string $sessionId): void
    {
        $sessionIdHash = hash('sha256', $sessionId);

        // Throttle updates to at most once per 60 seconds to avoid database write fatigue
        UserSession::where('session_id_hash', $sessionIdHash)
            ->whereNull('revoked_at')
            ->where('last_active_at', '<', now()->subSeconds(60))
            ->update(['last_active_at' => now()]);
    }

    /**
     * Revoke a specific session belonging to the user.
     */
    public function revokeSession(User $user, UserSession $session, string $currentSessionId): array
    {
        if ($session->user_id !== $user->id) {
            return ['success' => false, 'message' => 'Unauthorized.'];
        }

        $isCurrent = $session->isCurrent($currentSessionId);

        // 1. Mark session record as revoked
        $session->update([
            'revoked_at' => now(),
        ]);

        // 2. If sessions table exists, remove the matching session
        if (\Illuminate\Support\Facades\Schema::hasTable('sessions')) {
            $allSessions = DB::table('sessions')
                ->where('user_id', $user->id)
                ->get();

            foreach ($allSessions as $s) {
                if (hash('sha256', $s->id) === $session->session_id_hash) {
                    DB::table('sessions')->where('id', $s->id)->delete();
                    break;
                }
            }
        }

        return [
            'success' => true,
            'is_current' => $isCurrent,
            'message' => $isCurrent ? 'Current session revoked. You have been logged out.' : 'Session revoked successfully.',
        ];
    }
}
