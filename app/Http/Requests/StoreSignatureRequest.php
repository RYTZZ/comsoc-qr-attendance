<?php

namespace App\Http\Requests;

use App\Models\StudentSignature;
use Illuminate\Foundation\Http\FormRequest;

class StoreSignatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Any authenticated, non-kiosk account may manage ITS OWN signature. No user id is read from input.
        return $this->user() !== null && !$this->user()->isKiosk();
    }

    public function rules(): array
    {
        $hasActive = StudentSignature::where('user_id', $this->user()->id)
            ->where('status', StudentSignature::STATUS_ACTIVE)->exists();

        return [
            'signature' => ['required', 'string', 'max:' . config('signatures.max_payload_chars')],
            // Consent is mandatory before the initial signature is stored.
            'consent' => [($hasActive || $this->isMethod('PUT')) ? 'nullable' : 'accepted'],
        ];
    }

    public function messages(): array
    {
        return ['consent.accepted' => 'Please acknowledge the consent statement before saving.'];
    }
}
