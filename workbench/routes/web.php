<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use Karnoweb\Kanban\Support\ContextFactory;
use Karnoweb\Kanban\Support\PreferenceManager;
use Workbench\App\Models\DemoUser;

Broadcast::routes(['middleware' => ['web']]);

Route::middleware('web')->group(function (): void {
    Route::get('/workbench/health', fn () => response('ok'));

    Route::redirect('/', '/tasks');

    Route::get('/login-as/{name}', function (string $name) {
        Auth::login(DemoUser::query()->where('name', $name)->firstOrFail());

        return redirect()->back()->header('Fallback', '/tasks');
    });

    Route::post('/workbench/playground', function (Request $request, ContextFactory $contexts, PreferenceManager $preferences) {
        $boardKey = (string) $request->input('board_key', 'tasks.board');
        $preset = (string) $request->input('preset', '');

        $booleanFeatures = [
            'toolbar',
            'search',
            'filters',
            'view_switch',
            'theme_switch',
            'direction_switch',
            'density_switch',
            'create_card',
            'update_card',
            'create_stage',
            'update_stage',
            'archive_card',
            'archive_stage',
            'restore_card',
            'drag_drop',
            'reorder_cards',
            'reorder_stages',
            'bulk',
            'wip',
            'details',
            'virtualization',
            'realtime',
            'custom_actions',
        ];

        if ($preset !== '') {
            $features = array_fill_keys($booleanFeatures, true);
            $features['realtime'] = false;
            $hideShell = false;

            if ($preset === 'board_only' || $preset === 'full_bleed') {
                $features['toolbar'] = false;
                $features['search'] = false;
                $features['filters'] = false;
                $features['view_switch'] = false;
                $features['theme_switch'] = false;
                $features['direction_switch'] = false;
                $features['density_switch'] = false;
                $hideShell = $preset === 'full_bleed';
            } elseif ($preset === 'readonly') {
                $features['toolbar'] = false;
                $features['drag_drop'] = false;
                $features['reorder_cards'] = false;
                $features['reorder_stages'] = false;
                $features['create_card'] = false;
                $features['update_card'] = false;
                $features['create_stage'] = false;
                $features['update_stage'] = false;
                $features['archive_card'] = false;
                $features['archive_stage'] = false;
                $features['restore_card'] = false;
                $features['bulk'] = false;
                $features['custom_actions'] = false;
            }

            $existing = session('workbench.playground.'.$boardKey, []);
            $overrides = array_replace_recursive(is_array($existing) ? $existing : [], [
                'features' => $features,
            ]);
            session()->put('workbench.playground.'.$boardKey, $overrides);
            session()->put('workbench.hide_shell.'.$boardKey, $hideShell);
            $preferences->clear($contexts->make($boardKey));

            return redirect()->back();
        }

        $features = [];
        foreach ($booleanFeatures as $flag) {
            $features[$flag] = $request->boolean('features.'.$flag);
        }

        $allowedMode = (string) $request->input('views.allowed_mode', 'both');
        $allowedViews = match ($allowedMode) {
            'board' => ['board'],
            'list' => ['list'],
            default => ['board', 'list'],
        };
        $defaultView = (string) $request->input('views.default', 'board');
        if (! in_array($defaultView, $allowedViews, true)) {
            $defaultView = $allowedViews[0];
        }

        $pageSize = max(1, min(100, (int) $request->input('pagination.page_size', 25)));
        $transitionPolicy = (string) $request->input('interaction.transition_policy', '');
        $confirmation = (string) $request->input('interaction.confirmation', 'none');

        $interaction = [
            'animations' => $request->boolean('interaction.animations', true),
            'reduced_motion' => $request->boolean('interaction.reduced_motion'),
            'keyboard_alternative' => $request->boolean('interaction.keyboard_alternative', true),
            'confirmation' => in_array($confirmation, ['none', 'simple', 'form'], true) ? $confirmation : 'none',
        ];
        if (in_array($transitionPolicy, ['allow', 'deny'], true)) {
            $interaction['transition_policy'] = $transitionPolicy;
            if ($transitionPolicy === 'allow') {
                $interaction['transitions'] = [];
            }
        }

        $overrides = [
            'features' => $features,
            'appearance' => [
                'theme' => (string) $request->input('appearance.theme', 'light'),
                'direction' => (string) $request->input('appearance.direction', 'ltr'),
            ],
            'views' => [
                'default' => $defaultView,
                'allowed' => $allowedViews,
                'column_width' => (string) $request->input('views.column_width', '18.5rem'),
                'density' => (string) $request->input('views.density', 'comfortable'),
                'detail_surface' => (string) $request->input('views.detail_surface', 'modal'),
            ],
            'interaction' => $interaction,
            'pagination' => [
                'page_size' => $pageSize,
                'virtualization' => $features['virtualization'],
            ],
            'realtime' => [
                'enabled' => $features['realtime'],
            ],
        ];

        session()->put('workbench.playground.'.$boardKey, $overrides);
        session()->put('workbench.hide_shell.'.$boardKey, $request->boolean('workbench.hide_shell'));
        $preferences->clear($contexts->make($boardKey));

        return redirect()->back();
    });

    Route::post('/workbench/playground/reset', function (Request $request, ContextFactory $contexts, PreferenceManager $preferences) {
        $boardKey = (string) $request->input('board_key', 'tasks.board');
        session()->forget('workbench.playground.'.$boardKey);
        session()->forget('workbench.hide_shell.'.$boardKey);
        $preferences->clear($contexts->make($boardKey));

        return redirect()->back();
    });

    Route::get('/vendor/kanban/{file}', function (string $file) {
        abort_unless(in_array($file, ['kanban.css', 'kanban.js'], true), 404);

        return response()->file(dirname(__DIR__, 2).'/resources/dist/'.$file, [
            'Content-Type' => str_ends_with($file, '.css') ? 'text/css' : 'application/javascript',
        ]);
    });

    Route::get('/workbench/echo.js', fn () => response()->file(dirname(__DIR__).'/resources/dist/echo.js', ['Content-Type' => 'application/javascript']));

    Route::get('/workbench/workbench.css', fn () => response()->file(dirname(__DIR__).'/resources/css/workbench.css', ['Content-Type' => 'text/css']));

    Route::get('/multi', fn () => view('multi'));
    foreach (['tasks', 'tickets', 'classes', 'mixed', 'compact'] as $source) {
        Route::get('/models/'.$source, fn () => view('board', ['key' => 'models.'.$source]));
    }

    foreach (['crm' => 'crm.deals', 'tickets' => 'tickets.board', 'tasks' => 'tasks.board', 'orders' => 'orders.board'] as $path => $key) {
        Route::get('/'.$path, fn () => view('board', ['key' => $key]));
    }
});
