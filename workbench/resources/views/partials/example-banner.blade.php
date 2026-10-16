@if(!empty($example))
    <aside class="kb-wb-example" aria-label="Example configuration">
        <span class="kb-wb-example-tag">{{ $example['tag'] }}</span>
        <p class="kb-wb-example-text">{{ $example['summary'] }}</p>
        @if(($example['slug'] ?? '') === 'orders')
            <p class="kb-wb-example-hint">کارت ۱ در Review برای tenant فعلی <code>reviewApproved</code> دارد و می‌تواند به Finance برود.</p>
        @endif
        @if(($example['slug'] ?? '') === 'tickets')
            <p class="kb-wb-example-hint">برای Realtime زنده: <code>php vendor/bin/testbench reverb:start</code></p>
        @endif
    </aside>
@endif
