<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Fix email_templates
        DB::statement("ALTER TABLE email_templates DROP CONSTRAINT IF EXISTS email_templates_type_check");
        DB::statement("ALTER TABLE email_templates ALTER COLUMN type TYPE VARCHAR(50)");

        // Fix campaign_emails
        DB::statement("ALTER TABLE campaign_emails DROP CONSTRAINT IF EXISTS campaign_emails_template_type_check");
        DB::statement("ALTER TABLE campaign_emails ALTER COLUMN template_type TYPE VARCHAR(50)");
    }

    public function down(): void {}
};