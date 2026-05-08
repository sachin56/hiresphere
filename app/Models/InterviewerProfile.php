<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InterviewerProfile extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'current_company',
        'current_title',
        'years_of_experience',
        'experience_level',
        'domains',
        'interview_types',
        'specialization_badges',
        'hourly_rate',
        'currency',
        'session_duration_minutes',
        'interview_approach',
        'offers_bundle',
        'bundle_packages',
        'average_rating',
        'total_reviews',
        'total_sessions',
        'is_approved',
        'is_available',
    ];

    protected function casts(): array
    {
        return [
            'domains' => 'array',
            'interview_types' => 'array',
            'specialization_badges' => 'array',
            'bundle_packages' => 'array',
            'hourly_rate' => 'float',
            'average_rating' => 'float',
            'offers_bundle' => 'boolean',
            'is_approved' => 'boolean',
            'is_available' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function availabilitySlots()
    {
        return $this->hasMany(AvailabilitySlot::class, 'interviewer_id');
    }

    public function availableSlots()
    {
        return $this->availabilitySlots()->where('status', 'available')->where('start_time', '>', now());
    }

    public function updateRating(): void
    {
        $avg = Review::where('reviewee_id', $this->user_id)->avg('rating');
        $count = Review::where('reviewee_id', $this->user_id)->count();
        $this->update(['average_rating' => round($avg, 2), 'total_reviews' => $count]);
    }
}
