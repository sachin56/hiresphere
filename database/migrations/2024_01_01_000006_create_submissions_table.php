<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('candidate_id');
            $table->foreign('candidate_id')->references('id')->on('users')->onDelete('cascade');
            $table->uuid('booking_id')->nullable();
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('set null');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('language')->nullable();
            $table->enum('submission_type', ['file_upload', 'github_link', 'inline_code'])->default('file_upload');
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->string('github_url')->nullable();
            $table->string('github_branch')->nullable();
            $table->text('inline_code')->nullable();
            $table->enum('status', ['submitted', 'under_review', 'reviewed', 'archived'])->default('submitted');
            $table->text('interviewer_annotation')->nullable();
            $table->uuid('reviewed_by')->nullable();
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['candidate_id', 'status']);
            $table->index('booking_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
