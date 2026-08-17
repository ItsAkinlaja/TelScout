<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opportunity_id')->constrained()->cascadeOnDelete();
            $table->enum('status', [
                'discovered', 'shortlisted', 'contacted', 'follow_up',
                'replied', 'interview', 'offer', 'rejected', 'closed'
            ])->default('discovered');
            $table->integer('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->json('interview_dates')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->unique('opportunity_id');
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
