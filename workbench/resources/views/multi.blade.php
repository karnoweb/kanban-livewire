@extends('layout', ['activeBoard' => 'multi', 'pageTitle' => 'Multi-board', 'boardTitle' => null])

@section('content')
    <div class="kb-wb-multi">
        <section>
            <h2 class="kb-wb-board-title">Tasks</h2>
            <livewire:kanban-board board-key="tasks.board" />
        </section>
        <section>
            <h2 class="kb-wb-board-title">Orders</h2>
            <livewire:kanban-board board-key="orders.board" />
        </section>
    </div>
@endsection
