@php
    $examples = config('workbench.examples', []);
    $example = $examples[$key] ?? null;
    $active = $example['slug'] ?? 'tasks';
    $boardTitle = $example['title'] ?? $key;
    $hideShell = (bool) session('workbench.hide_shell.'.$key, false);
@endphp

@extends('layout', ['activeBoard' => $active, 'boardTitle' => $boardTitle, 'pageTitle' => $boardTitle, 'example' => $example, 'hideShell' => $hideShell])

@section('board_head')
    <div class="kb-wb-board-head">
        <h1 class="kb-wb-board-title">{{ $boardTitle }}</h1>
        @include('partials.example-banner', ['example' => $example])
    </div>
@endsection

@section('content')
    <livewire:kanban-board :board-key="$key" />
    @include('partials.playground-fab', ['key' => $key])
@endsection
