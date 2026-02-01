<?php

namespace App\Listeners\Tenant;

use App\Events\Tenant\TaskCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendTaskCreatedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public string $tenantId; // ✅ Store tenant ID

    /**
     * Create the event listener
     */
    public function __construct()
    {
        $this->tenantId = tenant('id');
    }

    /**
     * Handle the event
     */
    public function handle(TaskCreated $event): void
    {
        // Re-initialize tenancy context
        tenancy()->initialize($this->tenantId);

        Log::info('Task notification STARTED', [
            'tenant_id' => tenant('id'), // Should work now
            'task_id' => $event->task->id,
            'task_title' => $event->task->title,
        ]);

        // Simulate processing
        sleep(2);

        Log::info('Task notification COMPLETED', [
            'task_id' => $event->task->id,
        ]);
    }
}