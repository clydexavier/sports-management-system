<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Update events table
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['intrams_id']);
            $table->foreign('intrams_id')
                  ->references('id')
                  ->on('intramural_games')
                  ->onDelete('cascade');
        });
        
        // Update overall_teams table
        Schema::table('overall_teams', function (Blueprint $table) {
            $table->dropForeign(['intrams_id']);
            $table->foreign('intrams_id')
                  ->references('id')
                  ->on('intramural_games')
                  ->onDelete('cascade');
        });
        
        // Add any other tables that reference intramural_games here
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // Restore events table foreign key
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['intrams_id']);
            $table->foreign('intrams_id')
                  ->references('id')
                  ->on('intramural_games');
        });
        
        // Restore overall_teams table foreign key
        Schema::table('overall_teams', function (Blueprint $table) {
            $table->dropForeign(['intrams_id']);
            $table->foreign('intrams_id')
                  ->references('id')
                  ->on('intramural_games');
        });
        
        // Add any other tables that were modified
    }
};