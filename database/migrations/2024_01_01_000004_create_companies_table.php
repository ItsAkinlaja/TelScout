<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('domain')->nullable();
            $table->string('normalized_domain')->nullable();
            $table->string('website')->nullable();
            $table->string('careers_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('logo_url')->nullable();
            $table->text('description')->nullable();
            $table->string('industry')->nullable();
            $table->string('location')->nullable();
            $table->string('size')->nullable();
            $table->json('tech_stack')->nullable();
            $table->string('contact_status')->default('unavailable'); // available, unavailable, partial
            $table->string('contact_email')->nullable();
            $table->boolean('is_excluded')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('domain');
            $table->index('normalized_domain');
            $table->index('is_excluded');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
