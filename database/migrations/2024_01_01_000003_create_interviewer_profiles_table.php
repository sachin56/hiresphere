<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interviewer_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->unique();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('current_company')->nullable();
            $table->string('current_title');
            $table->integer('years_of_experience');
            $table->enum('experience_level', ['senior', 'staff', 'principal', 'distinguished'])->default('senior');
            $table->json('domains');
            $table->json('interview_types');
            $table->json('specialization_badges')->nullable();
            $table->decimal('hourly_rate', 8, 2);
            $table->string('currency', 3)->default('USD');
            $table->integer('session_duration_minutes')->default(60);
            $table->text('interview_approach')->nullable();
            $table->boolean('offers_bundle')->default(false);
            $table->json('bundle_packages')->nullable();
            $table->decimal('average_rating', 3, 2)->default(0.00);
            $table->integer('total_reviews')->default(0);
            $table->integer('total_sessions')->default(0);
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->index('experience_level');
            $table->index('is_approved');
            $table->index('average_rating');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interviewer_profiles');
    }
};
