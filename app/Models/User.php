<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'cognito_sub',
        'name',
        'email',
        'phone',
        'profile_picture',
        'role',
        'linkedin_url',
        'github_url',
        'bio',
        'is_verified',
        'is_active',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_verified' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function candidateProfile()
    {
        return $this->hasOne(CandidateProfile::class);
    }

    public function interviewerProfile()
    {
        return $this->hasOne(InterviewerProfile::class);
    }

    public function bookingsAsCandidate()
    {
        return $this->hasMany(Booking::class, 'candidate_id');
    }

    public function bookingsAsInterviewer()
    {
        return $this->hasMany(Booking::class, 'interviewer_id');
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class, 'candidate_id');
    }

    public function evaluationReports()
    {
        return $this->hasMany(EvaluationReport::class, 'candidate_id');
    }

    public function notifications()
    {
        return $this->hasMany(AppNotification::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'reviewee_id');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function isCandidate(): bool
    {
        return $this->role === 'candidate';
    }

    public function isInterviewer(): bool
    {
        return $this->role === 'interviewer';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
