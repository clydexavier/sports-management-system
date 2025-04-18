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
        Schema::table('participating_teams', function (Blueprint $table) {
            //
            $table->string('finalized')->after('event_id')->default('no')->comment('yes or no');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('participating_teams', function (Blueprint $table) {
            //
            $table->dropColumn('finalized');

        });
    }
};
