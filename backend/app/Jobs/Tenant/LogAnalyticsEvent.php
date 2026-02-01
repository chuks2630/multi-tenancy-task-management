<?php

namespace App\Jobs\Tenant;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class LogAnalyticsEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $eventType,
        public ?int $userId,
        public ?string $entityType,
        public ?int $entityId,
        public ?array $metadata,
    ) {}

    public function handle(): void
    {

        Log::info('Analytics event processing STARTED', [
            'tenant_id' => tenant('id'),
            'event_type' => $this->eventType,
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
        ]);

        // Insert into analytics_events table
        DB::table('analytics_events')->insert([
            'event_type' => $this->eventType,
            'user_id' => $this->userId,
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'metadata' => json_encode($this->metadata),
            'created_at' => now(),
        ]);

        // Simulate processing delay (aggregations, external analytics, etc.)
        sleep(2);

        Log::info('Analytics event processing COMPLETED', [
            'event_type' => $this->eventType,
            'entity_id' => $this->entityId,
        ]);
    }
}