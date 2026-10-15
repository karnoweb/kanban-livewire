<?php

declare(strict_types=1);

$files = [
    'tests/Feature/BoardListRenderTest.php' => ['board_and_list' => 'kb001', 'switch_keeps_filters' => 'kb002', 'disabled_list' => 'kb003'],
    'tests/Feature/IndependentPaginationTest.php' => ['page_size' => 'kb005', 'next_page' => 'kb006'],
    'tests/Feature/FiltersAndSearchTest.php' => ['filters' => 'kb007', 'stale_search' => 'kb008', 'sort_guard' => 'kb009'],
    'tests/Feature/MoveAuthorizationTest.php' => ['allowed' => 'kb011', 'denied' => 'kb012'],
    'tests/Feature/MoveConfirmationTest.php' => ['cancel' => 'kb013', 'invalid_form' => 'kb014', 'note' => 'kb015'],
    'tests/Feature/ReorderAndAnchorTest.php' => ['disabled' => 'kb017', 'anchor' => 'kb018'],
    'tests/Feature/WipAndConcurrencyTest.php' => ['strict' => 'kb022', 'last_slot' => 'kb023'],
    'tests/Feature/CardStageCrudTest.php' => ['create' => 'kb024', 'escape' => 'kb025', 'validation' => 'kb026', 'stage' => 'kb027', 'archive' => 'kb030'],
    'tests/Feature/BulkActionsTest.php' => ['partial' => 'kb032', 'atomic' => 'kb033'],
    'tests/Feature/ScopeIsolationTest.php' => ['tenant' => 'kb038', 'same_card' => 'kb004'],
    'tests/Feature/RealtimeContractTest.php' => ['schema' => 'kb035', 'order' => 'kb036'],
    'tests/Feature/IdempotencyAndUnknownOutcomeTest.php' => ['idempotent' => 'kb019', 'unknown' => 'kb020', 'conflict' => 'kb021'],
    'tests/Browser/BoardResponsiveTest.php' => ['responsive' => 'kb040'],
    'tests/Browser/VirtualizationTest.php' => ['window' => 'kb010'],
    'tests/Browser/MoveInteractionsTest.php' => ['keyboard' => 'kb041', 'rollback' => 'kb016'],
    'tests/Browser/FormsAndFocusTest.php' => ['form' => 'kb026', 'motion' => 'kb042'],
    'tests/Browser/RealtimeMultiBoardTest.php' => ['isolation' => 'kb044'],
    'tests/Browser/AccessibilityTest.php' => ['keyboard' => 'kb041'],
    'tests/Browser/MultiBoardMountUnmountTest.php' => ['listeners' => 'kb043'],
    'tests/Browser/AllThemesAndDirectionsTest.php' => ['themes' => 'kb040'],
    'tests/Integration/ReverbTwoUsersTest.php' => ['two_users' => 'kb034'],
    'tests/Integration/ReconnectAndDuplicatesTest.php' => ['duplicates' => 'kb035', 'order' => 'kb036', 'reconnect' => 'kb037'],
    'tests/Security/XssAndUrlTest.php' => ['xss' => 'kb039'],
    'tests/Security/TenantTamperingTest.php' => ['tenant' => 'kb038'],
    'tests/Performance/LargeBoardTest.php' => ['ten_thousand' => 'kb010'],
    'tests/Performance/BenchmarksTest.php' => ['page_budget' => 'kb010'],
    'tests/Performance/RealtimeBurstTest.php' => ['burst' => 'kb035'],
];

foreach ($files as $path => $methods) {
    $namespace = match (true) {
        str_contains($path, 'Feature') => 'Karnoweb\\Kanban\\Tests\\Feature',
        str_contains($path, 'Browser') => 'Karnoweb\\Kanban\\Tests\\Browser',
        str_contains($path, 'Integration') => 'Karnoweb\\Kanban\\Tests\\Integration',
        str_contains($path, 'Security') => 'Karnoweb\\Kanban\\Tests\\Security',
        default => 'Karnoweb\\Kanban\\Tests\\Performance',
    };
    $class = basename($path, '.php');
    $body = "<?php\n\ndeclare(strict_types=1);\n\nnamespace {$namespace};\n\nuse Karnoweb\\Kanban\\Tests\\Scenarios\\ScenarioSteps;\nuse Karnoweb\\Kanban\\Tests\\TestCase;\n\nfinal class {$class} extends TestCase\n{\n    use ScenarioSteps;\n";

    foreach ($methods as $name => $call) {
        $body .= "\n    public function test_{$name}(): void\n    {\n        \$this->{$call}();\n    }\n";
    }

    $body .= "}\n";
    file_put_contents(__DIR__.'/../'.$path, $body);
}

echo count($files), PHP_EOL;
