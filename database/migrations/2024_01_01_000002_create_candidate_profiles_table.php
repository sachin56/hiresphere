<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->unique();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('current_role')->nullable();
            $table->string('target_role')->nullable();
            $table->integer('years_of_experience')->default(0);
            $table->string('education_level')->nullable();
            $table->string('university')->nullable();
            $table->json('skills')->nullable();
            $table->json('target_companies')->nullable();
            $table->enum('preparation_level', ['beginner', 'intermediate', 'advanced'])->default('beginner');
            $table->integer('total_interviews')->default(0);
            $table->decimal('average_rating_received', 3, 2)->nullable();
            $table->integer('streak_days')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_profiles');
    }
};
