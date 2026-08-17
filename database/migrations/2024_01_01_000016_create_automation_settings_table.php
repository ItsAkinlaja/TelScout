<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('daily_send_limit')->default(10);
            $table->integer('hourly_send_limit')->default(5);
            $table->boolean('auto_send')->default(false);
            $table->boolean('require_approval')->default(true);
            $table->string('working_hours_start', 5)->default('08:00');
            $table->string('working_hours_end', 5)->default('18:00');
            $table->string('timezone')->default('Africa/Lagos');
            $table->integer('min_delay_seconds')->default(60);
            $table->integer('max_delay_seconds')->default(300);
            $table->integer('follow_up_interval_days')->default(4);
            $table->integer('max_follow_ups')->default(2);
            $table->integer('minimum_match_score')->default(70);
            $table->boolean('discovery_enabled')->default(true);
            $table->json('search_keywords')->nullable();
            $table->json('search_locations')->nullable();
            $table->boolean('remote_only')->default(false);
            $table->decimal('minimum_salary', 12, 2)->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_settings');
    }
};
