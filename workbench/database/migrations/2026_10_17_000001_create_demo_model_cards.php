<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_model_cards', function (Blueprint $table): void {
            $table->id(); $table->string('scope_key'); $table->string('source');
            $table->string('title')->nullable(); $table->string('subject')->nullable(); $table->string('name')->nullable();
            $table->string('status')->nullable(); $table->string('state')->nullable(); $table->string('approval_state')->nullable();
            $table->text('description')->nullable(); $table->string('priority')->nullable(); $table->string('team')->nullable();
            $table->bigInteger('sort_rank')->default(1024); $table->integer('version')->default(1);
            $table->integer('amount')->default(0); $table->timestamp('due_at')->nullable(); $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->index(['scope_key', 'source', 'status', 'sort_rank', 'id'], 'demo_model_task_page');
            $table->index(['scope_key', 'source', 'state', 'sort_rank', 'id'], 'demo_model_ticket_page');
            $table->index(['scope_key', 'source', 'approval_state', 'sort_rank', 'id'], 'demo_model_class_page');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_model_cards');
    }
};
