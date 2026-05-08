<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'candidate_id',
        'interviewer_id',
        'slot_id',
        'interview_type',
        'interview_domain',
        'status',
        'scheduled_at',
        'duration_minutes',
        'timezone',
        'candidate_notes',
        'interviewer_notes',
        'amount',
        'currency',
        'payment_status',
        'payment_intent_id',
        'stripe_session_id',
        'webrtc_room_id',
        'webrtc_room_url',
        'recording_url',
        'recording_consent',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'amount' => 'float',
            'recording_consent' => 'boolean',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function candidate()
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }

    public function interviewer()
    {
        return $this->belongsTo(User::class, 'interviewer_id');
    }

    public function slot()
    {
        return $this->belongsTo(AvailabilitySlot::class, 'slot_id');
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }

    public function evaluationReport()
    {
        return $this->hasOne(EvaluationReport::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function isPending(): bool { return $this->status === 'pending'; }
    public function isAccepted(): bool { return $this->status === 'accepted'; }
    public function isCompleted(): bool { return $this->status === 'completed'; }
}
