<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('overall_teams', function (Blueprint $table) {
            //
            $table->integer('total_gold')->default(0)->change();
            $table->integer('total_silver')->default(0)->change();
            $table->integer('total_bronze')->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('overall_teams', function (Blueprint $table) {
            //
            $table->integer('total_gold')->nullable()->change();
            $table->integer('total_silver')->nullable()->change();
            $table->integer('total_bronze')->nullable()->change();
        });
    }
};
