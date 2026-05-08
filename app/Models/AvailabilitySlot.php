<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AvailabilitySlot extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'interviewer_id',
        'start_time',
        'end_time',
        'timezone',
        'status',
        'is_recurring',
        'recurrence_rule',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'is_recurring' => 'boolean',
        ];
    }

    public function interviewerProfile()
    {
        return $this->belongsTo(InterviewerProfile::class, 'interviewer_id');
    }

    public function booking()
    {
        return $this->hasOne(Booking::class, 'slot_id');
    }
}
