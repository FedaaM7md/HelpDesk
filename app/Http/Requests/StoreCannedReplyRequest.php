<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCannedReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-canned-replies') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title_ar' => ['required', 'string', 'max:120'],
            'title_en' => ['required', 'string', 'max:120'],
            'body_ar' => ['required', 'string', 'max:5000'],
            'body_en' => ['required', 'string', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
