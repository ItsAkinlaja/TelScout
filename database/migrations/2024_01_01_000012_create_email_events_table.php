<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_message_id')->constrained()->cascadeOnDelete();
            $table->string('event_type'); // sent, opened, replied, failed, bounced, etc.
            $table->text('description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();

            $table->index(['email_message_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_events');
    }
};
