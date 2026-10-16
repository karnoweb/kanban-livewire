@php
    $status = $notice['status'] ?? null;
    $code = (string) ($notice['messageCode'] ?? '');
    $show = is_string($status) && ! in_array($status, ['success', 'conflict'], true);
    $key = 'kanban::kanban.notice_'.$code;
    $translated = \Illuminate\Support\Facades\Lang::has($key)
        ? __($key)
        : ($notice['safeMessage'] ?? $code);
@endphp
@if($show && $translated !== '')
    <div class="kb-notice kb-notice--{{ $status }}" role="status" data-kb-part="notice" data-status="{{ $status }}" data-code="{{ $code }}">
        <span class="kb-notice-text" data-kb-part="notice-text">{{ $translated }}</span>
        <button type="button" class="kb-notice-dismiss" data-kb-part="notice-dismiss" wire:click="dismissNotice" aria-label="{{ __('kanban::kanban.close') }}">×</button>
    </div>
@endif
