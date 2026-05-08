<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationReport extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'booking_id',
        'candidate_id',
        'interviewer_id',
        'overall_score',
        'technical_score',
        'communication_score',
        'problem_solving_score',
        'system_design_score',
        'behavioral_score',
        'strengths',
        'areas_for_improvement',
        'detailed_feedback',
        'topics_covered',
        'hire_recommendation',
        'report_pdf_url',
        'is_shared_with_candidate',
    ];

    protected function casts(): array
    {
        return [
            'topics_covered' => 'array',
            'is_shared_with_candidate' => 'boolean',
        ];
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function candidate()
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }

    public function interviewer()
    {
        return $this->belongsTo(User::class, 'interviewer_id');
    }
}
