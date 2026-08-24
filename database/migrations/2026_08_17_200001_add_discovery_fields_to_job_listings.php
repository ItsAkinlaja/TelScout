<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            $table->string('country')->nullable()->after('location');
            $table->string('city')->nullable()->after('country');
            $table->enum('workplace_type', ['remote', 'hybrid', 'onsite', 'unknown'])->default('unknown')->after('city');
            $table->enum('experience_level', ['internship', 'entry', 'mid', 'senior', 'lead', 'executive', 'unknown'])->default('unknown')->after('workplace_type');
            $table->string('content_hash', 64)->nullable()->after('experience_level')->comment('SHA-256 fingerprint for cross-source deduplication');
            $table->timestamp('first_seen_at')->nullable()->after('content_hash');
            $table->timestamp('last_seen_at')->nullable()->after('first_seen_at');

            $table->index('content_hash');
            $table->index('workplace_type');
            $table->index('experience_level');
            $table->index('last_seen_at');
        });

        // Add unique constraint separately, after deduplicating any existing rows.
        // We keep the most recent row where duplicates exist.
        \DB::statement('
            DELETE j1 FROM job_listings j1
            INNER JOIN job_listings j2
            WHERE j1.id < j2.id
              AND j1.source IS NOT NULL
              AND j1.external_id IS NOT NULL
              AND j1.source = j2.source
              AND j1.external_id = j2.external_id
        ');

        Schema::table('job_listings', function (Blueprint $table) {
            $table->unique(['source', 'external_id'], 'job_listings_source_external_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            $table->dropUnique('job_listings_source_external_id_unique');
            $table->dropIndex(['content_hash']);
            $table->dropIndex(['workplace_type']);
            $table->dropIndex(['experience_level']);
            $table->dropIndex(['last_seen_at']);

            $table->dropColumn([
                'country',
                'city',
                'workplace_type',
                'experience_level',
                'content_hash',
                'first_seen_at',
                'last_seen_at',
            ]);
        });
    }
};
