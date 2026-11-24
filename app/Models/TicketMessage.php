<?php

namespace App\Models;

use App\Enums\MessageAuthor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'author_type',
        'author_name',
        'author_email',
        'body',
        'is_internal',
        'message_id',
        'in_reply_to',
        'source',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'author_type' => MessageAuthor::class,
            'is_internal' => 'boolean',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function isInternal(): bool
    {
        return $this->is_internal;
    }

    public function isFromRequester(): bool
    {
        return $this->author_type === MessageAuthor::Requester;
    }
}
