<form role="dialog" aria-modal="true" aria-label="{{ __('kanban::kanban.confirm') }}" wire:keydown.escape="cancelPending" class="kb-dialog" data-kb-part="confirm-dialog" wire:submit.prevent="confirmPending" data-kb-confirm>
    <header class="kb-modal-header"><h2 class="kb-dialog-title">{{ __('kanban::kanban.confirm_move') }}</h2>@include('kanban::components.close-button', ['closeAction' => 'cancelPending'])</header>
    @foreach(($pending['fields'] ?? []) as $name => $rules)
        <label class="kb-field">{{ \Illuminate\Support\Facades\Lang::has('kanban::kanban.'.$name) ? __('kanban::kanban.'.$name) : $name }}
            @if($name === 'note' || ($rules['type'] ?? null) === 'textarea')
                <textarea class="textarea kb-input" name="{{ $name }}" wire:model="pendingValues.{{ $name }}" rows="4" maxlength="5000" data-kb-autofocus @if(!empty($rules['required'])) required @endif></textarea>
            @else
            <input type="text" class="input kb-input" name="{{ $name }}" wire:model="pendingValues.{{ $name }}" @if(!empty($rules['required'])) required @endif>
            @endif
        </label>
        @if(!empty($pending['errors'][$name]))<span class="kb-field-error" role="alert">{{ $pending['errors'][$name] }}</span>@endif
    @endforeach
    <div class="kb-form-actions">
        <button type="button" class="btn-ghost btn kb-btn kb-btn--ghost" wire:click="cancelPending">{{ __('kanban::kanban.cancel') }}</button>
        <button type="submit" class="btn-primary btn kb-btn kb-btn--primary" data-kb-confirm-submit>{{ __('kanban::kanban.confirm') }}</button>
    </div>
</form>
