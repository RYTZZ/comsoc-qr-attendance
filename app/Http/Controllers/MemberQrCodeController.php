<?php

namespace App\Http\Controllers;

use App\Services\DynamicQrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberQrCodeController extends Controller
{
    public function __construct(
        private DynamicQrService $dynamicQrService
    ) {}

    /**
     * Dedicated full-screen dynamic QR pass view for members.
     */
    public function show(Request $request): View
    {
        $user = $request->user();
        $tokenData = $this->dynamicQrService->generateTokenForUser($user);

        return view('member.dynamic_qr', [
            'user' => $user,
            'initialQrPayload' => $tokenData['qr_payload'],
            'initialExpiresAt' => $tokenData['expires_at'],
            'ttl' => DynamicQrService::TOKEN_TTL_SECONDS,
        ]);
    }

    /**
     * API endpoint called every ~30 seconds by Alpine.js to refresh the rolling token.
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->is_active || !$user->is_eligible) {
            return response()->json([
                'success' => false,
                'message' => 'Account is deactivated or not in good standing.',
            ], 403);
        }

        $tokenData = $this->dynamicQrService->generateTokenForUser($user);

        return response()->json([
            'success' => true,
            'payload' => $tokenData['qr_payload'],
            'expires_at' => $tokenData['expires_at'],
            'ttl' => $tokenData['ttl'],
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
