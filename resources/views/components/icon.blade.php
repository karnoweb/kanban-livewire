<svg class="kb-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@switch($icon)
    @case('status')<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>@break
    @case('priority')<path d="M5 21V3m0 1h12l-3 5 3 5H5"/>@break
    @case('assignee')<circle cx="12" cy="7" r="3"/><path d="M5 21v-3a7 7 0 0 1 14 0v3"/>@break
    @case('badge')<path d="m3 12 9 9 9-9L12 3H3Z"/><circle cx="7" cy="7" r="1"/>@break
    @case('board')<rect x="3" y="4" width="7" height="16" rx="1"/><rect x="14" y="4" width="7" height="10" rx="1"/>@break
    @case('list')<path d="M9 5h12M9 12h12M9 19h12"/><rect x="3" y="3" width="2" height="4"/><rect x="3" y="10" width="2" height="4"/><rect x="3" y="17" width="2" height="4"/>@break

    @case('calendar')<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18M8 15h.01M12 15h.01M16 15h.01M8 18h.01M12 18h.01"/>@break
    @case('checklist')<rect x="4" y="4" width="16" height="16" rx="2"/><path d="m8 11 3 3 6-7"/>@break
    @case('comment')<path d="M21 11.5a8.5 8.5 0 0 1-8.5 8.5 10 10 0 0 1-4-.8L3 21l1.8-5.5a8.5 8.5 0 1 1 16.2-4Z"/>@break
    @case('attachment')<path d="m9 12 6-6a3 3 0 0 1 4 4L9 20a5 5 0 0 1-7-7L13 2M6 15l9-9"/>@break
@endswitch
</svg>
