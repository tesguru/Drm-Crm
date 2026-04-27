<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('campaign_emails', function (Blueprint $table) {
            $table->boolean('has_bounce')->default(false)->after('has_reply');
            $table->timestamp('bounced_at')->nullable()->after('has_bounce');
        });

        // PostgreSQL — drop old check constraint and add new one with 'bounced'
        DB::statement("ALTER TABLE campaign_emails DROP CONSTRAINT IF EXISTS campaign_emails_status_check");
        DB::statement("ALTER TABLE campaign_emails ADD CONSTRAINT campaign_emails_status_check CHECK (status IN ('pending','sent','failed','bounced'))");
    }

    public function down(): void
    {
        Schema::table('campaign_emails', function (Blueprint $table) {
            $table->dropColumn(['has_bounce', 'bounced_at']);
        });

        DB::statement("ALTER TABLE campaign_emails DROP CONSTRAINT IF EXISTS campaign_emails_status_check");
        DB::statement("ALTER TABLE campaign_emails ADD CONSTRAINT campaign_emails_status_check CHECK (status IN ('pending','sent','failed'))");
    }
};