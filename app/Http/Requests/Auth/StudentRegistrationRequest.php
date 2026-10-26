<?php

namespace App\Http\Requests\Auth;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StudentRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'student_id' => $this->student_id ? trim((string) $this->student_id) : null,
            'first_name' => $this->first_name ? trim((string) $this->first_name) : null,
            'last_name' => $this->last_name ? trim((string) $this->last_name) : null,
            'middle_name' => $this->middle_name ? trim((string) $this->middle_name) : null,
            'email' => $this->email ? strtolower(trim((string) $this->email)) : null,
            'username' => $this->username ? strtolower(trim((string) $this->username)) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'student_id' => [
                'required',
                'string',
                'regex:/^[\d-]+$/',
                function ($attribute, $value, $fail) {
                    $digits = preg_replace('/\D/', '', (string) $value);
                    if (strlen($digits) === 0) {
                        $fail('The Student ID must contain digits.');
                    } elseif (strlen($digits) > 8) {
                        $fail('The Student ID may not exceed 8 digits.');
                    }
                },
                Rule::unique('students', 'student_number'),
                Rule::unique('users', 'student_number'),
            ],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
                Rule::unique('students', 'email'),
            ],
            'program' => [
                'required',
                'string',
                Rule::in(Student::ALLOWED_PROGRAMS),
            ],
            'year' => [
                'required',
                'string',
                Rule::in(Student::ALLOWED_YEARS),
            ],
            'block' => [
                'required',
                'string',
                Rule::in(Student::BLOCKS),
            ],
            'password' => [
                'required',
                'string',
                Password::min(8)->letters()->numbers(),
                'confirmed',
            ],
            'terms' => ['accepted'],
            'username' => [
                'required',
                'string',
                new \App\Rules\UsernameRule(),
            ],
            'avatar_seed' => [
                'required',
                'string',
                'in:' . implode(',', array_keys(\App\Services\AvatarService::PRESET_SEEDS)),
            ],
            'avatar_style' => ['nullable', 'string', 'in:adventurer'],
            'signature' => [
                'required',
                'string',
                'max:' . config('signatures.max_payload_chars', 400000),
                function ($attribute, $value, $fail) {
                    try {
                        app(\App\Services\SignatureVaultService::class)->normalize($value);
                    } catch (\InvalidArgumentException $e) {
                        $fail($e->getMessage());
                    } catch (\Throwable $e) {
                        $fail('The signature is invalid.');
                    }
                },
            ],
            'signature_consent' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.required' => 'Student ID is required.',
            'student_id.regex' => 'The Student ID format is invalid.',
            'student_id.unique' => 'The provided registration information could not be processed. If you already have an account, please log in or request account recovery.',
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            'email.required' => 'Email address is required.',
            'email.unique' => 'The provided registration information could not be processed. If you already have an account, please log in or request account recovery.',
            'program.required' => 'Please select your academic program.',
            'program.in' => 'The selected program is invalid.',
            'year.required' => 'Please select your year level.',
            'year.in' => 'The selected year level is invalid.',
            'block.required' => 'Please select your section block.',
            'block.in' => 'The selected block is invalid.',
            'password.required' => 'Password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
            'terms.accepted' => 'You must accept the terms and privacy notice to continue.',
            'username.required' => 'Please enter a username.',
            'avatar_seed.required' => 'Please select an adventurer avatar.',
            'avatar_seed.in' => 'The selected avatar is not part of the official adventurer collection.',
            'signature.required' => 'Your handwritten signature is required to complete registration.',
            'signature_consent.accepted' => 'You must acknowledge the signature consent statement.',
        ];
    }
}
