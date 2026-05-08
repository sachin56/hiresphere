<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('availability_slots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('interviewer_id');
            $table->foreign('interviewer_id')->references('id')->on('interviewer_profiles')->onDelete('cascade');
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->string('timezone', 50)->default('UTC');
            $table->enum('status', ['available', 'booked', 'cancelled'])->default('available');
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_rule')->nullable();
            $table->timestamps();

            $table->index(['interviewer_id', 'status']);
            $table->index(['start_time', 'end_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('availability_slots');
    }
};
