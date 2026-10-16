<?php

declare(strict_types=1);

require dirname(__DIR__, 2).'/vendor/autoload.php';

use Illuminate\Cache\{FileStore, Repository as Cache};
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;
use Karnoweb\Kanban\Actions\EloquentKanbanActionHandler;
use Karnoweb\Kanban\Data\{ActionCommand, BoardContext};
use Karnoweb\Kanban\Models\KanbanModelRegistry;
use Karnoweb\Kanban\Support\BoardRegistry;
use Karnoweb\Kanban\Tests\Fixtures\{EnterpriseBoardFactory, EnterpriseTask};

[, $file, $cachePath, $start, $id] = $argv;
$container = new Container;
Container::setInstance($container);
$container->instance('config', new Repository(['kanban' => require dirname(__DIR__, 2).'/config/kanban.php']));
$container->instance('events', new Dispatcher($container));
$cache = new Cache(new FileStore(new Filesystem, $cachePath));
$container->instance(\Illuminate\Contracts\Cache\Repository::class, $cache);
$container->bind(\Karnoweb\Kanban\Contracts\RankStrategy::class, \Karnoweb\Kanban\Actions\SparseNumericRank::class);
$container->bind(\Karnoweb\Kanban\Contracts\ModelOperationStore::class, \Karnoweb\Kanban\Actions\ModelOperationStore::class);
Facade::setFacadeApplication($container);
$capsule = new Capsule($container);
$capsule->addConnection(['driver' => 'sqlite', 'database' => $file, 'busy_timeout' => 10000, 'transaction_mode' => 'IMMEDIATE']);
$capsule->setAsGlobal(); $capsule->bootEloquent();
$capsule->getConnection()->setTransactionManager(new DatabaseTransactionsManager);
$models = new KanbanModelRegistry; $models->register('tasks', EnterpriseTask::class);
$container->instance(KanbanModelRegistry::class, $models);
$boards = new BoardRegistry($container); $boards->register('tasks', EnterpriseBoardFactory::class);
$container->instance(BoardRegistry::class, $boards);
while (microtime(true) < (float) $start) { usleep(200); }
$result = $container->make(EloquentKanbanActionHandler::class)->execute(
    ActionCommand::allowlisted('race-'.$id, 'moveCard', ['cardId' => 'tasks:'.$id, 'fromStageId' => 'todo', 'toStageId' => 'doing'], '1'),
    new BoardContext('tasks', 'workspace-a', 'alice', 'race-'.$id),
);
echo json_encode(['status' => $result->status->value, 'code' => $result->messageCode]);
