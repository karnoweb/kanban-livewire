<?php

declare(strict_types=1);

$root = realpath(dirname(__DIR__, 2).'/.phpunit.cache');
$file = realpath(getenv('WORKBENCH_DB') ?: '');
if ($root === false || $file === false || ! str_starts_with($file, $root.DIRECTORY_SEPARATOR)) {
    throw new RuntimeException('Browser fixture requires an isolated database under .phpunit.cache.');
}
$pdo = new PDO('sqlite:'.$file);
$statement = $pdo->prepare('INSERT INTO demo_model_cards (scope_key,source,title,status,sort_rank,version,amount,team) VALUES (?,?,?,?,?,?,?,?)');
foreach (range(4, 75) as $i) {
    $statement->execute(['tenant-a', 'tasks', 'Window card '.$i, 'todo', $i * 1024, 1, 1, 'Engineering']);
}
