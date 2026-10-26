<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scenarios', function (Blueprint $table) {
            $table->string('title_en')->nullable()->after('title');
            $table->text('description_en')->nullable()->after('description');
        });

        Schema::table('scenario_choices', function (Blueprint $table) {
            $table->string('label_en')->nullable()->after('label');
            $table->text('aftermath_en')->nullable()->after('aftermath');
            $table->string('ending_title_en')->nullable()->after('ending_title');
            $table->text('ending_text_en')->nullable()->after('ending_text');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->string('name_en')->nullable()->after('name');
            $table->text('description_en')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('scenario_choices', function (Blueprint $table) {
            $table->dropColumn(['label_en', 'aftermath_en', 'ending_title_en', 'ending_text_en']);
        });

        Schema::table('scenarios', function (Blueprint $table) {
            $table->dropColumn(['title_en', 'description_en']);
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['name_en', 'description_en']);
        });
    }
};
