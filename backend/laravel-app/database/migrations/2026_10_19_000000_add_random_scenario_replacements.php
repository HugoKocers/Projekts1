<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scenarios', function (Blueprint $table) {
            $table->foreignId('replacement_for_scenario_id')
                ->nullable()
                ->after('sort_order')
                ->constrained('scenarios')
                ->nullOnDelete();
            $table->decimal('spawn_chance', 10, 8)->nullable()->after('replacement_for_scenario_id');
        });

        Schema::table('scenario_choices', function (Blueprint $table) {
            $table->decimal('reward_chance', 10, 8)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('scenario_choices', function (Blueprint $table) {
            $table->decimal('reward_chance', 4, 3)->nullable()->change();
        });

        Schema::table('scenarios', function (Blueprint $table) {
            $table->dropForeign(['replacement_for_scenario_id']);
            $table->dropColumn(['replacement_for_scenario_id', 'spawn_chance']);
        });
    }
};
