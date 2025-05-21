<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'id_number',
        'birthdate',
        'course_year',
        'contact',
        'is_varsity',
        'sport',
        'intrams_id',
        'team_id',
        'event_id',
        'picture',
        'medical_certificate_status',
        'parents_consent_status',
        'cor_status',
        'approval_status', // Changed from 'approved' boolean to status string
        'rejection_reason', // New field to store rejection reason
        'role',
        'picture_public_id',
    ];


    protected $casts = [
        'name' => 'string',
        'id_number' => 'string',
        'is_varsity' => 'boolean',
        'sport' => 'string',
        'medical_certificate_status' => 'string',
        'parents_consent_status' => 'string',
        'cor_status' => 'string',
        'approval_status' => 'string',
    ];
    
    public function intramural_game() {
        return $this->belongsTo(IntramuralGame::class, 'intrams_id');
    }

    public function overall_team() {
        return $this->belongsTo(OverallTeam::class, 'team_id');
    }

    public function isVarsity() {
        return $this->is_varsity;
    }
    
    // Helper methods for status
    public function isApproved() {
        return $this->approval_status === 'approved';
    }
    
    public function isPending() {
        return $this->approval_status === 'pending';
    }
    
    public function isRejected() {
        return $this->approval_status === 'rejected';
    }
    
    // Helper methods for document status
    public function hasValidDocuments() {
        return $this->medical_certificate_status === 'valid' &&
               $this->parents_consent_status === 'valid' &&
               $this->cor_status === 'valid';
    }
    
    public function getInvalidDocuments() {
        $invalid = [];
        
        if ($this->medical_certificate_status !== 'valid') {
            $invalid[] = 'Medical Certificate';
        }
        
        if ($this->parents_consent_status !== 'valid') {
            $invalid[] = 'Parents Consent';
        }
        
        if ($this->cor_status !== 'valid') {
            $invalid[] = 'Certificate of Registration';
        }
        
        return $invalid;
    }
}