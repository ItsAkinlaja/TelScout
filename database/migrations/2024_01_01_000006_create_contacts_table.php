<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('role')->nullable();
            $table->enum('contact_type', ['recruiter', 'hiring_manager', 'hr', 'founder', 'general_company', 'unknown'])->default('unknown');
            $table->string('source_url')->nullable();
            $table->boolean('verified')->default(false);
            $table->boolean('opted_out')->default(false);
            $table->timestamp('opted_out_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
