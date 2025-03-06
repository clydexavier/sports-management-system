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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('file_path'); // Relative path for Laravel's local storage
            $table->string('mime_type')->nullable(); // Store file type (PDF, DOCX, etc.)
            $table->integer('size')->nullable(); // Store file size in KB
            $table->foreignId('intrams_id')->constrained('intramural_games')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
