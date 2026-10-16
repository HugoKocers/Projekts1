<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scenarios', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('risk_zone', 16)->default('caution');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description');
            $table->timestamps();
        });

        Schema::create('scenario_choices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scenario_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->text('aftermath');
            $table->foreignId('next_scenario_id')->nullable()->constrained('scenarios')->nullOnDelete();
            $table->foreignId('required_item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->foreignId('reward_item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->decimal('reward_chance', 4, 3)->nullable();
            $table->string('ending_title')->nullable();
            $table->text('ending_text')->nullable();
            $table->string('ending_kind', 16)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('game_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('current_scenario_id')->constrained('scenarios')->restrictOnDelete();
            $table->json('inventory')->default('[]');
            $table->unsignedInteger('elapsed_seconds')->default(0);
            $table->boolean('is_completed')->default(false);
            $table->timestamps();
            $table->index(['user_id', 'is_completed', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_sessions');
        Schema::dropIfExists('scenario_choices');
        Schema::dropIfExists('items');
        Schema::dropIfExists('scenarios');
    }
};
