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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('intrams_id');
            $table->foreign('intrams_id')->references('id')->on('intramural_games');
            $table->string('challonge_event_id')->unique();
            $table->string('tournament_type');
            $table->boolean('hold_third_place_match');
            //$table->foreignId('tournament_manager')->constrained()->onDelete('cascade'); //(user) tournament manager in charge og the event
            $table->string('category'); // men and women
            $table->string('type'); //sports, dance, music
            $table->integer('gold'); //number of gold/s to be won
            $table->integer('silver');//number of silver/s to be won
            $table->integer('bronze'); //number of bronze/s to be won
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
