<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained()
                  ->onDelete('cascade');
            $table->string('name');
            $table->string('category')
                  ->nullable();
            $table->enum('type', [
                'bulk_template',
                'followup_1',
                'followup_2',
                'followup_3'
            ])->default('bulk_template');
            $table->string('subject_template');
            $table->longText('body_template');
            $table->boolean('is_active')
                  ->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};