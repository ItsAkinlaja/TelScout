<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_profile_id')->constrained()->cascadeOnDelete();
            $table->string('skill');
            $table->enum('level', ['beginner', 'intermediate', 'advanced', 'expert'])->default('intermediate');
            $table->integer('years')->nullable();
            $table->timestamps();

            $table->unique(['candidate_profile_id', 'skill']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_skills');
    }
};
