<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Performance;

use Illuminate\Support\Facades\DB;
use Karnoweb\Kanban\Data\BoardQuery;
use Karnoweb\Kanban\Data\{BoardData, CardData, StageData};
use Karnoweb\Kanban\Providers\InlineDataProvider;
use Karnoweb\Kanban\Models\{BoardSources, KanbanModelDefinition, KanbanModelRegistry};
use Karnoweb\Kanban\Providers\EloquentKanbanDataProvider;
use Karnoweb\Kanban\Support\{BoardRegistry, ModelCursor};
use Karnoweb\Kanban\Tests\Fixtures\EnterpriseTestCase;
use Karnoweb\Kanban\Tests\Fixtures\EnterpriseTask;

final class EnterpriseQueryPerformanceTest extends EnterpriseTestCase
{
    public function test_ten_columns_at_ten_and_one_hundred_thousand_rows_have_bounded_hydration(): void
    {
        $models = new KanbanModelRegistry;
        $models->register('tasks', PerformanceTask::class);
        $provider = new EloquentKanbanDataProvider(new BoardSources(app(BoardRegistry::class), $models), new ModelCursor);
        $reports = [];
        foreach ([10000, 100000] as $size) {
            DB::table('enterprise_tasks')->delete();
            for ($offset = 0; $offset < $size; $offset += 500) {
                $batch = [];
                for ($i = $offset; $i < min($size, $offset + 500); $i++) {
                    $batch[] = ['title' => 'Card '.$i, 'owner_id' => 'alice', 'status' => 'stage-'.($i % 10), 'rank' => $i * 1024, 'amount' => 1];
                }
                DB::table('enterprise_tasks')->insert($batch);
            }
            // Existing small-data provider is a reference baseline, not a database adapter.
            memory_reset_peak_usage(); $baselineStart = hrtime(true);
            $baselineCards = [];
            foreach (range(0, $size - 1) as $i) {
                $baselineCards[] = new CardData('tasks:'.$i, 'baseline', 'stage-'.($i % 10), 'Card '.$i, manualOrder: (string) ($i * 1024));
            }
            $baselineStages = array_map(fn ($i) => new StageData('stage-'.$i, 'baseline', 'Column '.$i, $i), range(0, 9));
            $baseline = new InlineDataProvider(new BoardData('baseline', 'Baseline', 'workspace-a'), $baselineStages, $baselineCards);
            $baseline->counts($this->context, new BoardQuery);
            foreach ($baselineStages as $stage) { $baseline->stageCards($this->context, $stage->id, new BoardQuery); }
            $baselineReport = ['provider' => 'existing InlineDataProvider', 'materialized_cards' => count($baselineCards),
                'milliseconds_including_materialization' => round((hrtime(true) - $baselineStart) / 1e6, 3), 'peak_bytes' => memory_get_peak_usage(true),
                'note' => 'In-memory reference; database seed/fetch cost excluded. Not a like-for-like database latency comparison.'];
            unset($baseline, $baselineCards, $baselineStages); gc_collect_cycles(); memory_reset_peak_usage();
            $hydrated = 0;
            PerformanceTask::retrieved(function () use (&$hydrated): void { $hydrated++; });
            DB::enableQueryLog(); DB::flushQueryLog();
            $start = hrtime(true); $startMemory = memory_get_usage(true);
            $counts = $provider->counts($this->context, new BoardQuery);
            $loaded = 0;
            foreach ($provider->stages($this->context, new BoardQuery) as $stage) {
                $page = $provider->stageCards($this->context, $stage->id, new BoardQuery);
                $loaded += count($page->items);
                self::assertCount(25, $page->items);
                self::assertSame(intdiv($size, 10), $page->totalCount);
                self::assertTrue($page->hasMore);
            }
            $queries = count(DB::getQueryLog());
            $reports[] = ['rows' => $size, 'columns' => 10, 'page_size' => 25, 'loaded' => $loaded,
                'hydrated' => $hydrated, 'queries' => $queries, 'milliseconds' => round((hrtime(true) - $start) / 1e6, 3),
                'memory_delta_bytes' => memory_get_usage(true) - $startMemory, 'process_peak_bytes' => memory_get_peak_usage(true),
                'php' => PHP_VERSION, 'database' => DB::selectOne('select sqlite_version() as version')->version,
                'os' => PHP_OS_FAMILY, 'cache' => 'disabled', 'temperature' => 'first pass after insert'];
            $reports[array_key_last($reports)]['reference_baseline'] = $baselineReport;
            self::assertSame($size, $counts->boardTotal);
            self::assertSame(250, $loaded);
            self::assertSame(260, $hydrated); // One lookahead row per column.
            self::assertSame(21, $queries); // One grouped count, ten page counts, ten bounded selects.
            PerformanceTask::flushEventListeners();
            DB::disableQueryLog();
        }
        if (getenv('KANBAN_WRITE_BENCHMARK') === '1') {
            $outputDirectory = dirname(__DIR__, 2).'/.phpunit.cache';
            if (! is_dir($outputDirectory)) {
                mkdir($outputDirectory, 0777, true);
            }
            file_put_contents(dirname(__DIR__, 2).'/.phpunit.cache/enterprise-benchmark.json', json_encode($reports, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL);
        }
    }
}

final class PerformanceTask extends EnterpriseTask
{
    public static function kanbanDefinition(): KanbanModelDefinition
    {
        $definition = parent::kanbanDefinition();
        $definition->stageDefinitions = [];
        foreach (range(0, 9) as $i) {
            $definition->stages(['stage-'.$i => 'Column '.$i]);
        }

        return $definition;
    }
}
