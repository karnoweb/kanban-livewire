@php
    $hideShell = (bool) ($hideShell ?? false);
    $packageRoot = dirname((new \ReflectionClass(\Karnoweb\Kanban\KanbanServiceProvider::class))->getFileName(), 2);
    $assetVersion = max(filemtime($packageRoot.'/resources/dist/kanban.css'), filemtime($packageRoot.'/resources/dist/kanban.js'), filemtime($packageRoot.'/workbench/resources/css/workbench.css'));
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ session('workbench.direction', 'ltr') }}" data-theme="{{ session('workbench.theme', 'light') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle ?? 'Kanban' }} · Workbench</title>
    <link rel="stylesheet" href="/vendor/kanban/kanban.css?v={{ $assetVersion }}">
    <link rel="stylesheet" href="/workbench/workbench.css?v={{ $assetVersion }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;family=Vazirmatn:wght@400;500;600;700&amp;display=swap" rel="stylesheet">
    @livewireStyles
</head>
<body @class(['kb-wb', 'kb-wb--shell-hidden' => $hideShell])>
    @unless($hideShell)
        <header class="kb-wb-top">
            <div class="kb-wb-top-start">
                <a class="kb-wb-brand" href="/tasks" title="Workbench home">Kanban</a>
                <nav class="kb-wb-boards" aria-label="Demo boards">
                    @foreach(['tasks' => 'Tasks', 'crm' => 'CRM', 'tickets' => 'Tickets', 'orders' => 'Orders'] as $path => $label)
                        <a href="/{{ $path }}" @class(['kb-wb-board-link', 'is-active' => ($activeBoard ?? '') === $path])>{{ $label }}</a>
                    @endforeach
                    <a href="/multi" @class(['kb-wb-board-link', 'is-active' => ($activeBoard ?? '') === 'multi'])>Multi</a>
                </nav>
            </div>
            <div class="kb-wb-top-end">
                <form method="POST" action="/workbench/theme" class="kb-wb-theme">
                    @csrf
                    <select name="theme" aria-label="{{ __('kanban::kanban.theme') }}">
                        @foreach(['light' => 'theme_light', 'dark' => 'theme_dark', 'system' => 'theme_system'] as $value => $label)
                            <option value="{{ $value }}" @selected(session('workbench.theme', 'light') === $value)>{{ __('kanban::kanban.'.$label) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" aria-label="{{ __('kanban::kanban.apply_theme') }}" title="{{ __('kanban::kanban.apply_theme') }}"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg></button>
                </form>
                <form method="POST" action="/workbench/locale" class="kb-wb-locale">
                    @csrf
                    <select name="direction" aria-label="{{ __('kanban::kanban.direction') }}" onchange="this.form.requestSubmit()">
                        <option value="ltr" @selected(session('workbench.direction', 'ltr') === 'ltr')>English · LTR</option>
                        <option value="rtl" @selected(session('workbench.direction', 'ltr') === 'rtl')>فارسی · RTL</option>
                    </select>
                    <noscript><button type="submit">{{ __('kanban::kanban.save') }}</button></noscript>
                </form>
                @auth
                    <span class="kb-wb-user">{{ auth()->user()->name }} · {{ auth()->user()->tenant_id }}</span>
                    <div class="kb-wb-switch-user" role="group" aria-label="Switch demo user">
                        @foreach(['alice', 'bob', 'carol'] as $name)
                            <a href="/login-as/{{ $name }}" @class(['kb-wb-user-link', 'is-active' => auth()->user()->name === $name])>{{ ucfirst($name) }}</a>
                        @endforeach
                    </div>
                @else
                    <a class="kb-wb-user-link" href="/login-as/alice">Sign in as Alice</a>
                @endauth
            </div>
        </header>
    @endunless
    <main class="kb-wb-board-area">
        @unless($hideShell)
            @hasSection('board_head')
                @yield('board_head')
            @elseif(!empty($boardTitle))
                <div class="kb-wb-board-head">
                    <h1 class="kb-wb-board-title">{{ $boardTitle }}</h1>
                </div>
            @endif
        @endunless
        @yield('content')
    </main>
    <script>
        window.WORKBENCH_REVERB = {
            key: @json(env('REVERB_APP_KEY', 'kanban-key')),
            host: @json(env('REVERB_HOST', '127.0.0.1')),
            port: {{ (int) config('broadcasting.connections.reverb.options.port', env('REVERB_PORT', 8081)) }}
        };
    </script>
    <script src="/workbench/echo.js"></script>
    @livewireScripts
    <script src="/vendor/kanban/kanban.js?v={{ $assetVersion }}"></script>
</body>
</html>
