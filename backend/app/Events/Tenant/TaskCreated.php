<?php

namespace App\Events\Tenant;

use App\Models\Tenant\Task;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Task $task
    ) {}
}