<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->string('primary_title')->nullable();
            $table->string('location')->nullable();
            $table->string('portfolio_url')->nullable();
            $table->text('summary')->nullable();
            $table->string('cv_path')->nullable();
            $table->json('preferred_roles')->nullable();
            $table->json('preferred_locations')->nullable();
            $table->enum('work_preference', ['remote', 'hybrid', 'onsite', 'any'])->default('any');
            $table->decimal('minimum_salary', 12, 2)->nullable();
            $table->json('preferred_currencies')->nullable();
            $table->json('preferred_industries')->nullable();
            $table->json('excluded_industries')->nullable();
            $table->json('preferred_technologies')->nullable();
            $table->integer('years_of_experience')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_profiles');
    }
};
