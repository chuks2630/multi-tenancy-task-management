<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Jobs\Tenant\LogAnalyticsEvent;

class AnalyticsEvent extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'event_type',
        'user_id',
        'entity_type',
        'entity_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log an event
     */
    public static function log(
        string $eventType,
        ?int $userId = null,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $metadata = null
    ): void {
        LogAnalyticsEvent::dispatch(
            eventType: $eventType,
            userId: $userId ?? auth()->id(),
            entityType: $entityType,
            entityId: $entityId,
            metadata: $metadata,
        );
        // return self::create([
        //     'event_type' => $eventType,
        //     'user_id' => $userId ?? auth()->id(),
        //     'entity_type' => $entityType,
        //     'entity_id' => $entityId,
        //     'metadata' => $metadata,
        // ]);
    }
}