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
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('is_umbrella')->default(false);
            $table->foreignId('parent_id')->nullable()->constrained('events')->onDelete('cascade');
            $table->boolean('has_independent_medaling')->default(true)->nullable();
            $table->string('challonge_event_id')->nullable()->change();

            // Add venue field if it doesn't already exist
            if (!Schema::hasColumn('events', 'venue')) {
                $table->string('venue')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['is_umbrella', 'parent_id', 'has_independent_medaling', 'challonge_event_id']);

            // Only drop venue if it exists
            if (Schema::hasColumn('events', 'venue')) {
                $table->dropColumn('venue');
            }
            $table->string('challonge_event_id')->nullable(false)->change();

        });
    }
};