<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->boolean('is_remote')->default(false);
            $table->string('employment_type')->nullable(); // full-time, part-time, contract
            $table->decimal('salary_min', 12, 2)->nullable();
            $table->decimal('salary_max', 12, 2)->nullable();
            $table->string('salary_currency', 10)->nullable();
            $table->string('application_url')->nullable();
            $table->string('source_url')->nullable();
            $table->string('external_id')->nullable();
            $table->string('source')->nullable(); // remoteok, linkedin, manual, etc.
            $table->enum('status', ['active', 'expired', 'closed', 'unknown'])->default('active');
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('source_url');
            $table->index('external_id');
            $table->index('status');
            $table->index('is_remote');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_listings');
    }
};
