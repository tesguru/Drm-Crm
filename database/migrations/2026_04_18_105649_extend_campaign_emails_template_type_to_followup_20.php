<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("
            ALTER TABLE campaign_emails
            MODIFY COLUMN template_type ENUM(
                'bulk_template',
                'followup_1',  'followup_2',  'followup_3',  'followup_4',  'followup_5',
                'followup_6',  'followup_7',  'followup_8',  'followup_9',  'followup_10',
                'followup_11', 'followup_12', 'followup_13', 'followup_14', 'followup_15',
                'followup_16', 'followup_17', 'followup_18', 'followup_19', 'followup_20'
            ) NOT NULL DEFAULT 'bulk_template'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE campaign_emails
            MODIFY COLUMN template_type ENUM(
                'bulk_template',
                'followup_1',
                'followup_2',
                'followup_3'
            ) NOT NULL DEFAULT 'bulk_template'
        ");
    }
};