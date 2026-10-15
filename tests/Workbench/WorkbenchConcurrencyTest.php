<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Workbench;

use Workbench\App\Models\DemoCard;
use Workbench\App\Models\DemoStage;

/**
 * Two real PHP processes race for the last strict-WIP slot on one SQLite file.
 */
final class WorkbenchConcurrencyTest extends WorkbenchTestCase
{
    private static string $file = '';

    public static function setUpBeforeClass(): void
    {
        self::$file = sys_get_temp_dir().DIRECTORY_SEPARATOR.'kb-concurrency-'.bin2hex(random_bytes(4)).'.sqlite';
        touch(self::$file);
        parent::setUpBeforeClass();
        putenv('WORKBENCH_DB='.self::$file);
        $_ENV['WORKBENCH_DB'] = self::$file;
        $_SERVER['WORKBENCH_DB'] = self::$file;
    }

    public static function tearDownAfterClass(): void
    {
        @unlink(self::$file);
        parent::tearDownAfterClass();
    }

    public function test_two_processes_cannot_both_take_the_last_wip_slot(): void
    {
        // doing has limit 3: put two cards in so exactly one slot is free.
        DemoCard::query()->where('scope_key', 'tenant-a')->where('board_key', 'tasks.board')->whereIn('public_id', ['101', '102'])->update(['stage_id' => 'doing']);
        $this->assertSame(3, (int) DemoStage::query()->where('scope_key', 'tenant-a')->where('board_key', 'tasks.board')->where('public_id', 'doing')->value('wip_limit'));

        $php = PHP_BINARY;
        $script = __DIR__.'/child-move.php';
        $start = microtime(true) + 1.5;
        $processes = [];

        foreach (['103', '103x'] as $index => $card) {
            // second process competes with a different card created for the race
            if ($card === '103x') {
                DemoCard::query()->create([
                    'board_key' => 'tasks.board', 'scope_key' => 'tenant-a', 'public_id' => '104',
                    'stage_id' => 'todo', 'title' => 'racer', 'version' => '1', 'manual_order' => 9,
                ]);
                $card = '104';
            }

            $cmd = [$php, $script, self::$file, (string) $start, $card, 'race-'.$index];
            $process = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            $this->assertIsResource($process);
            $processes[] = [$process, $pipes];
        }

        $statuses = [];

        foreach ($processes as [$process, $pipes]) {
            $out = stream_get_contents($pipes[1]);
            $err = stream_get_contents($pipes[2]);
            proc_close($process);
            $decoded = json_decode((string) $out, true);
            $this->assertIsArray($decoded, 'child failed: '.$err.$out);
            $statuses[] = $decoded['status'];
        }

        sort($statuses);
        $this->assertSame(['success', 'wip_limit'], $statuses);
        $this->assertSame(3, DemoCard::query()->where('scope_key', 'tenant-a')->where('board_key', 'tasks.board')->where('stage_id', 'doing')->where('archived', false)->count());
    }
}
