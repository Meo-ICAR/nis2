<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Incident extends Model
{
    use LogsActivity;

    protected $fillable = [
        'application_id',
        'title',
        'description',
        'incident_type',
        'severity',
        'status',
        'detected_at',
        'resolved_at',
        'acn_notified',
        'acn_notification_date',
        'acn_protocol_number',
        'impact_analysis',
    ];

    protected $casts = [
        'detected_at' => 'datetime',
        'resolved_at' => 'datetime',
        'acn_notified' => 'boolean',
        'acn_notification_date' => 'datetime',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty();
    }
}
