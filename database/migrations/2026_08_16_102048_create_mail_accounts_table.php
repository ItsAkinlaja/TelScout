<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Provider: gmail | outlook | zoho | smtp
            $table->string('provider')->default('gmail');
            $table->string('label')->nullable();       // e.g. "Work Gmail", "Zoho"
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);

            // OAuth providers (Gmail, Outlook)
            $table->text('access_token_encrypted')->nullable();
            $table->text('refresh_token_encrypted')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->json('scopes')->nullable();
            $table->timestamp('connected_at')->nullable();

            // OAuth app credentials (per-user, encrypted)
            $table->text('oauth_client_id_encrypted')->nullable();
            $table->text('oauth_client_secret_encrypted')->nullable();
            $table->string('oauth_redirect_uri')->nullable();

            // SMTP providers (Zoho, custom SMTP, etc.)
            $table->string('smtp_host')->nullable();
            $table->integer('smtp_port')->nullable();
            $table->string('smtp_encryption')->nullable();  // tls | ssl | null
            $table->text('smtp_username_encrypted')->nullable();
            $table->text('smtp_password_encrypted')->nullable();

            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'provider']);
            $table->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_accounts');
    }
};
