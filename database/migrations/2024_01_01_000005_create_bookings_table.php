<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('candidate_id');
            $table->foreign('candidate_id')->references('id')->on('users')->onDelete('cascade');
            $table->uuid('interviewer_id');
            $table->foreign('interviewer_id')->references('id')->on('users')->onDelete('cascade');
            $table->uuid('slot_id')->nullable();
            $table->foreign('slot_id')->references('id')->on('availability_slots')->onDelete('set null');
            $table->enum('interview_type', ['dsa', 'system_design', 'behavioral', 'mixed'])->default('dsa');
            $table->string('interview_domain')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected', 'cancelled', 'completed', 'no_show'])->default('pending');
            $table->dateTime('scheduled_at');
            $table->integer('duration_minutes')->default(60);
            $table->string('timezone', 50)->default('UTC');
            $table->text('candidate_notes')->nullable();
            $table->text('interviewer_notes')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->enum('payment_status', ['pending', 'paid', 'refunded', 'failed'])->default('pending');
            $table->string('payment_intent_id')->nullable();
            $table->string('stripe_session_id')->nullable();
            $table->string('webrtc_room_id')->nullable();
            $table->string('webrtc_room_url')->nullable();
            $table->string('recording_url')->nullable();
            $table->boolean('recording_consent')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['candidate_id', 'status']);
            $table->index(['interviewer_id', 'status']);
            $table->index('scheduled_at');
            $table->index('payment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
