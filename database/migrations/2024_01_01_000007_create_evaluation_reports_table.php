<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('booking_id');
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
            $table->uuid('candidate_id');
            $table->foreign('candidate_id')->references('id')->on('users')->onDelete('cascade');
            $table->uuid('interviewer_id');
            $table->foreign('interviewer_id')->references('id')->on('users')->onDelete('cascade');
            $table->integer('overall_score')->comment('1-10');
            $table->integer('technical_score')->nullable();
            $table->integer('communication_score')->nullable();
            $table->integer('problem_solving_score')->nullable();
            $table->integer('system_design_score')->nullable();
            $table->integer('behavioral_score')->nullable();
            $table->text('strengths');
            $table->text('areas_for_improvement');
            $table->text('detailed_feedback');
            $table->json('topics_covered')->nullable();
            $table->enum('hire_recommendation', ['strong_hire', 'hire', 'no_hire', 'strong_no_hire'])->nullable();
            $table->string('report_pdf_url')->nullable();
            $table->boolean('is_shared_with_candidate')->default(true);
            $table->timestamps();

            $table->index(['candidate_id', 'overall_score']);
            $table->index('booking_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_reports');
    }
};
