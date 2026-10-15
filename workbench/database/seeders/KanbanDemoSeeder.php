<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use Workbench\App\Models\DemoCard;
use Workbench\App\Models\DemoStage;
use Workbench\App\Models\DemoUser;

final class KanbanDemoSeeder extends Seeder
{
    /** @var array<string, array{title: string, stages: array<string, string>, wip?: array<string, int>}> */
    private const BOARDS = [
        'crm.deals' => ['title' => 'CRM', 'stages' => ['new' => 'جدید', 'progress' => 'پیگیری', 'negotiation' => 'مذاکره', 'closed' => 'بسته‌شده']],
        'tickets.board' => ['title' => 'Tickets', 'stages' => ['open' => 'Open', 'answering' => 'Answering', 'waiting' => 'Waiting', 'closed' => 'Closed']],
        'tasks.board' => ['title' => 'Tasks', 'stages' => ['todo' => 'To do', 'doing' => 'Doing', 'done' => 'Done'], 'wip' => ['doing' => 3]],
        'orders.board' => ['title' => 'Orders', 'stages' => ['review' => 'Review', 'finance' => 'Finance', 'shipped' => 'Shipped']],
    ];

    public function run(): void
    {
        DemoUser::query()->create(['id' => 1, 'name' => 'alice', 'tenant_id' => 'tenant-a']);
        DemoUser::query()->create(['id' => 2, 'name' => 'bob', 'tenant_id' => 'tenant-a']);
        DemoUser::query()->create(['id' => 3, 'name' => 'carol', 'tenant_id' => 'tenant-b']);

        foreach (['tenant-a', 'tenant-b'] as $tenant) {
            foreach (self::BOARDS as $board => $config) {
                $order = 0;
                $first = null;

                foreach ($config['stages'] as $id => $title) {
                    $first ??= $id;
                    $limit = $config['wip'][$id] ?? null;
                    DemoStage::query()->create([
                        'board_key' => $board,
                        'scope_key' => $tenant,
                        'public_id' => $id,
                        'title' => $title,
                        'sort_order' => $order++,
                        'color' => '#336699',
                        'wip_limit' => $limit,
                        'wip_mode' => $limit === null ? 'off' : 'strict',
                    ]);
                }

                foreach (range(1, 3) as $number) {
                    DemoCard::query()->create([
                        'board_key' => $board,
                        'scope_key' => $tenant,
                        'public_id' => (string) (100 + $number),
                        'stage_id' => (string) $first,
                        'title' => $tenant.' '.$board.' card '.$number,
                        'version' => '1',
                        'manual_order' => (float) $number,
                        'priority' => $number === 1 ? 'high' : 'low',
                    ]);
                }
            }
        }
    }
}
