<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('venue_id');
            $table->foreign('venue_id')->references('id')->on('venues');
            //$table->foreignId('tournament_manager')->constrained()->onDelete('cascade'); //(user) tournament manager in charge og the event
            $table->string('category'); // ?? look in the notes or recording
            $table->integer('golds'); //number of gold/s to be won
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
