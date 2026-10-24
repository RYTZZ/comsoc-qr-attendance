<?php

namespace App\Services;

use App\Models\StudentSignature;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * Validates, normalizes, encrypts and (only for authorized backend flows) decrypts signatures.
 * Encryption uses Laravel's Encrypter (AES-CBC+HMAC or AES-GCM, per configured cipher).
 */
class SignatureVaultService
{
    private ?Encrypter $encrypter = null;

    /** Validate a data-URL PNG, re-encode it through GD and return normalized bytes + size. */
    public function normalize(string $dataUrl): array
    {
        if (strlen($dataUrl) > config('signatures.max_payload_chars')) {
            throw new InvalidArgumentException('The signature is too large.');
        }
        if (!preg_match('#^data:image/png;base64,([A-Za-z0-9+/]+={0,2})$#', $dataUrl, $m)) {
            throw new InvalidArgumentException('Unsupported or malformed signature format.');
        }
        $bytes = base64_decode($m[1], true);
        if ($bytes === false || $bytes === '' || strlen($bytes) > config('signatures.max_image_bytes')) {
            throw new InvalidArgumentException('Invalid signature data.');
        }
        if (!str_starts_with($bytes, "\x89PNG\r\n\x1a\n")) {
            throw new InvalidArgumentException('Invalid signature image.');
        }

        $info = @getimagesizefromstring($bytes);
        if (!$info || $info[2] !== IMAGETYPE_PNG) {
            throw new InvalidArgumentException('Invalid signature image.');
        }
        [$w, $h] = $info;
        if ($w < config('signatures.min_width') || $h < config('signatures.min_height')
            || $w > config('signatures.max_width') || $h > config('signatures.max_height')) {
            throw new InvalidArgumentException('Signature image dimensions are not allowed.');
        }

        $src = @imagecreatefromstring($bytes);
        if (!$src) {
            throw new InvalidArgumentException('Invalid signature image.');
        }

        $out = imagecreatetruecolor($w, $h);
        imagealphablending($out, false);
        imagesavealpha($out, true);
        imagefill($out, 0, 0, imagecolorallocatealpha($out, 255, 255, 255, 127));
        imagecopy($out, $src, 0, 0, 0, 0, $w, $h);
        imagedestroy($src);

        $ink = 0;
        $need = (int) config('signatures.min_ink_pixels');
        for ($y = 0; $y < $h && $ink < $need; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $c = imagecolorat($out, $x, $y);
                $alpha = ($c >> 24) & 0x7F;
                $rgb = $c & 0xFFFFFF;
                if ($alpha < 64 && $rgb !== 0xFFFFFF && ++$ink >= $need) {
                    break;
                }
            }
        }
        if ($ink < $need) {
            imagedestroy($out);
            throw new InvalidArgumentException('The signature appears to be blank.');
        }

        ob_start();
        imagepng($out, null, 6);
        $clean = ob_get_clean();
        imagedestroy($out);

        return ['bytes' => $clean, 'width' => $w, 'height' => $h];
    }

    /**
     * Store a new signature for $user. If an active one exists it is superseded (kept, never deleted),
     * so previously issued attendance sheets remain reproducible.
     */
    public function store(User $user, string $dataUrl, bool $replace): StudentSignature
    {
        $img = $this->normalize($dataUrl);
        $payload = $this->encrypter()->encrypt($img['bytes'], false);
        unset($img['bytes']);

        return DB::transaction(function () use ($user, $payload, $img, $replace) {
            $active = StudentSignature::where('user_id', $user->id)
                ->where('status', StudentSignature::STATUS_ACTIVE)
                ->lockForUpdate()->first();

            if ($active && !$replace) {
                throw new RuntimeException('already_registered');
            }
            if (!$active && $replace) {
                throw new RuntimeException('not_registered');
            }

            $nextVersion = ((int) StudentSignature::where('user_id', $user->id)->max('version')) + 1;
            $consentAt = $active?->consent_acknowledged_at ?? now();

            if ($active) {
                $active->update(['status' => StudentSignature::STATUS_SUPERSEDED, 'replaced_at' => now()]);
            }

            return StudentSignature::create([
                'user_id' => $user->id,
                'encrypted_payload' => $payload,
                'image_format' => 'png',
                'data_version' => 1,
                'width' => $img['width'],
                'height' => $img['height'],
                'version' => $nextVersion,
                'status' => StudentSignature::STATUS_ACTIVE,
                'consent_acknowledged_at' => $consentAt,
                'consent_version' => config('signatures.consent_version'),
            ]);
        });
    }

    /**
     * Deactivates the currently active signature for $user without deleting it.
     * Preserves version history with STATUS_SUPERSEDED and replaced_at timestamp.
     */
    public function reset(User $user): ?StudentSignature
    {
        return DB::transaction(function () use ($user) {
            $active = StudentSignature::where('user_id', $user->id)
                ->where('status', StudentSignature::STATUS_ACTIVE)
                ->lockForUpdate()
                ->first();

            if (!$active) {
                return null;
            }

            $active->update([
                'status' => StudentSignature::STATUS_SUPERSEDED,
                'replaced_at' => now(),
            ]);

            return $active;
        });
    }

    public function decrypt(StudentSignature $signature): ?string
    {
        try {
            $bytes = $this->encrypter()->decrypt($signature->encrypted_payload, false);
        } catch (DecryptException $e) {
            Log::warning('Signature decryption failed', ['signature_id' => $signature->id]);
            return null;
        }

        return is_string($bytes) && str_starts_with($bytes, "\x89PNG") ? $bytes : null;
    }

    /**
     * Determine the persistent signature status for a user:
     * - 'Signature Registered': Valid, usable active signature exists and decrypts cleanly.
     * - 'Signature Required': No active signature record exists.
     * - 'Signature Unavailable': An active record exists, but fails decryption/retrieval (technical issue logged without sensitive payload).
     *
     * @return array{state: string, label: string, is_usable: bool, version: ?int, signature: ?StudentSignature}
     */
    public function signatureStatus(User $user): array
    {
        $signature = $user->relationLoaded('studentSignature')
            ? $user->studentSignature
            : StudentSignature::where('user_id', $user->id)->where('status', StudentSignature::STATUS_ACTIVE)->first();

        if (!$signature) {
            return [
                'state' => 'required',
                'label' => 'Signature Required',
                'is_usable' => false,
                'version' => null,
                'signature' => null,
            ];
        }

        $decrypted = $this->decrypt($signature);
        if ($decrypted === null) {
            Log::error('Signature record is unretrievable or corrupt', [
                'user_id' => $user->id,
                'signature_id' => $signature->id,
                'version' => $signature->version,
            ]);

            return [
                'state' => 'unavailable',
                'label' => 'Signature Unavailable',
                'is_usable' => false,
                'version' => $signature->version,
                'signature' => $signature,
            ];
        }

        return [
            'state' => 'registered',
            'label' => 'Signature Registered',
            'is_usable' => true,
            'version' => $signature->version,
            'signature' => $signature,
        ];
    }

    /** Encrypt arbitrary bytes (used for the private issued-PDF archive). */
    public function encryptBytes(string $bytes): string
    {
        return $this->encrypter()->encrypt($bytes, false);
    }

    public function decryptBytes(string $payload): ?string
    {
        try {
            return $this->encrypter()->decrypt($payload, false);
        } catch (DecryptException) {
            return null;
        }
    }

    /** The active signature model for a user, or null if none active. */
    public function activeRecord(?User $user): ?StudentSignature
    {
        if (!$user) {
            return null;
        }

        return StudentSignature::where('user_id', $user->id)
            ->where('status', StudentSignature::STATUS_ACTIVE)
            ->first();
    }

    /** Active signatures keyed by user id. */
    public function activeFor(iterable $userIds): Collection
    {
        return StudentSignature::whereIn('user_id', collect($userIds)->all())
            ->where('status', StudentSignature::STATUS_ACTIVE)->get()->keyBy('user_id');
    }

    private function encrypter(): Encrypter
    {
        if ($this->encrypter) {
            return $this->encrypter;
        }
        $cipher = config('signatures.cipher') ?: config('app.cipher');
        $key = config('signatures.key') ?: config('app.key');
        $decode = fn (string $k) => str_starts_with($k, 'base64:') ? base64_decode(Str::after($k, 'base64:')) : $k;

        $enc = new Encrypter($decode($key), $cipher); // throws if key/cipher mismatch
        $prev = array_map($decode, config('signatures.previous_keys', []));
        if ($prev) {
            $enc->previousKeys($prev);
        }

        return $this->encrypter = $enc;
    }
}
