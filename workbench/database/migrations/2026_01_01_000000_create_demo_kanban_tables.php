<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('tenant_id');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('demo_stages', function (Blueprint $table): void {
            $table->id();
            $table->string('board_key');
            $table->string('scope_key');
            $table->string('public_id');
            $table->string('title');
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('color')->nullable();
            $table->unsignedInteger('wip_limit')->nullable();
            $table->string('wip_mode')->default('off');
            $table->boolean('archived')->default(false);
            $table->timestamps();
            $table->unique(['scope_key', 'board_key', 'public_id']);
        });

        Schema::create('demo_cards', function (Blueprint $table): void {
            $table->id();
            $table->string('board_key');
            $table->string('scope_key');
            $table->string('public_id');
            $table->string('stage_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('priority')->nullable();
            $table->string('due_at')->nullable();
            $table->json('metadata')->nullable();
            $table->string('version')->default('1');
            $table->double('manual_order')->default(0);
            $table->boolean('archived')->default(false);
            $table->timestamps();
            $table->unique(['scope_key', 'board_key', 'public_id']);
            $table->index(['scope_key', 'board_key', 'stage_id', 'archived', 'manual_order'], 'demo_cards_stage_order');
        });

        Schema::create('demo_operations', function (Blueprint $table): void {
            $table->id();
            $table->string('scope_key');
            $table->string('operation_id');
            $table->string('status');
            $table->json('result')->nullable();
            $table->timestamps();
            $table->unique(['scope_key', 'operation_id']);
        });
    }
};
