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
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_url')->nullable();
            $table->foreignId('intrams_id')->nullable()->constrained('intramural_games')->onDelete('set null');
            $table->foreignId('event_id')->nullable()->constrained('events')->onDelete('set null');
            $table->foreignId('team_id')->nullable()->constrained('overall_teams')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('avatar_url');

            $table->dropForeign(['intrams_id']);
            $table->dropColumn('intrams_id');

            $table->dropForeign(['event_id']);
            $table->dropColumn('event_id');

            $table->dropForeign(['team_id']);
            $table->dropColumn('team_id');
        });
    }
};