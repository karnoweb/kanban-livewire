@php
    $hideShell = (bool) ($hideShell ?? false);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle ?? 'Kanban' }} · Workbench</title>
    <link rel="stylesheet" href="/vendor/kanban/kanban.css">
    <link rel="stylesheet" href="/workbench/workbench.css">
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
    <script src="/vendor/kanban/kanban.js"></script>
</body>
</html>
