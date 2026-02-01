<?php

namespace App\Observers;

use App\Models\Tenant\Task;
use App\Models\Tenant\AnalyticsEvent;

class TaskObserver
{
    public function created(Task $task): void
    {
        AnalyticsEvent::log('task_created', $task->created_by, 'Task', $task->id, [
            'board_id' => $task->board_id,
            'priority' => $task->priority,
        ]);
    }

    public function updated(Task $task): void
    {
        if ($task->isDirty('status') && $task->status === 'done') {
            AnalyticsEvent::log('task_completed', auth()->id(), 'Task', $task->id, [
                'board_id' => $task->board_id,
            ]);
        }
    }

    public function deleted(Task $task): void
    {
        AnalyticsEvent::log('task_deleted', auth()->id(), 'Task', $task->id);
    }
}