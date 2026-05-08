@extends('layouts.app')
@section('title', 'Evaluation Report')
@section('page-title', 'Evaluation Report')

@section('content')
<div class="row justify-content-center">
<div class="col-lg-9">

    {{-- Header --}}
    <div class="card stat-card mb-4">
        <div class="card-body p-4">
            <div class="d-flex align-items-start justify-content-between gap-4 mb-4">
                <div>
                    <h5 class="fw-bold mb-0">Interview Evaluation</h5>
                    <p class="text-muted small mb-0">By {{ $report->interviewer->name }} · {{ $report->created_at->format('F d, Y') }}</p>
                </div>
                @if($report->hire_recommendation)
                    @php
                        $rec = $report->hire_recommendation;
                        $recCls = in_array($rec, ['strong_hire','hire']) ? 'bg-success text-white' : 'bg-danger text-white';
                    @endphp
                    <span class="badge rounded-pill fs-6 {{ $recCls }}">{{ ucwords(str_replace('_', ' ', $rec)) }}</span>
                @endif
            </div>

            {{-- Score grid --}}
            @php
                $scores = [
                    'Overall'         => $report->overall_score,
                    'Technical'       => $report->technical_score,
                    'Communication'   => $report->communication_score,
                    'Problem Solving' => $report->problem_solving_score,
                    'System Design'   => $report->system_design_score,
                    'Behavioral'      => $report->behavioral_score,
                ];
            @endphp
            <div class="row g-3 mb-4">
                @foreach($scores as $label => $score)
                    @if($score)
                    <div class="col-6 col-sm-4">
                        <div class="bg-light rounded-3 p-3 text-center">
                            <p class="text-muted small mb-1">{{ $label }}</p>
                            <p class="fw-bold mb-2 {{ $label === 'Overall' ? 'text-primary display-6' : 'fs-4' }}">{{ $score }}</p>
                            <div class="bg-secondary bg-opacity-25 rounded-pill" style="height:5px">
                                <div class="bg-primary rounded-pill" style="height:5px;width:{{ ($score / 10) * 100 }}%"></div>
                            </div>
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>

            {{-- Topics covered --}}
            @if($report->topics_covered)
            <div>
                <p class="text-muted small fw-semibold text-uppercase mb-2" style="letter-spacing:.05em">Topics Covered</p>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($report->topics_covered as $topic)
                        <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary">{{ $topic }}</span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Feedback --}}
    <div class="card stat-card mb-4">
        <div class="card-body p-4">
            <h6 class="fw-semibold text-success mb-2">✅ Strengths</h6>
            <p class="text-muted small lh-base mb-4" style="white-space:pre-line">{{ $report->strengths }}</p>

            <h6 class="fw-semibold mb-2" style="color:#c2410c">🎯 Areas for Improvement</h6>
            <p class="text-muted small lh-base mb-4" style="white-space:pre-line">{{ $report->areas_for_improvement }}</p>

            <h6 class="fw-semibold mb-2">Detailed Feedback</h6>
            <p class="text-muted small lh-base mb-0" style="white-space:pre-line">{{ $report->detailed_feedback }}</p>
        </div>
    </div>

    <a href="{{ route('evaluations.index') }}" class="text-primary text-decoration-none small">← Back to Reports</a>
</div>
</div>
@endsection
