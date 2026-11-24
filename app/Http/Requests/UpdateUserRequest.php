<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-users') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180', Rule::unique('users', 'email')->ignore($user?->id)],
            'role' => ['required', Rule::enum(UserRole::class)],
            'job_title' => ['nullable', 'string', 'max:100'],
            'locale' => ['required', 'in:ar,en'],
            'password' => ['nullable', 'string', Password::min(10)->letters()->numbers(), 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
