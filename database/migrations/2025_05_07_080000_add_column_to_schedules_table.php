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
        Schema::table('schedules', function (Blueprint $table) {
            //
            $table->string("venue")->nullable()->after('time');
            $table->date('date')->nullable()->change();
            $table->time('time')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            //
            // STEP 1: Drop added column
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn("venue");
        });

        // STEP 2: Fix bad/null data before changing to NOT NULL
        DB::table('schedules')
            ->whereNull('date')
            ->update(['date' => now()->toDateString()]); // Or choose a safer fallback like '2000-01-01'

        DB::table('schedules')
            ->whereNull('time')
            ->update(['time' => now()->format('H:i:s')]); // Or fallback like '00:00:00'

        // STEP 3: Change columns to NOT NULL
        Schema::table('schedules', function (Blueprint $table) {
            $table->date('date')->nullable(false)->change();
            $table->time('time')->nullable(false)->change();
            });
        });
    }
};