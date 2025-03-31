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
        Schema::table('intramural_games', function (Blueprint $table) {
            $table->string('location')->after('name');
            $table->enum('status', ['pending', 'in progress', 'completed'])->default('pending')->after('location');
            $table->date('start_date')->nullable()->after('status');
            $table->date('end_date')->nullable()->after('start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('intramural_games', function (Blueprint $table) {
            $table->dropColumn(['location', 'status', 'start_date', 'end_date']);
        });
    }
};