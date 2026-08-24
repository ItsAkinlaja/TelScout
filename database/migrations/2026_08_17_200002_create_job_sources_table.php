<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_sources', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('source_type'); // e.g. 'greenhouse', 'lever', 'ashby', 'workable', 'generic', 'remoteok'
            $table->string('source_url'); // the feed/API URL to poll
            $table->string('ats_type')->nullable(); // normalized ATS identifier: 'greenhouse', 'lever', 'ashby', 'workable', null for generic
            $table->boolean('active')->default(true);
            $table->timestamp('last_fetched_at')->nullable();
            $table->timestamp('next_fetch_at')->nullable();
            $table->integer('failure_count')->default(0);
            $table->json('meta')->nullable(); // source-specific config (e.g. board token for Greenhouse)
            $table->timestamps();

            $table->index('company_id');
            $table->index('active');
            $table->index('next_fetch_at');
            $table->index('source_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_sources');
    }
};
