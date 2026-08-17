<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('automation_settings', function (Blueprint $table) {
            // Gmail OAuth — stored encrypted
            $table->text('google_client_id_encrypted')->nullable()->after('minimum_salary');
            $table->text('google_client_secret_encrypted')->nullable()->after('google_client_id_encrypted');
            $table->string('google_redirect_uri')->nullable()->after('google_client_secret_encrypted');

            // AI provider settings
            $table->string('ai_provider')->default('openai')->after('google_redirect_uri');
            $table->text('ai_api_key_encrypted')->nullable()->after('ai_provider');
            $table->string('ai_model')->default('gpt-4o-mini')->after('ai_api_key_encrypted');
            $table->float('ai_temperature')->default(0.7)->after('ai_model');
            $table->integer('ai_max_tokens')->default(1000)->after('ai_temperature');
        });
    }

    public function down(): void
    {
        Schema::table('automation_settings', function (Blueprint $table) {
            $table->dropColumn([
                'google_client_id_encrypted',
                'google_client_secret_encrypted',
                'google_redirect_uri',
                'ai_provider',
                'ai_api_key_encrypted',
                'ai_model',
                'ai_temperature',
                'ai_max_tokens',
            ]);
        });
    }
};
