<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Change enum to string to support unlimited types
        Schema::table('email_templates', function (Blueprint $table) {
            $table->string('type')
                  ->default('bulk_template')
                  ->change();
        });

        Schema::table('campaign_emails', function (Blueprint $table) {
            $table->string('template_type')
                  ->default('bulk_template')
                  ->change();
        });
    }

    public function down(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->enum('type', [
                'bulk_template',
                'followup_1',
                'followup_2',
                'followup_3'
            ])->default('bulk_template')->change();
        });
    }
};