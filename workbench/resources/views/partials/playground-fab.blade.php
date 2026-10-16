@php
    $boardKey = $key ?? 'tasks.board';
    $context = app(\Karnoweb\Kanban\Support\ContextFactory::class)->make($boardKey);
    $definition = app(\Karnoweb\Kanban\Support\BoardRegistry::class)->resolve($boardKey, $context);
    $s = $definition->settings;
    $f = $s->features;
    $hideShell = (bool) session('workbench.hide_shell.'.$boardKey, false);
    $hasOverrides = session()->has('workbench.playground.'.$boardKey) || $hideShell;
    $allowedViews = $s->views->allowedViews;
    $allowedMode = count($allowedViews) === 2 ? 'both' : ($allowedViews[0] ?? 'board');

    $exportedSettings = [
        'features' => [
            'toolbar' => $f->toolbar,
            'search' => $f->search,
            'filters' => $f->filters,
            'view_switch' => $f->viewSwitch,
            'theme_switch' => $f->themeSwitch,
            'direction_switch' => $f->directionSwitch,
            'density_switch' => $f->densitySwitch,
            'create_card' => $f->createCard,
            'update_card' => $f->updateCard,
            'archive_card' => $f->archiveCard,
            'restore_card' => $f->restoreCard,
            'create_stage' => $f->createStage,
            'update_stage' => $f->updateStage,
            'archive_stage' => $f->archiveStage,
            'drag_drop' => $f->dragDrop,
            'reorder_cards' => $f->reorderCards,
            'reorder_stages' => $f->reorderStages,
            'bulk' => $f->bulk,
            'wip' => $f->wip,
            'details' => $f->details,
            'virtualization' => $f->virtualization,
            'realtime' => $f->realtime,
            'custom_actions' => $f->customActions,
        ],
        'appearance' => [
            'theme' => $s->appearance->theme,
            'direction' => $s->appearance->direction,
        ],
        'views' => [
            'default' => $s->views->defaultView->value,
            'allowed' => $s->views->allowedViews,
            'column_width' => $s->views->columnWidth,
            'density' => $s->views->density,
            'detail_surface' => $s->views->detailSurface,
        ],
        'interaction' => [
            'animations' => $s->interaction->animations,
            'reduced_motion' => $s->interaction->reducedMotion,
            'keyboard_alternative' => $s->interaction->keyboardAlternative,
            'transition_policy' => $s->interaction->transitionPolicy,
            'confirmation' => $s->interaction->confirmation->value,
        ],
        'pagination' => [
            'page_size' => $s->pagination->pageSize,
            'virtualization' => $s->pagination->virtualization,
        ],
    ];
@endphp

<details class="kb-wb-fab" data-kb-part="workbench-playground">
    <summary class="kb-wb-fab-trigger" data-kb-part="workbench-playground-trigger">
        <span class="kb-wb-fab-icon" aria-hidden="true">⚙</span>
        <span>تنظیمات برد (Playground)</span>
        @if($hasOverrides)
            <span class="kb-wb-fab-dot" title="دارای تنظیمات سفارشی">●</span>
        @endif
    </summary>

    <div class="kb-wb-fab-panel" dir="rtl" data-kb-part="workbench-playground-panel">
        <div class="kb-wb-fab-head">
            <div>
                <strong class="kb-wb-fab-title">تنظیمات کامل کامپوننت <code>{{ $boardKey }}</code></strong>
                <p class="kb-wb-fab-sub">هر تغییر بلافاصله اعمال می‌شود؛ می‌توانید با یک کلیک کل باکس فیلتر و سرچ را حذف کنید</p>
            </div>
            @if($hasOverrides)
                <form method="POST" action="/workbench/playground/reset">
                    @csrf
                    <input type="hidden" name="board_key" value="{{ $boardKey }}">
                    <button type="submit" class="kb-wb-fab-reset">بازنشانی پیش‌فرض</button>
                </form>
            @endif
        </div>

        <form method="POST" action="/workbench/playground" class="kb-wb-fab-presets">
            @csrf
            <input type="hidden" name="board_key" value="{{ $boardKey }}">
            <button type="submit" name="preset" value="board_only" class="kb-wb-preset @if(! $f->toolbar && ! $hideShell) is-active @endif">
                فقط بورد (حذف کل باکس سرچ، فیلتر و نوار ابزار)
            </button>
            <button type="submit" name="preset" value="full_bleed" class="kb-wb-preset @if(! $f->toolbar && $hideShell) is-active @endif">
                فقط بورد ۱۰۰٪ صفحه (حذف نوار ابزار + هدر تست)
            </button>
            <button type="submit" name="preset" value="enterprise" class="kb-wb-preset @if($f->toolbar && $f->search && $f->filters && ! $hideShell) is-active @endif">
                حالت کامل سازمانی (همه ابزارها فعال)
            </button>
            <button type="submit" name="preset" value="readonly" class="kb-wb-preset @if(! $f->dragDrop && ! $f->createCard) is-active @endif">
                حالت فقط‌خواندنی (Read-Only)
            </button>
        </form>

        <form method="POST" action="/workbench/playground" class="kb-wb-fab-form" onchange="this.requestSubmit()">
            @csrf
            <input type="hidden" name="board_key" value="{{ $boardKey }}">

            <fieldset class="kb-wb-fab-group">
                <legend>۱. باکس بالای بورد (نوار ابزار، سرچ، فیلتر و سوییچ‌ها)</legend>
                <label class="kb-wb-fab-check kb-wb-fab-check--highlight">
                    <input type="checkbox" name="features[toolbar]" value="1" @checked($f->toolbar)>
                    <span>نمایش کل باکس نوار ابزار بالای بورد (<code>toolbar</code>)</span>
                </label>
                <label class="kb-wb-fab-check kb-wb-fab-check--highlight">
                    <input type="checkbox" name="workbench[hide_shell]" value="1" @checked($hideShell)>
                    <span>مخفی کردن هدر و بنر محیط تست Workbench</span>
                </label>
                <label class="kb-wb-fab-check">
                    <input type="checkbox" name="features[search]" value="1" @checked($f->search)>
                    <span>باکس جست‌وجو (<code>search</code>)</span>
                </label>
                <label class="kb-wb-fab-check">
                    <input type="checkbox" name="features[filters]" value="1" @checked($f->filters)>
                    <span>باکس فیلتر اولویت و مرتب‌سازی (<code>filters</code>)</span>
                </label>
                <label class="kb-wb-fab-check">
                    <input type="checkbox" name="features[view_switch]" value="1" @checked($f->viewSwitch)>
                    <span>سوییچ Board / List (<code>view_switch</code>)</span>
                </label>
                <label class="kb-wb-fab-check">
                    <input type="checkbox" name="features[theme_switch]" value="1" @checked($f->themeSwitch)>
                    <span>سوییچ تم Light/Dark (<code>theme_switch</code>)</span>
                </label>
                <label class="kb-wb-fab-check">
                    <input type="checkbox" name="features[direction_switch]" value="1" @checked($f->directionSwitch)>
                    <span>سوییچ جهت LTR/RTL (<code>direction_switch</code>)</span>
                </label>
                <label class="kb-wb-fab-check">
                    <input type="checkbox" name="features[density_switch]" value="1" @checked($f->densitySwitch)>
                    <span>سوییچ تراکم (<code>density_switch</code>)</span>
                </label>
            </fieldset>

            <fieldset class="kb-wb-fab-group">
                <legend>۲. عملیات کارت و ستون (Card & Stage CRUD)</legend>
                <label class="kb-wb-fab-check">
                    <input type="checkbox" name="features[create_card]" value="1" @checked($f->createCard)>
                    <span>ساخت کارت جدید (<code>create_card</code>)</span>
                </label>
                <label class="kb-wb-fab-check">
                    <input type="checkbox" name="features[update_card]" value="1" @checked($f->updateCard)>
                    <span>ویرایش کارت (<code>update_card</code>)</span>
                </label>
                <label class="kb-wb-fab-check">
                    <input type="checkbox" name="features[archive_card]" value="1" @checked($f->archiveCard)>
                    <span>آرشیو کارت (<code>archive_card</code>)</span>
                </label>
                <label class="kb-wb-fab-check">
                    <input type="checkbox" name="features[restore_card]" value="1" @checked($f->restoreCard)>
                    <span>بازیابی از آرشیو (<code>restore_card</code>)</span>
                </label>
                <label class="kb-wb-fab-check">
                    <input type="checkbox" name="features[create_stage]" value="1" @checked($f->createStage)>
                    <span>ساخت ستون جدید (<code>create_stage</code>)</span>
                </label>
                <label class="kb-wb-fab-check">
                    <input type="checkbox" name="features[update_stage]" value="1" @checked($f->updateStage)>
                    <span>ویرایش ستون (<code>update_stage</code>)</span>
                </label>
                <label class="kb-wb-fab-check">
                    <input type="checkbox" name="features[archive_stage]" value="1" @checked($f->archiveStage)>
                    <span>آرشیو ستون (<code>archive_stage</code>)</span>
                </label>
                <label class="kb-wb-fab-check">
                    <input type="checkbox" name="features[details]" value="1" @checked($f->details)>
                    <span>جزئیات کارت (<code>details</code>)</span>
                </label>
                <label class="kb-wb-fab-check">
                    <input type="checkbox" name="features[custom_actions]" value="1" @checked($f->customActions)>
                    <span>اکشن‌های سفارشی منو (<code>custom_actions</code>)</span>
                </label>
            </fieldset>

            <fieldset class="kb-wb-fab-group">
                <legend>۳. جابه‌جایی، قوانین، انیمیشن و Realtime</legend>
                <label class="kb-wb-fab-check">
                    <input type="checkbox" name="features[drag_drop]" value="1" @checked($f->dragDrop)>
                    <span>Drag & Drop کارت‌ها (<code>drag_drop</code>)</span>
                </label>
                <label class="kb-wb-fab-check">
                    <input type="checkbox" name="features[reorder_cards]" value="1" @checked($f->reorderCards)>
                    <span>مرتب‌سازی داخل ستون (<code>reorder_cards</code>)</span>
                </label>
                <label class="kb-wb-fab-check">
                    <input type="checkbox" name="features[reorder_stages]" value="1" @checked($f->reorderStages)>
                    <span>جابه‌جایی ستون‌ها (<code>reorder_stages</code>)</span>
                </label>
                <label class="kb-wb-fab-check">
                    <input type="checkbox" name="features[wip]" value="1" @checked($f->wip)>
                    <span>محدودیت ظرفیت ستون (<code>wip</code>)</span>
                </label>
                <label class="kb-wb-fab-check">
                    <input type="checkbox" name="features[bulk]" value="1" @checked($f->bulk)>
                    <span>انتخاب چندتایی / گروهی (<code>bulk</code>)</span>
                </label>
                <label class="kb-wb-fab-check">
                    <input type="checkbox" name="features[virtualization]" value="1" @checked($f->virtualization)>
                    <span>مجازی‌سازی اسکرول (<code>virtualization</code>)</span>
                </label>
                <label class="kb-wb-fab-check">
                    <input type="checkbox" name="features[realtime]" value="1" @checked($f->realtime)>
                    <span>به‌روزرسانی زنده (<code>realtime</code>)</span>
                </label>
                <label class="kb-wb-fab-check">
                    <input type="checkbox" name="interaction[animations]" value="1" @checked($s->interaction->animations)>
                    <span>انیمیشن‌های جابه‌جایی (<code>animations</code>)</span>
                </label>
                <label class="kb-wb-fab-check">
                    <input type="checkbox" name="interaction[reduced_motion]" value="1" @checked($s->interaction->reducedMotion)>
                    <span>حالت بدون حرکت (<code>reduced_motion</code>)</span>
                </label>
                <label class="kb-wb-fab-check">
                    <input type="checkbox" name="interaction[keyboard_alternative]" value="1" @checked($s->interaction->keyboardAlternative)>
                    <span>جابه‌جایی با کیبورد (<code>keyboard_alternative</code>)</span>
                </label>
            </fieldset>

            <fieldset class="kb-wb-fab-group kb-wb-fab-selects">
                <legend>۴. ظاهر، ابعاد، سیاست انتقال و صفحه‌بندی</legend>
                <label class="kb-wb-fab-field">
                    <span>تم پیش‌فرض (<code>theme</code>)</span>
                    <select name="appearance[theme]">
                        <option value="light" @selected($s->appearance->theme === 'light')>Light</option>
                        <option value="dark" @selected($s->appearance->theme === 'dark')>Dark</option>
                        <option value="system" @selected($s->appearance->theme === 'system')>System</option>
                    </select>
                </label>
                <label class="kb-wb-fab-field">
                    <span>جهت پیش‌فرض (<code>direction</code>)</span>
                    <select name="appearance[direction]">
                        <option value="ltr" @selected($s->appearance->direction === 'ltr')>LTR</option>
                        <option value="rtl" @selected($s->appearance->direction === 'rtl')>RTL</option>
                    </select>
                </label>
                <label class="kb-wb-fab-field">
                    <span>تراکم (<code>density</code>)</span>
                    <select name="views[density]">
                        <option value="comfortable" @selected($s->views->density === 'comfortable')>Comfortable</option>
                        <option value="compact" @selected($s->views->density === 'compact')>Compact</option>
                    </select>
                </label>
                <label class="kb-wb-fab-field">
                    <span>عرض ستون (<code>column_width</code>)</span>
                    <select name="views[column_width]">
                        @foreach(['16rem' => 'فشرده (16rem)', '18.5rem' => 'استاندارد (18.5rem)', '21rem' => 'عریض (21rem)', '24rem' => 'خیلی عریض (24rem)'] as $w => $label)
                            <option value="{{ $w }}" @selected($s->views->columnWidth === $w)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="kb-wb-fab-field">
                    <span>نمایش جزئیات (<code>detail_surface</code>)</span>
                    <select name="views[detail_surface]">
                        <option value="modal" @selected($s->views->detailSurface === 'modal')>Modal (پنجره وسط)</option>
                        <option value="drawer" @selected($s->views->detailSurface === 'drawer')>Drawer (کشویی)</option>
                    </select>
                </label>
                <label class="kb-wb-fab-field">
                    <span>نماهای مجاز (<code>allowed</code>)</span>
                    <select name="views[allowed_mode]">
                        <option value="both" @selected($allowedMode === 'both')>Board + List</option>
                        <option value="board" @selected($allowedMode === 'board')>فقط Board</option>
                        <option value="list" @selected($allowedMode === 'list')>فقط List</option>
                    </select>
                </label>
                <label class="kb-wb-fab-field">
                    <span>نمای اولیه (<code>default</code>)</span>
                    <select name="views[default]">
                        <option value="board" @selected($s->views->defaultView->value === 'board')>Board</option>
                        <option value="list" @selected($s->views->defaultView->value === 'list')>List</option>
                    </select>
                </label>
                <label class="kb-wb-fab-field">
                    <span>سیاست انتقال (<code>transition_policy</code>)</span>
                    <select name="interaction[transition_policy]">
                        <option value="allow" @selected($s->interaction->transitionPolicy === 'allow')>Allow (انتقال آزاد بین همه ستون‌ها)</option>
                        <option value="deny" @selected($s->interaction->transitionPolicy === 'deny')>Deny (فقط مسیرهای مجاز تعریف‌شده)</option>
                    </select>
                </label>
                <label class="kb-wb-fab-field">
                    <span>تأیید قبل از انتقال (<code>confirmation</code>)</span>
                    <select name="interaction[confirmation]">
                        <option value="none" @selected($s->interaction->confirmation->value === 'none')>None (بدون تأیید)</option>
                        <option value="simple" @selected($s->interaction->confirmation->value === 'simple')>Simple (تأیید ساده)</option>
                        <option value="form" @selected($s->interaction->confirmation->value === 'form')>Form (فرم یادداشت)</option>
                    </select>
                </label>
                <label class="kb-wb-fab-field">
                    <span>تعداد کارت هر صفحه (<code>page_size</code>)</span>
                    <select name="pagination[page_size]">
                        @foreach([2, 5, 10, 25, 50] as $sz)
                            <option value="{{ $sz }}" @selected($s->pagination->pageSize === $sz)>{{ $sz }} کارت در هر ستون</option>
                        @endforeach
                    </select>
                </label>
            </fieldset>
        </form>

        <details class="kb-wb-fab-code" dir="ltr">
            <summary>PHP Settings Config Snippet</summary>
            <pre><code>->settings({{ json_encode($exportedSettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }})</code></pre>
        </details>
    </div>
</details>
