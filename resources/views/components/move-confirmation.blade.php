<form role="dialog" aria-modal="true" aria-label="{{ __('kanban::kanban.confirm') }}" wire:keydown.escape="cancelPending" class="kb-dialog" data-kb-part="confirm-dialog" wire:submit.prevent="confirmPending" data-kb-confirm>
    <p class="kb-dialog-title">{{ $pending['mode'] }}</p>
    @foreach(($pending['fields'] ?? []) as $name => $rules)
        <label class="kb-field">{{ $name }}
            <input type="text" class="input kb-input" name="{{ $name }}" wire:model="pendingValues.{{ $name }}" @if(!empty($rules['required'])) required @endif>
        </label>
        @if(!empty($pending['errors'][$name]))<span class="kb-field-error" role="alert">{{ $pending['errors'][$name] }}</span>@endif
    @endforeach
    <div class="kb-form-actions">
        <button type="button" class="btn-ghost btn kb-btn kb-btn--ghost" wire:click="cancelPending">{{ __('kanban::kanban.cancel') }}</button>
        <button type="submit" class="btn-primary btn kb-btn kb-btn--primary" data-kb-confirm-submit>{{ __('kanban::kanban.confirm') }}</button>
    </div>
</form>
