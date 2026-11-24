<?php

namespace App\Models;

use App\Enums\TicketChannel;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    protected $fillable = [
        'reference',
        'subject',
        'description',
        'status',
        'priority',
        'channel',
        'category_id',
        'requester_name',
        'requester_email',
        'requester_locale',
        'assigned_to',
        'created_by',
        'first_response_due_at',
        'resolution_due_at',
        'first_responded_at',
        'resolved_at',
        'closed_at',
        'first_response_breached_at',
        'resolution_breached_at',
        'last_reply_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'channel' => TicketChannel::class,
            'first_response_due_at' => 'datetime',
            'resolution_due_at' => 'datetime',
            'first_responded_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'first_response_breached_at' => 'datetime',
            'resolution_breached_at' => 'datetime',
            'last_reply_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->orderBy('created_at')->orderBy('id');
    }

    public function publicMessages(): HasMany
    {
        return $this->messages()->where('is_internal', false);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->orderBy('name_en');
    }

    public function csat(): HasOne
    {
        return $this->hasOne(CsatRating::class);
    }

    public function latestPublicMessage(): HasOne
    {
        return $this->hasOne(TicketMessage::class)->where('is_internal', false)->latestOfMany();
    }

    public function isResolved(): bool
    {
        return $this->status->isResolved();
    }

    public function isAssignedTo(User $user): bool
    {
        return $this->assigned_to === $user->id;
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->canViewAllTickets()) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('assigned_to', $user->id)->orWhereNull('assigned_to');
        });
    }

    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->whereIn('status', TicketStatus::unresolvedValues());
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        $term = trim((string) ($filters['q'] ?? ''));

        return $query
            ->when($term !== '', function (Builder $q) use ($term) {
                $like = '%'.$term.'%';
                $q->where(function (Builder $inner) use ($like) {
                    $inner->where('reference', 'like', $like)
                        ->orWhere('subject', 'like', $like)
                        ->orWhere('requester_name', 'like', $like)
                        ->orWhere('requester_email', 'like', $like)
                        ->orWhere('description', 'like', $like);
                });
            })
            ->when(($filters['status'] ?? null), function (Builder $q, string $status) {
                if ($status === 'unresolved') {
                    $q->whereIn('status', TicketStatus::unresolvedValues());
                } elseif ($status === 'resolved') {
                    $q->whereIn('status', ['solved', 'closed']);
                } elseif (in_array($status, array_map(fn ($case) => $case->value, TicketStatus::cases()), true)) {
                    $q->where('status', $status);
                }
            })
            ->when(($filters['priority'] ?? null), function (Builder $q, string $priority) {
                if (in_array($priority, TicketPriority::values(), true)) {
                    $q->where('priority', $priority);
                }
            })
            ->when(($filters['category'] ?? null), fn (Builder $q, $category) => $q->where('category_id', $category))
            ->when(($filters['tag'] ?? null), fn (Builder $q, $tag) => $q->whereHas('tags', fn (Builder $t) => $t->where('tags.id', $tag)))
            ->when(($filters['assignee'] ?? null), function (Builder $q, string $assignee) {
                if ($assignee === 'me') {
                    $q->where('assigned_to', auth()->id());
                } elseif ($assignee === 'unassigned') {
                    $q->whereNull('assigned_to');
                } elseif (ctype_digit($assignee)) {
                    $q->where('assigned_to', (int) $assignee);
                }
            })
            ->when(($filters['sla'] ?? null), function (Builder $q, string $sla) {
                if ($sla === 'breached') {
                    $q->where(function (Builder $inner) {
                        $inner->whereNotNull('first_response_breached_at')
                            ->orWhereNotNull('resolution_breached_at')
                            ->orWhere(function (Builder $overdue) {
                                $overdue->whereNull('first_responded_at')
                                    ->whereNotNull('first_response_due_at')
                                    ->where('first_response_due_at', '<', now());
                            })
                            ->orWhere(function (Builder $overdue) {
                                $overdue->whereNull('resolved_at')
                                    ->whereNotNull('resolution_due_at')
                                    ->where('resolution_due_at', '<', now());
                            });
                    });
                }
            })
            ->when(($filters['sort'] ?? 'newest'), function (Builder $q, string $sort) {
                match ($sort) {
                    'oldest' => $q->oldest(),
                    'updated' => $q->latest('updated_at'),
                    'priority' => $q->orderByRaw("case priority when 'urgent' then 4 when 'high' then 3 when 'normal' then 2 else 1 end desc")->latest(),
                    default => $q->latest(),
                };
            });
    }

    public function isFirstResponseOverdue(): bool
    {
        return ! $this->first_responded_at
            && $this->first_response_due_at
            && now()->greaterThanOrEqualTo($this->first_response_due_at);
    }

    public function isResolutionOverdue(): bool
    {
        return ! $this->resolved_at
            && $this->resolution_due_at
            && now()->greaterThanOrEqualTo($this->resolution_due_at);
    }

    public function isSlaBreached(): bool
    {
        return (bool) ($this->first_response_breached_at || $this->resolution_breached_at)
            || $this->isFirstResponseOverdue()
            || $this->isResolutionOverdue();
    }

    public function nextDueAt(): ?Carbon
    {
        $dates = [];

        if (! $this->first_responded_at && $this->first_response_due_at) {
            $dates[] = $this->first_response_due_at;
        }

        if (! $this->resolved_at && $this->resolution_due_at) {
            $dates[] = $this->resolution_due_at;
        }

        return $dates === [] ? null : collect($dates)->sort()->first();
    }

    public function slaState(): string
    {
        if ($this->isSlaBreached()) {
            return 'breached';
        }

        if ($this->isResolved()) {
            return 'met';
        }

        $next = $this->nextDueAt();

        if (! $next) {
            return 'none';
        }

        if (now()->greaterThanOrEqualTo($next->copy()->subMinutes(120))) {
            return 'at_risk';
        }

        return 'on_track';
    }

    public function isUnassigned(): bool
    {
        return $this->assigned_to === null;
    }
}
