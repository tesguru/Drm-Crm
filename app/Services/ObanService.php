<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ObanService
{
    public static function insertEmailJob(
        int $campaignEmailId,
        int $delayMinutes = 0
    ): void {
        $state = $delayMinutes > 0 ? 'scheduled' : 'available';

        DB::table('oban_jobs')->insert([
            'state'        => DB::raw("'{$state}'::oban_job_state"),
            'queue'        => 'emails',
            'worker'       => 'Elixir.DomainOutreach.Workers.SendInitialEmailWorker',
            'args'         => DB::raw("'" . json_encode(['campaign_email_id' => $campaignEmailId]) . "'::jsonb"),
            'errors'       => DB::raw("ARRAY[]::jsonb[]"),
            'tags'         => DB::raw("ARRAY[]::text[]"),
            'meta'         => DB::raw("'{}'::jsonb"),
            'attempted_by' => DB::raw("ARRAY[]::text[]"),
            'priority'     => 0,
            'attempt'      => 0,
            'max_attempts' => 3,
            'inserted_at'  => now(),
            'scheduled_at' => now()->addMinutes($delayMinutes),
        ]);
    }

    public static function insertFollowUpJob(
        int $campaignEmailId,
        int $delayMinutes = 0
    ): void {
        $state = $delayMinutes > 0 ? 'scheduled' : 'available';

        DB::table('oban_jobs')->insert([
            'state'        => DB::raw("'{$state}'::oban_job_state"),
            'queue'        => 'follow_ups',
            'worker'       => 'Elixir.DomainOutreach.Workers.SendFollowUpWorker',
            'args'         => DB::raw("'" . json_encode(['campaign_email_id' => $campaignEmailId]) . "'::jsonb"),
            'errors'       => DB::raw("ARRAY[]::jsonb[]"),
            'tags'         => DB::raw("ARRAY[]::text[]"),
            'meta'         => DB::raw("'{}'::jsonb"),
            'attempted_by' => DB::raw("ARRAY[]::text[]"),
            'priority'     => 0,
            'attempt'      => 0,
            'max_attempts' => 3,
            'inserted_at'  => now(),
            'scheduled_at' => now()->addMinutes($delayMinutes),
        ]);
    }

    public static function getPendingCount(array $campaignEmailIds): int
    {
        if (empty($campaignEmailIds)) return 0;

        return DB::table('oban_jobs')
            ->whereIn('state', ['available', 'scheduled', 'executing'])
            ->whereIn(
                DB::raw("(args->>'campaign_email_id')::int"),
                $campaignEmailIds
            )
            ->count();
    }
}