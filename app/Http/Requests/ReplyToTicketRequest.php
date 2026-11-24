<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReplyToTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $attachments = config('helpdesk.attachments');

        return [
            'message' => ['required', 'string', 'min:2', 'max:20000'],
            'attachments' => ['nullable', 'array', 'max:'.$attachments['max_files']],
            'attachments.*' => [
                'file',
                'max:'.$attachments['max_kilobytes'],
                'mimes:'.implode(',', $attachments['extensions']),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'message' => __('fields.reply'),
            'attachments' => __('fields.attachments'),
            'attachments.*' => __('fields.attachment'),
        ];
    }
}
