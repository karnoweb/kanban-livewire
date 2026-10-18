<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Workbench;

final class HostLocaleTest extends WorkbenchTestCase
{
    public function test_header_locale_applies_direction_language_and_calendar_across_boards(): void
    {
        $this->loginAs('alice');
        $this->from('/tasks')->post('/workbench/locale', ['direction' => 'rtl'])->assertRedirect('/tasks')->assertSessionHas('workbench.direction', 'rtl');
        $this->get('/tasks')->assertOk()->assertSee('lang="fa" dir="rtl"', false)->assertSee('۱۴۰۵/۰۸/۲۳')->assertSee('ایجاد کارت');
        $this->get('/crm')->assertOk()->assertSee('dir="rtl"', false)->assertSee('۱۴۰۵/۰۸/۲۳');
        $this->get('/models/tasks')->assertOk()->assertSee('lang="fa" dir="rtl"', false);
        $this->from('/tasks')->post('/workbench/locale', ['direction' => 'ltr'])->assertRedirect('/tasks');
        $this->get('/tasks')->assertOk()->assertSee('lang="en" dir="ltr"', false)->assertSee('2026-11-14')->assertSee('Create card');
        $this->get('/models/tasks')->assertOk()->assertSee('dir="ltr"', false);
        $this->post('/workbench/locale', ['direction' => 'invalid'])->assertSessionHasErrors('direction');
    }
}
