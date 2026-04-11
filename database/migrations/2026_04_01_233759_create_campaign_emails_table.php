<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('campaign_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')
                  ->constrained()
                  ->onDelete('cascade');
            $table->foreignId('user_id')
                  ->constrained()
                  ->onDelete('cascade');
            $table->foreignId('gmail_account_id')
                  ->constrained()
                  ->onDelete('cascade');

            // Recipient info
            $table->string('to_email');
            $table->string('from_email');
            $table->string('first_name')->nullable();
            $table->string('company_name')->nullable();

            // Email content
            $table->string('subject');
            $table->longText('body');

            // Gmail threading
            $table->string('gmail_message_id')
                  ->nullable();
            $table->string('gmail_thread_id')
                  ->nullable();
            $table->string('gmail_label_id')
                  ->nullable();

            // Template tracking
            $table->enum('template_type', [
                'bulk_template',
                'followup_1',
                'followup_2',
                'followup_3'
            ])->default('bulk_template');
            $table->integer('template_number')
                  ->nullable();

            // Status
            $table->enum('status', [
                'pending',
                'sent',
                'failed'
            ])->default('pending');

            // Reply tracking
            $table->boolean('has_reply')
                  ->default(false);
            $table->timestamp('replied_at')
                  ->nullable();

            // Follow-up tracking
            $table->integer('follow_up_count')
                  ->default(0);
            $table->timestamp('last_follow_up_at')
                  ->nullable();

            $table->timestamp('sent_at')
                  ->nullable();
            $table->timestamps();

            // Indexes for speed
            $table->index(['campaign_id', 'status']);
            $table->index(['campaign_id', 'has_reply']);
            $table->index('gmail_thread_id');
            $table->index('from_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_emails');
    }
};