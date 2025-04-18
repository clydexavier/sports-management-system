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
            $table->dropForeign(['GAM_id']); // foreign key must be dropped first

            $table->dropColumn('GAM_id');


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('participating_teams', function (Blueprint $table) {
            //
            $table->string('GAM_id')->after('id')->nullable()->comment('GAM id');
        });
    }
};
