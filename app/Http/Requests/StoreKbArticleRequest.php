<?php

namespace App\Http\Requests;

use App\Enums\KbStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKbArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-kb') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $article = $this->route('article');

        return [
            'kb_category_id' => ['required', 'integer', 'exists:kb_categories,id'],
            'locale' => ['required', 'in:ar,en'],
            'translation_key' => [
                'nullable',
                'string',
                'max:40',
                Rule::unique('kb_articles', 'translation_key')
                    ->where(fn ($query) => $query->where('locale', $this->input('locale')))
                    ->ignore($article?->id),
            ],
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:200', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('kb_articles', 'slug')->ignore($article?->id)],
            'excerpt' => ['nullable', 'string', 'max:300'],
            'body' => ['required', 'string', 'min:20', 'max:60000'],
            'status' => ['required', Rule::enum(KbStatus::class)],
        ];
    }
}
