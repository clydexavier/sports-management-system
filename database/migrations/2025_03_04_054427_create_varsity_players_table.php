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
        Schema::create('varsity_players', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('id_number');
            $table->string('sport');
            $table->unsignedBigInteger('intrams_id');             
            $table->foreign('intrams_id')->references('id')->on('intramural_games');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('varsity_players');
    }
};
