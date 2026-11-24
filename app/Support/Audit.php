<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Records sensitive administrative actions for an audit trail.
 */
class Audit
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function record(string $action, ?Model $subject = null, array $metadata = []): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey() !== null ? (string) $subject->getKey() : null,
            'metadata' => $metadata ?: null,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
