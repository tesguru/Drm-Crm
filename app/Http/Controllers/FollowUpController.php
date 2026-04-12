<?php

namespace App\Http\Controllers;

use App\Jobs\SendFollowUpEmailJob;
use App\Models\Campaign;
use App\Models\CampaignEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\ObanService;
class FollowUpController extends Controller
{
    // ============================================================
    // FOLLOW-UP STATUS
    // ============================================================
    public function status($campaignId)
    {
        $campaign = Campaign::where('user_id', Auth::id())
                            ->findOrFail($campaignId);

        $emails = CampaignEmail::where('campaign_id', $campaign->id)
                               ->where('status', 'sent')
                               ->get();

        $eligible = $emails->filter->canReceiveFollowUp()->count();
        $replied  = $emails->where('has_reply', true)->count();

        return response()->json([
            'success'         => true,
            'total_sent'      => $emails->count(),
            'eligible'        => $eligible,
            'replied_skipped' => $replied,
            'breakdown'       => [
                'needs_followup_1'    => $emails
                    ->where('follow_up_count', 0)
                    ->where('has_reply', false)
                    ->count(),
                'needs_followup_2'    => $emails
                    ->where('follow_up_count', 1)
                    ->where('has_reply', false)
                    ->count(),
                'needs_followup_3plus'=> $emails
                    ->where('follow_up_count', '>=', 2)
                    ->where('has_reply', false)
                    ->count(),
            ]
        ]);
    }

    // ============================================================
    // SEND FOLLOW-UPS — YOU TAP THIS BUTTON
    // Unlimited follow-ups — only skips replied emails
    // ============================================================
    public function send($campaignId)
    {
        $campaign = Campaign::where('user_id', Auth::id())
                            ->findOrFail($campaignId);

        // Get ALL sent emails without reply — no limit
        $emails = CampaignEmail::where('campaign_id', $campaign->id)
                               ->where('status', 'sent')
                               ->where('has_reply', false)
                               ->get();

        if ($emails->isEmpty()) {
            return response()->json([
                'success' => false,
                'error'   => 'No eligible emails for follow-up. Either all replied or none sent yet.'
            ]);
        }

        $dispatched = 0;
        $skipped    = 0;
        $delay      = 0;

        foreach ($emails as $email) {
            if (!$email->canReceiveFollowUp()) {
                $skipped++;
                continue;
            }

         
       // Follow-ups — 3 to 6 minutes
$delayMinutes = ($dispatched + 1) * rand(1, 3);

// dispatch(new SendFollowUpEmailJob($email->id))
//     ->delay(now()->addMinutes($delayMinutes));

    ObanService::insertFollowUpJob(
    $email->id,
    ($dispatched + 1) * rand(1, 4)
);

            $dispatched++;
        }

        return response()->json([
            'success'    => true,
            'message'    => "{$dispatched} follow-ups queued!",
            'dispatched' => $dispatched,
            'skipped'    => $skipped,
            'estimate'   => $this->estimateTime($dispatched),
        ]);
    }

    // ============================================================
    // CHECK REPLIES VIA GMAIL API
    // ============================================================
    public function checkReplies($campaignId)
    {
        $campaign = Campaign::where('user_id', Auth::id())
                            ->findOrFail($campaignId);

        $emails = CampaignEmail::where('campaign_id', $campaign->id)
                               ->where('status', 'sent')
                               ->where('has_reply', false)
                               ->whereNotNull('gmail_thread_id')
                               ->with('gmailAccount')
                               ->get();

        $repliesFound = 0;

        foreach ($emails as $email) {
            $gmailService = new \App\Services\GmailService(
                $email->gmailAccount
            );

            $hasReply = $gmailService->threadHasReply(
                $email->gmail_thread_id,
                $email->to_email
            );

            if ($hasReply) {
                $email->markAsReplied();
                $repliesFound++;
            }
        }

        $campaign->refreshStats();

        return response()->json([
            'success'       => true,
            'replies_found' => $repliesFound,
            'message'       => $repliesFound > 0
                ? "{$repliesFound} new replies detected and marked"
                : "No new replies found"
        ]);
    }

    // ============================================================
    // ESTIMATE TIME
    // ============================================================
    private function estimateTime(int $count): string
    {
        $avgMinutes = 5.5;
        $totalMins  = (int)($count * $avgMinutes);
        $hours      = floor($totalMins / 60);
        $mins       = $totalMins % 60;

        if ($hours > 0) {
            return "{$hours}h {$mins}m estimated";
        }
        return "{$mins} minutes estimated";
    }

public function progress($campaignId)
{
    $campaign = Campaign::where('user_id', Auth::id())
                        ->findOrFail($campaignId);

    $emails = CampaignEmail::where('campaign_id', $campaign->id)
                           ->where('status', 'sent')
                           ->get();

    $inQueue = \DB::table('oban_jobs')
        ->whereIn('state', [
            'available',
            'scheduled',
            'executing'
        ])
        ->whereRaw(
            "args->>'campaign_email_id' IN (
                SELECT id::text FROM campaign_emails
                WHERE campaign_id = ?
            )",
            [$campaign->id]
        )
        ->count();

    $followUps = $emails->map(function ($email) {
        return [
            'id'             => $email->id,
            'to_email'       => $email->to_email,
            'from_email'     => $email->from_email,
            'follow_up_count'=> $email->follow_up_count,
            'has_reply'      => $email->has_reply,
            'last_follow_up' => $email->last_follow_up_at
                ? $email->last_follow_up_at
                    ->diffForHumans()
                : null,
            'next_type'      => $email->nextFollowUpType(),
            'eligible'       => $email->canReceiveFollowUp(),
        ];
    });

    return response()->json([
        'success'     => true,
        'total'       => $emails->count(),
        'eligible'    => $emails->filter(
            fn($e) => $e->canReceiveFollowUp()
        )->count(),
        'replied'     => $emails->where('has_reply', true)->count(),
        'in_queue'    => $inQueue,
        'follow_ups'  => $followUps,
    ]);
}

}