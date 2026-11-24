<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TicketAttachment extends Model
{
    protected $fillable = [
        'ticket_id',
        'ticket_message_id',
        'uploaded_by',
        'original_name',
        'path',
        'mime_type',
        'size',
        'source',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(TicketMessage::class, 'ticket_message_id');
    }

    public function temporaryUrl(int $minutes = 30): string
    {
        return Storage::disk(config('helpdesk.attachments.disk'))
            ->temporaryUrl($this->path, now()->addMinutes($minutes));
    }

    public function humanSize(): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = max((int) $this->size, 0);
        $power = $size > 0 ? min((int) floor(log($size, 1024)), count($units) - 1) : 0;
        $value = $size / (1024 ** $power);

        return number_format($value, $power === 0 ? 0 : 1).' '.$units[$power];
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }
}
