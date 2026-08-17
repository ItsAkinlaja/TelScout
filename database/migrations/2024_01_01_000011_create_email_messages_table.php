<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opportunity_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('google_account_id')->nullable()->constrained()->nullOnDelete();

            $table->string('recipient_email');
            $table->string('recipient_name')->nullable();
            $table->string('subject');
            $table->text('body_html')->nullable();
            $table->text('body_text')->nullable();

            $table->enum('status', [
                'draft', 'approved', 'queued', 'sending', 'sent',
                'failed', 'replied', 'follow_up_due', 'completed', 'rejected'
            ])->default('draft');

            $table->string('gmail_message_id')->nullable();
            $table->string('gmail_thread_id')->nullable();
            $table->integer('follow_up_count')->default(0);
            $table->timestamp('follow_up_due_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->integer('retry_count')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('recipient_email');
            $table->index('status');
            $table->index('sent_at');
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_messages');
    }
};
