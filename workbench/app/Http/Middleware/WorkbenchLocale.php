<?php

declare(strict_types=1);

namespace Workbench\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

final class WorkbenchLocale
{
    public function handle(Request $request, Closure $next): mixed
    {
        $direction = $request->session()->get('workbench.direction');
        if (in_array($direction, ['ltr', 'rtl'], true)) {
            app()->setLocale($direction === 'rtl' ? 'fa' : 'en');
            config(['kanban.appearance.direction' => $direction, 'kanban.dates.calendar' => $direction === 'rtl' ? 'jalali' : 'gregorian', 'kanban.dates.locale' => $direction === 'rtl' ? 'fa_IR' : 'en_US', 'kanban.dates.timezone' => $direction === 'rtl' ? 'Asia/Tehran' : null, 'kanban.dates.persian_digits' => $direction === 'rtl']);
        }
        return $next($request);
    }
}
