<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('automation_settings', function (Blueprint $table) {
            // Contact enrichment API keys — stored encrypted, set via AutomationSettings setters
            $table->text('hunter_api_key_encrypted')->nullable()->after('ai_max_tokens');
            $table->text('apollo_api_key_encrypted')->nullable()->after('hunter_api_key_encrypted');
        });
    }

    public function down(): void
    {
        Schema::table('automation_settings', function (Blueprint $table) {
            $table->dropColumn(['hunter_api_key_encrypted', 'apollo_api_key_encrypted']);
        });
    }
};
