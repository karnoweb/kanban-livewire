<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use Workbench\App\Models\DemoCard;
use Workbench\App\Models\DemoStage;
use Workbench\App\Models\DemoUser;

final class KanbanDemoSeeder extends Seeder
{
    /** @var array<string, array{title: string, stages: array<string, array{title: string, color: string}>, wip?: array<string, int>}> */
    private const BOARDS = [
        'crm.deals' => [
            'title' => 'CRM',
            'stages' => [
                'new' => ['title' => 'جدید · Lead', 'color' => '#3b82f6'],
                'progress' => ['title' => 'پیگیری · Discovery', 'color' => '#8b5cf6'],
                'negotiation' => ['title' => 'مذاکره · Proposal', 'color' => '#f59e0b'],
                'closed' => ['title' => 'بسته‌شده · Won', 'color' => '#10b981'],
            ],
        ],
        'tickets.board' => [
            'title' => 'Tickets',
            'stages' => [
                'open' => ['title' => 'Open', 'color' => '#ef4444'],
                'answering' => ['title' => 'Answering', 'color' => '#3b82f6'],
                'waiting' => ['title' => 'Waiting on customer', 'color' => '#f59e0b'],
                'closed' => ['title' => 'Closed', 'color' => '#64748b'],
            ],
        ],
        'tasks.board' => [
            'title' => 'Tasks',
            'stages' => [
                'todo' => ['title' => 'To do', 'color' => '#6366f1'],
                'doing' => ['title' => 'Doing', 'color' => '#0ea5e9'],
                'done' => ['title' => 'Done', 'color' => '#10b981'],
            ],
            'wip' => ['doing' => 3],
        ],
        'orders.board' => [
            'title' => 'Orders',
            'stages' => [
                'review' => ['title' => 'Review', 'color' => '#f59e0b'],
                'finance' => ['title' => 'Finance Approval', 'color' => '#8b5cf6'],
                'shipped' => ['title' => 'Shipped', 'color' => '#10b981'],
            ],
        ],
    ];

    public function run(): void
    {
        DemoUser::query()->updateOrCreate(['id' => 1], ['name' => 'alice', 'tenant_id' => 'tenant-a']);
        DemoUser::query()->updateOrCreate(['id' => 2], ['name' => 'bob', 'tenant_id' => 'tenant-a']);
        DemoUser::query()->updateOrCreate(['id' => 3], ['name' => 'carol', 'tenant_id' => 'tenant-b']);
        $this->call(EloquentKanbanSeeder::class);

        foreach (['tenant-a', 'tenant-b'] as $tenant) {
            foreach (self::BOARDS as $board => $config) {
                $order = 0;
                $first = null;

                foreach ($config['stages'] as $id => $stageDef) {
                    $first ??= $id;
                    $limit = $config['wip'][$id] ?? null;
                    DemoStage::query()->updateOrCreate(
                        [
                            'board_key' => $board,
                            'scope_key' => $tenant,
                            'public_id' => $id,
                        ],
                        [
                            'title' => $stageDef['title'],
                            'sort_order' => $order++,
                            'color' => $stageDef['color'],
                            'wip_limit' => $limit,
                            'wip_mode' => $limit === null ? 'off' : 'strict',
                        ]
                    );
                }

                foreach (range(1, 3) as $number) {
                    $detail = $this->cardDetail($board, $number);

                    DemoCard::query()->updateOrCreate(
                        [
                            'board_key' => $board,
                            'scope_key' => $tenant,
                            'public_id' => (string) (100 + $number),
                        ],
                        [
                            'stage_id' => (string) $first,
                            'title' => $tenant.' '.$board.' card '.$number,
                            'description' => $detail['description'],
                            'priority' => $detail['priority'],
                            'due_at' => $detail['due_at'],
                            'version' => '1',
                            'manual_order' => (float) $number,
                            'metadata' => $detail['metadata'],
                        ]
                    );
                }
            }
        }
    }

    /** @return array{description: string, priority: string, due_at: string, metadata: array<string, mixed>} */
    private function cardDetail(string $board, int $number): array
    {
        $prefix = match ($board) {
            'crm.deals' => 'CRM',
            'tickets.board' => 'TCK',
            'orders.board' => 'ORD',
            default => 'TSK',
        };

        $priority = match ($number) {
            1 => 'high',
            2 => 'medium',
            default => 'low',
        };

        $badges = match ($number) {
            1 => [
                ['text' => 'Enterprise SLA', 'tone' => 'danger', 'description' => 'Response under 2h'],
                ['text' => 'Security Audit', 'tone' => 'warning'],
                ['text' => 'Q4 Priority', 'tone' => 'info'],
            ],
            2 => [
                ['text' => 'Backend API', 'tone' => 'info'],
                ['text' => 'Ready for QA', 'tone' => 'success'],
            ],
            default => [
                ['text' => 'UX Polish', 'tone' => 'neutral'],
                ['text' => 'Documentation', 'tone' => 'info'],
            ],
        };

        if ($board === 'orders.board' && $number === 1) {
            $badges[] = ['text' => 'Review Approved ✓', 'tone' => 'success'];
        } elseif ($board === 'orders.board') {
            $badges[] = ['text' => 'Awaiting Review', 'tone' => 'warning'];
        }

        $assignees = match ($number) {
            1 => [
                ['id' => 'u-alice', 'name' => 'Alice Karimi', 'label' => 'Tech Lead'],
                ['id' => 'u-bob', 'name' => 'Bob Ahmadi', 'label' => 'QA Engineer'],
            ],
            2 => [
                ['id' => 'u-sara', 'name' => 'Sara Moradi', 'label' => 'Product Owner'],
            ],
            default => [
                ['id' => 'u-reza', 'name' => 'Reza Nouri', 'label' => 'DevOps'],
                ['id' => 'u-mina', 'name' => 'Mina Rahimi', 'label' => 'Designer'],
                ['id' => 'u-ali', 'name' => 'Ali Tehrani', 'label' => 'Backend'],
            ],
        };

        $attachments = $number === 1 ? [
            ['id' => 'att-1', 'name' => 'architecture-rfc-v2.pdf', 'mime' => 'application/pdf', 'size' => 428032, 'url' => 'https://example.com/specs/rfc-v2.pdf'],
            ['id' => 'att-2', 'name' => 'load-test-report.csv', 'mime' => 'text/csv', 'size' => 94208, 'url' => 'https://example.com/specs/load.csv'],
        ] : ($number === 2 ? [
            ['id' => 'att-3', 'name' => 'contract-redline.docx', 'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'size' => 184320, 'url' => 'https://example.com/specs/contract.docx'],
        ] : []);

        $metadata = [
            'code' => sprintf('%s-%d', $prefix, 100 + $number),
            'points' => $number === 1 ? 8 : ($number === 2 ? 5 : 3),
            'progress' => $number === 1 ? 65 : ($number === 2 ? 35 : 90),
            'checklistDone' => $number === 1 ? 4 : ($number === 2 ? 2 : 5),
            'checklistTotal' => $number === 1 ? 6 : ($number === 2 ? 5 : 5),
            'commentsCount' => $number === 1 ? 7 : ($number === 2 ? 3 : 1),
            'blocked' => $board === 'orders.board' && $number > 1,
            'url' => 'https://example.com/work-items/'.(100 + $number),
            'badges' => $badges,
            'assignees' => $assignees,
            'attachments' => $attachments,
            'actions' => [
                ['id' => 'escalate', 'label' => 'Escalate to Lead', 'enabled' => true],
            ],
        ];

        if ($board === 'orders.board' && $number === 1) {
            $metadata['reviewApproved'] = true;
        }

        $description = match ($number) {
            1 => 'Verify idempotent ledger hand-off, strict WIP lock under concurrent workers, and audit log retention across multi-tenant scopes.',
            2 => 'Coordinate stakeholder sign-off, validate field-level permissions, and prepare release checklist with rollback runbook.',
            default => 'Review edge cases on RTL/LTR layouts, keyboard navigation shortcuts, and high-density table rendering.',
        };

        return [
            'description' => $description,
            'priority' => $priority,
            'due_at' => sprintf('2026-11-%02d', 10 + $number * 4),
            'metadata' => $metadata,
        ];
    }
}
