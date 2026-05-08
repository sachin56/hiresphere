<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CandidateProfile extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'current_role',
        'target_role',
        'years_of_experience',
        'education_level',
        'university',
        'skills',
        'target_companies',
        'preparation_level',
        'total_interviews',
        'average_rating_received',
        'streak_days',
    ];

    protected function casts(): array
    {
        return [
            'skills' => 'array',
            'target_companies' => 'array',
            'average_rating_received' => 'float',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
