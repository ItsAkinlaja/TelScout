<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_listing_id')->constrained('job_listings')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();

            $table->decimal('match_score', 5, 2)->default(0);
            $table->string('match_classification')->nullable(); // excellent, strong, good, possible, low
            $table->json('matched_skills')->nullable();
            $table->json('missing_skills')->nullable();
            $table->text('match_reasoning')->nullable();
            $table->json('score_breakdown')->nullable();

            $table->enum('status', [
                'discovered', 'shortlisted', 'contacted', 'follow_up',
                'replied', 'interview', 'offer', 'rejected', 'closed'
            ])->default('discovered');

            $table->string('application_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('discovered_at')->useCurrent();
            $table->timestamp('applied_at')->nullable();
            $table->json('interview_dates')->nullable();
            $table->timestamps();

            $table->index('match_score');
            $table->index('status');
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};
