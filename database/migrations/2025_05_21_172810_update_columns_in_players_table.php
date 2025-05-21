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
            // Drop file storage columns
            $table->dropColumn(['medical_certificate', 'parents_consent', 'cor']);
            
            // Drop the old approved boolean column
            $table->dropColumn('approved');
            
            // Add new status columns
            $table->string('medical_certificate_status')->default('pending')->after('picture');
            $table->string('parents_consent_status')->default('pending')->after('medical_certificate_status');
            $table->string('cor_status')->default('pending')->after('parents_consent_status');
            $table->string('approval_status')->default('pending')->after('cor_status');
            $table->text('rejection_reason')->nullable()->after('approval_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            // Remove the new columns
            $table->dropColumn([
                'medical_certificate_status', 
                'parents_consent_status', 
                'cor_status', 
                'approval_status',
                'rejection_reason'
            ]);
            
            // Add back the original columns
            $table->string('medical_certificate')->nullable()->after('picture');
            $table->string('parents_consent')->nullable()->after('medical_certificate');
            $table->string('cor')->nullable()->after('parents_consent');
            $table->boolean('approved')->default(false)->after('cor');
        });
    }
};