<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $fillable = ['slug', 'name_ar', 'name_en', 'color'];

    public function tickets(): BelongsToMany
    {
        return $this->belongsToMany(Ticket::class);
    }

    public function name(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

    public function badgeClasses(): string
    {
        return match ($this->color) {
            'teal' => 'bg-teal-50 text-teal-700 ring-teal-200',
            'indigo' => 'bg-brand-50 text-brand-700 ring-brand-200',
            'amber' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'rose' => 'bg-rose-50 text-rose-700 ring-rose-200',
            default => 'bg-slate-100 text-slate-600 ring-slate-200',
        };
    }
}
