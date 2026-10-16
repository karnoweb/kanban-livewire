<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class EloquentKanbanSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['tenant-a', 'tenant-b'] as $scope) {
            foreach (['tasks' => ['title', 'status', 'todo'], 'tickets' => ['subject', 'state', 'open'], 'classes' => ['name', 'approval_state', 'pending']] as $source => [$title, $stage, $value]) {
                foreach (range(1, 3) as $i) {
                    DB::table('demo_model_cards')->updateOrInsert(['scope_key' => $scope, 'source' => $source, $title => $scope.' '.$source.' '.$i],
                        [$stage => $value, 'sort_rank' => $i * 1024, 'version' => 1, 'team' => $i === 1 ? 'Support' : 'Engineering', 'amount' => $i * 10, 'priority' => 'high', 'created_at' => now(), 'updated_at' => now()]);
                }
            }
        }
    }
}
