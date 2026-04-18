<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Drop old constraint
        DB::statement("ALTER TABLE email_templates DROP CONSTRAINT email_templates_type_check");
        
        // Change to varchar — no more limits
        DB::statement("ALTER TABLE email_templates ALTER COLUMN type TYPE VARCHAR(50)");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE email_templates ALTER COLUMN type TYPE VARCHAR(50) USING type::varchar");
    }
};