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
        Schema::table('schedules', function (Blueprint $table) {
            $table->text('scores_csv')->nullable(); // Store raw scores_csv for set-based games
            $table->integer('score_team1')->nullable(); // Optional total score
            $table->integer('score_team2')->nullable(); // Optional total score
            $table->string('winner_id')->nullable();
            $table->boolean('is_completed')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn(['scores_csv', 'score_team1', 'score_team2', 'winner_id', 'is_completed']);
        });
    }
};