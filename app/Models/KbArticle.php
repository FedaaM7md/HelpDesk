<?php

namespace App\Models;

use App\Enums\KbStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class KbArticle extends Model
{
    protected $fillable = [
        'kb_category_id',
        'author_id',
        'translation_key',
        'locale',
        'title',
        'slug',
        'excerpt',
        'body',
        'status',
        'published_at',
        'views',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => KbStatus::class,
            'published_at' => 'datetime',
            'views' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(KbCategory::class, 'kb_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', KbStatus::Published->value)
            ->where(function (Builder $q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    public function scopeForLocale(Builder $query, string $locale): Builder
    {
        return $query->where('locale', $locale);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $like = '%'.trim($term).'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('title', 'like', $like)
                ->orWhere('excerpt', 'like', $like)
                ->orWhere('body', 'like', $like);
        });
    }

    public function excerptText(): string
    {
        if ($this->excerpt) {
            return $this->excerpt;
        }

        return Str::limit(trim(strip_tags((string) $this->body)), 160);
    }

    public function readingMinutes(): int
    {
        $words = str_word_count(strip_tags((string) $this->body), 0, 'أبتثجحخدذرزسشصضطظعغفقكلمنهوي');

        return max(1, (int) ceil($words / 180));
    }

    public function isPublished(): bool
    {
        return $this->status === KbStatus::Published;
    }
}
