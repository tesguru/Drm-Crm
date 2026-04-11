<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('gmail_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained()
                  ->onDelete('cascade');
            $table->string('email')->unique();
            $table->string('name')->nullable();
            $table->string('avatar')->nullable();
            $table->text('google_token');
            $table->text('google_refresh_token')
                  ->nullable();
            $table->integer('daily_limit')
                  ->default(50);
            $table->integer('daily_sent')
                  ->default(0);
            $table->integer('total_sent')
                  ->default(0);
            $table->boolean('is_active')
                  ->default(true);
            $table->timestamp('last_used_at')
                  ->nullable();
            $table->timestamp('daily_reset_at')
                  ->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gmail_accounts');
    }
};