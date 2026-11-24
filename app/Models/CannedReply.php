<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CannedReply extends Model
{
    protected $fillable = [
        'title_ar',
        'title_en',
        'body_ar',
        'body_en',
        'created_by',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function title(): string
    {
        return app()->getLocale() === 'ar' ? $this->title_ar : $this->title_en;
    }

    public function body(): string
    {
        return app()->getLocale() === 'ar' ? $this->body_ar : $this->body_en;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('title_en');
    }
}
