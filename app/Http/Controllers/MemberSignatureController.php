<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSignatureRequest;
use App\Models\StudentSignature;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\SignatureVaultService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;

class MemberSignatureController extends Controller
{
    public function __construct(private SignatureVaultService $vault) {}

    private function activeFor(Request $request): ?StudentSignature
    {
        return StudentSignature::where('user_id', $request->user()->id)
            ->where('status', StudentSignature::STATUS_ACTIVE)->first();
    }

    private function statusPayload(?StudentSignature $s, ?User $user = null): array
    {
        $statusInfo = $user ? $this->vault->signatureStatus($user) : [
            'state' => $s ? 'registered' : 'required',
            'label' => $s ? 'Signature Registered' : 'Signature Required',
            'is_usable' => (bool) $s,
            'version' => $s?->version,
        ];

        return [
            'registered' => $statusInfo['state'] === 'registered',
            'state' => $statusInfo['state'],
            'label' => $statusInfo['label'],
            'version' => $statusInfo['version'] ?? $s?->version,
            'registered_at' => $s?->created_at?->toIso8601String(),
            'consent_at' => $s?->consent_acknowledged_at?->toIso8601String(),
        ];
    }

    public function show(Request $request): View
    {
        $user = $request->user();
        return view('member.signature', ['status' => $this->statusPayload($this->activeFor($request), $user)]);
    }

    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        return response()->json($this->statusPayload($this->activeFor($request), $user));
    }

    /** Streams the CURRENT USER's own signature only. No id is accepted from the client. */
    public function preview(Request $request): Response
    {
        $signature = $this->activeFor($request);
        abort_unless($signature, 404);
        Gate::authorize('view', $signature);

        $bytes = $this->vault->decrypt($signature);
        abort_unless($bytes !== null, 404);

        return response($bytes, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function store(StoreSignatureRequest $request): JsonResponse
    {
        return $this->persist($request, false);
    }

    public function replace(StoreSignatureRequest $request): JsonResponse
    {
        return $this->persist($request, true);
    }

    /**
     * Resets/deactivates the student's active signature.
     * Enforces ownership strictly from the authenticated user.
     * Preserves signature record history under STATUS_SUPERSEDED.
     */
    public function reset(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if(!$user || $user->isKiosk(), 403);

        $active = $this->activeFor($request);
        if (!$active) {
            return response()->json([
                'message' => 'No active signature is currently registered.',
            ] + $this->statusPayload(null, $user), 404);
        }

        Gate::authorize('reset', $active);

        $resetSig = $this->vault->reset($user);

        if ($resetSig) {
            AuditLogger::log('signature.reset', $resetSig, [
                'status' => StudentSignature::STATUS_ACTIVE,
            ], [
                'status' => StudentSignature::STATUS_SUPERSEDED,
                'version' => $resetSig->version,
            ], 'Student requested signature reset from dashboard');
        }

        return response()->json([
            'message' => 'Signature deactivated successfully. You can now register a new signature.',
        ] + $this->statusPayload(null, $user), 200);
    }

    private function persist(StoreSignatureRequest $request, bool $replace): JsonResponse
    {
        $user = $request->user();

        try {
            $signature = $this->vault->store($user, $request->input('signature'), $replace);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (RuntimeException $e) {
            $msg = $e->getMessage() === 'already_registered'
                ? 'A signature is already registered. Use the replace option.'
                : 'No signature is registered yet.';
            return response()->json(['message' => $msg], 409);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'The signature could not be saved. Please try again.'], 500);
        }

        // Audit metadata only: never the image, ciphertext or keys.
        if ($replace) {
            AuditLogger::log('signature.replaced', $signature, [], ['version' => $signature->version]);
        } else {
            AuditLogger::log('signature.registered', $signature, [], ['version' => $signature->version]);
            AuditLogger::log('signature.consent_acknowledged', $signature, [], [
                'consent_version' => $signature->consent_version,
                'acknowledged_at' => $signature->consent_acknowledged_at->toIso8601String(),
            ]);
        }

        return response()->json(['message' => 'Signature saved securely.'] + $this->statusPayload($signature, $user), $replace ? 200 : 201);
    }
}
