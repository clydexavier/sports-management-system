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
        Schema::create('overall_teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('team_logo_path');
            $table->integer('total_gold');
            $table->integer('total_silver');
            $table->integer('total_bronze');
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
        Schema::dropIfExists('overall_teams');
    }
};
