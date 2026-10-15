<?php

declare(strict_types=1);

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;

class DemoCard extends Model
{
    protected $table = 'demo_cards';

    protected $guarded = [];

    protected $casts = [
        'archived' => 'boolean',
        'metadata' => 'array',
        'manual_order' => 'float',
    ];
}
