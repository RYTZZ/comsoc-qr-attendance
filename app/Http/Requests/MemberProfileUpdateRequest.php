<?php

namespace App\Http\Requests;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MemberProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($userId),
            ],
            'bio' => ['nullable', 'string', 'max:1000'],
            'avatar_style' => ['nullable', 'string', 'in:adventurer'],
            'avatar_seed' => ['nullable', 'string', 'max:50'],
            'github_url' => ['nullable', 'url', 'max:255'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'interests' => ['nullable'],
            'visibility' => ['nullable', 'string', 'in:public,members_only,private'],
            'is_public' => ['nullable', 'boolean'],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['string', 'max:80'],
        ];
    }
}
