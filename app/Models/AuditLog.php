<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'event_type',
        'user_id',
        'admin_id',
        'subject_type',
        'subject_id',
        'payload',
        'ip_address',
        'created_at',
    ];

    /**
     * Disable automatic timestamp management.
     * Only created_at is used and it is set by the database default (CURRENT_TIMESTAMP).
     */
    public $timestamps = false;

    /**
     * The name of the "created at" column.
     */
    const CREATED_AT = 'created_at';

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload'    => 'array',
            'created_at' => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The user involved in the audited event.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The admin who performed the action.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
