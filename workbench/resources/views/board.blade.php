<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $key }}</title>
    <link rel="stylesheet" href="/vendor/kanban/kanban.css">
    @livewireStyles
</head>
<body>
    <p id="whoami">{{ auth()->user()?->name }} / {{ auth()->user()?->tenant_id }}</p>
    <livewire:kanban-board :board-key="$key" />
    <script>
        window.WORKBENCH_REVERB = {
            key: @json(env('REVERB_APP_KEY', 'kanban-key')),
            host: @json(env('REVERB_HOST', '127.0.0.1')),
            port: {{ (int) env('REVERB_PORT', 8081) }}
        };
    </script>
    <script src="/workbench/echo.js"></script>
    @livewireScripts
    <script src="/vendor/kanban/kanban.js"></script>
</body>
</html>
