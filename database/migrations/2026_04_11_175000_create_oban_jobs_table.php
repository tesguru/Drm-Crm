<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("
            CREATE TABLE oban_jobs (
                id              BIGSERIAL PRIMARY KEY,
                state           TEXT        NOT NULL DEFAULT 'available',
                queue           TEXT        NOT NULL DEFAULT 'default',
                worker          TEXT        NOT NULL,
                args            JSONB       NOT NULL DEFAULT '{}',
                errors          JSONB       NOT NULL DEFAULT '[]',
                tags            TEXT[]      NOT NULL DEFAULT '{}',
                meta            JSONB       NOT NULL DEFAULT '{}',
                priority        INTEGER     NOT NULL DEFAULT 0,
                attempt         INTEGER     NOT NULL DEFAULT 0,
                max_attempts    INTEGER     NOT NULL DEFAULT 3,
                inserted_at     TIMESTAMP   NOT NULL,
                scheduled_at    TIMESTAMP   NOT NULL,
                attempted_at    TIMESTAMP,
                completed_at    TIMESTAMP,
                discarded_at    TIMESTAMP,
                cancelled_at    TIMESTAMP
            )
        ");

        DB::statement("
            CREATE INDEX oban_jobs_state_queue_priority_scheduled_at_id_index
            ON oban_jobs (state, queue, priority, scheduled_at, id)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('oban_jobs');
    }
};