<?php

namespace App\Http\Requests;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'requester_email' => mb_strtolower(trim((string) $this->input('requester_email'))),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $attachments = config('helpdesk.attachments');

        return [
            'requester_name' => ['required', 'string', 'max:120'],
            'requester_email' => ['required', 'email', 'max:180'],
            'subject' => ['required', 'string', 'max:200'],
            'category_id' => [
                'required',
                'integer',
                Rule::exists('ticket_categories', 'id')->where('is_active', true),
            ],
            'message' => ['required', 'string', 'min:10', 'max:20000'],
            'attachments' => ['nullable', 'array', 'max:'.$attachments['max_files']],
            'attachments.*' => [
                'file',
                'max:'.$attachments['max_kilobytes'],
                'mimes:'.implode(',', $attachments['extensions']),
            ],
            'website' => ['prohibited'],
            'priority' => ['sometimes', Rule::enum(TicketPriority::class)],
            'assigned_to' => ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
            'status' => ['sometimes', Rule::enum(TicketStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'website.prohibited' => __('portal.submit.bot_detected'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'requester_name' => __('fields.requester_name'),
            'requester_email' => __('fields.requester_email'),
            'subject' => __('fields.subject'),
            'category_id' => __('fields.category'),
            'message' => __('fields.message'),
            'attachments' => __('fields.attachments'),
            'attachments.*' => __('fields.attachment'),
        ];
    }

    public function activeAgents(): array
    {
        return User::query()->active()->orderBy('name')->get()->all();
    }
}
