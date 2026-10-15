<form class="kb-dialog" role="dialog" aria-modal="true" wire:submit.prevent="confirmPending" data-kb-confirm>
    <p>{{ $pending['mode'] }}</p>
    @foreach(($pending['fields'] ?? []) as $name => $rules)
        <label>{{ $name }}
            <input type="text" name="{{ $name }}" wire:model="pendingValues.{{ $name }}" @if(!empty($rules['required'])) required @endif>
        </label>
        @if(!empty($pending['errors'][$name]))<span role="alert">{{ $pending['errors'][$name] }}</span>@endif
    @endforeach
    <button type="button" wire:click="cancelPending">{{ __('kanban::kanban.cancel') }}</button>
    <button type="submit" data-kb-confirm-submit>{{ __('kanban::kanban.confirm') }}</button>
</form>
