<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use App\Models\Tenant\Board;
use App\Models\Tenant\Task;
use App\Models\Tenant\User;
use Carbon\Carbon;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        
        if ($users->count() === 0) {
            $this->command->error('No users found. Please create users first.');
            return;
        }

        // Create boards
        $boards = [];
        for ($i = 1; $i <= 5; $i++) {
            $boards[] = Board::create([
                'name' => "Project Board $i",
                'description' => "Sample board for testing analytics",
                'color' => '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT),
            ]);
        }

        // Create tasks with varied dates
        $statuses = ['todo', 'in_progress', 'done'];
        $priorities = ['low', 'medium', 'high', 'urgent'];

        foreach ($boards as $board) {
            for ($i = 0; $i < 20; $i++) {
                $createdAt = Carbon::now()->subDays(rand(1, 30));
                
                Task::create([
                    'board_id' => $board->id,
                    'title' => "Task $i for {$board->name}",
                    'description' => 'Sample task for analytics testing',
                    'status' => $statuses[array_rand($statuses)],
                    'priority' => $priorities[array_rand($priorities)],
                    'position' => $i,
                    'created_by' => $users->random()->id,
                    'assigned_to' => rand(0, 1) ? $users->random()->id : null,
                    'due_date' => rand(0, 1) ? Carbon::now()->addDays(rand(1, 30)) : null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt->copy()->addHours(rand(1, 48)),
                ]);
            }
        }

        $this->command->info('Sample data created successfully!');
    }
}