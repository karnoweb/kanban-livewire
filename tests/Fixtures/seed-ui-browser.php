<?php

declare(strict_types=1);

$root = realpath(dirname(__DIR__, 2).'/.phpunit.cache');
$file = realpath(getenv('WORKBENCH_DB') ?: '');
if ($root === false || $file === false || !str_starts_with($file, $root.DIRECTORY_SEPARATOR)) {
    throw new RuntimeException('UI fixture requires an isolated database under .phpunit.cache.');
}
$pdo = new PDO('sqlite:'.$file);
$query = $pdo->query("SELECT metadata FROM demo_cards WHERE scope_key='tenant-a' AND board_key='tasks.board' AND public_id='101'");
$meta = json_decode($query->fetchColumn(), true, flags: JSON_THROW_ON_ERROR);
$meta['assignees'] = array_merge($meta['assignees'] ?? [], [['id' => 'extra-1', 'name' => 'Sara Moradi'], ['id' => 'extra-2', 'name' => 'Reza Nouri'], ['id' => 'extra-3', 'name' => 'Mina Rahimi']]);
$meta['checklist'] = [['title' => 'Review requirements', 'done' => true], ['title' => 'Verify layout', 'done' => false]];
$meta['comments'] = [['author' => 'Alice', 'body' => 'Ready for review.', 'createdAt' => '2026-10-18 10:00']];
$meta['checklistDone'] = 1;
$meta['checklistTotal'] = 2;
$meta['commentsCount'] = 1;
$meta['attachments'][] = ['id' => 'cover', 'name' => 'Kanban preview', 'mime' => 'image/svg+xml', 'url' => '/workbench/ui-fixture-cover.svg'];
$pdo->prepare("UPDATE demo_cards SET metadata=? WHERE scope_key='tenant-a' AND board_key='tasks.board' AND public_id='101'")->execute([json_encode($meta, JSON_THROW_ON_ERROR)]);
