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
        Schema::table('players', function (Blueprint $table) {
            $table->dropForeign(['participant_id']);
            $table->dropColumn('participant_id');
            
            $table->foreignId('event_id')->nullable()->constrained('events')->onDelete('cascade');
            $table->foreignId('team_id')->nullable()->constrained('overall_teams')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            // First, drop the foreign key constraint for 'team_id'
            $table->dropForeign(['team_id']);

            // Then, drop the 'team_id' column
            $table->dropColumn('team_id');

           $table->dropForeign(['event_id']);
            $table->dropColumn('event_id');

            // Finally, add back the 'participant_id' column
            $table->foreignId('participant_id')->nullable()->constrained('participating_teams')->onDelete('cascade');
        });
    }
};