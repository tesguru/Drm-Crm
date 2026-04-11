<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained()
                  ->onDelete('cascade');
            $table->string('name');
            $table->string('domain');
            $table->string('price');
            $table->string('your_name');
            $table->string('label_name');
            $table->string('gmail_label_id')
                  ->nullable();
            $table->enum('status', [
                'active',
                'paused',
                'completed'
            ])->default('active');
            $table->integer('total_emails')
                  ->default(0);
            $table->integer('sent_count')
                  ->default(0);
            $table->integer('replied_count')
                  ->default(0);
            $table->integer('follow_up_count')
                  ->default(0);
            $table->timestamps();

            // Duplicate check — campaign name per user
            $table->unique(['user_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};