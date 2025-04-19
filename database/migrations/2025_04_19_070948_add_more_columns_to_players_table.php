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
            //
            if (Schema::hasColumn('players', 'team_id')) {
                $table->dropForeign(['team_id']);
                $table->dropColumn('team_id');
            }

            // Add new column and foreign key
            $table->foreignId('participant_id')->nullable()->constrained('participating_teams')->onDelete('cascade')->after('id_number');
            $table->string('medical_certificate')->nullable()->after('participant_id');
            $table->string('parents_consent')->nullable()->after('medical_certificate');
            $table->string('cor')->nullable()->after('parents_consent');
            $table->boolean('approved')->default(false)->after('cor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            //
            $table->dropForeign(['participant_id']);
            $table->dropColumn('participant_id');

            $table->foreignId('team_id')->nullable()->constrained('overall_teams')->onDelete('set null');
            $table->dropColumn('medical_certificate');
            $table->dropColumn('parents_consent');
            $table->dropColumn('cor');
            $table->dropColumn('approved');

        });
    }
};
