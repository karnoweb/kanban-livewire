<?php

declare(strict_types=1);

// Separate OS process used by WorkbenchConcurrencyTest. Boots only Eloquent on
// the shared SQLite file, waits for a common start time, then runs the real
// WorkbenchWriter so both processes compete for the last WIP slot.

require dirname(__DIR__, 2).'/vendor/autoload.php';

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Facade;
use Illuminate\Config\Repository;
use Illuminate\Support\DateFactory;
use Karnoweb\Kanban\Data\ActionCommand;
use Karnoweb\Kanban\Data\BoardContext;
use Workbench\App\Kanban\WorkbenchWriter;

[, $db, $start, $card, $operation] = $argv;

$container = new Container;
Container::setInstance($container);
$container->instance('config', new Repository(['kanban' => require dirname(__DIR__, 2).'/config/kanban.php']));
$container->instance('date', new DateFactory);
Facade::setFacadeApplication($container);

$capsule = new Capsule($container);
$capsule->addConnection([
    'driver' => 'sqlite',
    'database' => $db,
    'busy_timeout' => 20000,
    'transaction_mode' => 'IMMEDIATE',
    'foreign_key_constraints' => false,
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

while (microtime(true) < (float) $start) {
    usleep(200);
}

$result = (new WorkbenchWriter)->execute(
    ActionCommand::allowlisted($operation, 'moveCard', ['cardId' => $card, 'fromStageId' => 'todo', 'toStageId' => 'doing']),
    new BoardContext('tasks.board', 'tenant-a', '1', 'child'),
);

echo json_encode(['card' => $card, 'status' => $result->status->value]);
