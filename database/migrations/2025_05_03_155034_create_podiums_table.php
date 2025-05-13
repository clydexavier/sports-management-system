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
        Schema::create('podiums', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intrams_id')->nullable()->constrained('intramural_games')->onDelete('cascade');
            $table->foreignId('event_id')
                ->unique() // add this
                ->nullable()
                ->constrained('events')
                ->onDelete('cascade');
        
            $table->foreignId('gold_team_id')->nullable()->constrained('overall_teams')->nullOnDelete();
            $table->foreignId('silver_team_id')->nullable()->constrained('overall_teams')->nullOnDelete();
            $table->foreignId('bronze_team_id')->nullable()->constrained('overall_teams')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('podiums');
    }
};