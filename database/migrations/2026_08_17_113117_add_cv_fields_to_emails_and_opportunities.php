<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_messages', function (Blueprint $table) {
            $table->boolean('attach_cv')->default(false)->after('body_text');
            $table->string('cv_path')->nullable()->after('attach_cv');
        });

        Schema::table('opportunities', function (Blueprint $table) {
            $table->text('cv_tailoring_suggestions')->nullable()->after('match_reasoning');
        });
    }

    public function down(): void
    {
        Schema::table('email_messages', function (Blueprint $table) {
            $table->dropColumn(['attach_cv', 'cv_path']);
        });

        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropColumn('cv_tailoring_suggestions');
        });
    }
};
