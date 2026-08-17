<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opportunity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('email_message_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('follow_up_number')->default(1);
            $table->timestamp('scheduled_at');
            $table->enum('status', ['pending', 'sent', 'cancelled', 'completed'])->default('pending');
            $table->string('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_ups');
    }
};
