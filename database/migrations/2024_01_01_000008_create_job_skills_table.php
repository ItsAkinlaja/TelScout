<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_listing_id')->constrained('job_listings')->cascadeOnDelete();
            $table->string('skill');
            $table->boolean('is_required')->default(true);
            $table->timestamps();

            $table->unique(['job_listing_id', 'skill']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_skills');
    }
};
