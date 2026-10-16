<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Integration;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Karnoweb\Kanban\Tests\Fixtures\EnterpriseTestCase;

final class EnterpriseConcurrencyTest extends EnterpriseTestCase
{
    public function test_two_real_processes_compete_for_one_remaining_capacity_slot(): void
    {
        $root = dirname(__DIR__, 2).'/.phpunit.cache/enterprise-race-'.bin2hex(random_bytes(6));
        mkdir($root, 0777, true);
        $file = $root.'/race.sqlite';
        DB::statement("VACUUM INTO '".str_replace("'", "''", $file)."'");
        config(['database.connections.race' => ['driver' => 'sqlite', 'database' => $file]]);
        DB::connection('race')->table('enterprise_tasks')->insert([
            ['title' => 'Existing', 'owner_id' => 'alice', 'status' => 'doing'],
            ['title' => 'Racer one', 'owner_id' => 'alice', 'status' => 'todo'],
            ['title' => 'Racer two', 'owner_id' => 'alice', 'status' => 'todo'],
        ]);
        $children = [];
        $start = (string) (microtime(true) + .5);
        try {
            foreach (['2', '3'] as $id) {
                $child = proc_open([PHP_BINARY, dirname(__DIR__).'/Fixtures/enterprise-child-move.php', $file, $root.'/cache', $start, $id],
                    [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
                self::assertIsResource($child);
                $children[] = [$child, $pipes];
            }
            $statuses = [];
            foreach ($children as [$child, $pipes]) {
                $out = stream_get_contents($pipes[1]); $err = stream_get_contents($pipes[2]);
                fclose($pipes[1]); fclose($pipes[2]);
                self::assertSame(0, proc_close($child), $err.$out);
                $result = json_decode($out, true);
                self::assertIsArray($result, $err.$out);
                $statuses[] = $result['status'];
            }
            sort($statuses);
            self::assertSame(['success', 'wip_limit'], $statuses);
            self::assertSame(2, DB::connection('race')->table('enterprise_tasks')->where('status', 'doing')->count());
        } finally {
            DB::purge('race');
            $resolved = realpath($root); $parent = realpath(dirname(__DIR__, 2).'/.phpunit.cache');
            if ($resolved !== false && $parent !== false && str_starts_with($resolved, $parent.DIRECTORY_SEPARATOR)) {
                (new Filesystem)->deleteDirectory($resolved);
            }
        }
    }
}
