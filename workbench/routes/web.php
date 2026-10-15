<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use Workbench\App\Models\DemoUser;

Broadcast::routes(['middleware' => ['web']]);

Route::get('/workbench/health', fn () => response('ok'));

Route::get('/login-as/{name}', function (string $name) {
    Auth::login(DemoUser::query()->where('name', $name)->firstOrFail());

    return redirect('/tasks');
});

Route::get('/vendor/kanban/{file}', function (string $file) {
    abort_unless(in_array($file, ['kanban.css', 'kanban.js'], true), 404);

    return response()->file(dirname(__DIR__, 2).'/resources/dist/'.$file, [
        'Content-Type' => str_ends_with($file, '.css') ? 'text/css' : 'application/javascript',
    ]);
});

Route::get('/workbench/echo.js', fn () => response()->file(dirname(__DIR__).'/resources/dist/echo.js', ['Content-Type' => 'application/javascript']));

Route::get('/multi', fn () => view('multi'));

foreach (['crm' => 'crm.deals', 'tickets' => 'tickets.board', 'tasks' => 'tasks.board', 'orders' => 'orders.board'] as $path => $key) {
    Route::get('/'.$path, fn () => view('board', ['key' => $key]));
}
