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
            $table->string('birthdate')->nullable()->after('id_number');
            $table->string('course_year')->nullable()->after('id_number');
            $table->string('contact')->nullable()->after('id_number');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            //
            $table->dropColumn('birthdate');
            $table->dropColumn('course_year');
            $table->dropColumn('contact');

        });
    }
};