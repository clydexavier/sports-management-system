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
        //
        Schema::table('players', function (Blueprint $table) {
            $table->boolean('medical_certificate')->nullable()->change();
            $table->boolean('parents_consent')->nullable()->change();
            $table->boolean('cor')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('players', function (Blueprint $table) {
            $table->string('medical_certificate')->nullable()->change();
            $table->string('parents_consent')->nullable()->change();
            $table->string('cor')->nullable()->change();
        });
    }
};