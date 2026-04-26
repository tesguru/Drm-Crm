<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignEmail;
use App\Models\GmailAccount;
use App\Services\GmailService;
use App\Services\ObanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
                'needs_followup_1'     => $emails
                    ->where('follow_up_count', 0)
                    ->where('has_reply', false)
                    ->count(),
                'needs_followup_2'     => $emails
                    ->where('follow_up_count', 1)
                    ->where('has_reply', false)
                    ->count(),
                'needs_followup_3plus' => $emails
                    ->where('follow_up_count', '>=', 2)
                    ->where('has_reply', false)
                    ->count(),
            ]
        ]);
    }

    // ============================================================
    // SEND FOLLOW-UPS
    // Checks bounces BEFORE queuing jobs
    // ============================================================
    public function send($campaignId)
    {
        $campaign = Campaign::where('user_id', Auth::id())
                            ->findOrFail($campaignId);

        $emails = CampaignEmail::where('campaign_id', $campaign->id)
                               ->where('status', 'sent')
                               ->where('has_reply', false)
                               ->get();

        if ($emails->isEmpty()) {
            return response()->json([
                'success' => false,
                'error'   => 'No eligible emails for follow-up.'
            ]);
        }

        // ✅ Check bounces FIRST before queuing anything
        $bouncedEmails = $this->getBounceEmails($emails);

        if (!empty($bouncedEmails)) {
            Log::info('Bounced emails detected before follow-up', [
                'count'   => count($bouncedEmails),
                'bounced' => $bouncedEmails,
            ]);
        }

        $dispatched = 0;
        $skipped    = 0;
        $bounced    = 0;

        // Group by gmail account — parallel sending
        $grouped = $emails->groupBy('gmail_account_id');

        foreach ($grouped as $accountId => $accountEmails) {
            $delay = 0;

            foreach ($accountEmails as $email) {

                // ✅ Skip bounced emails
                if (in_array(strtolower($email->to_email), $bouncedEmails)) {
                    $email->update(['status' => 'bounced']);
                    Log::info('Skipping bounced email', [
                        'to' => $email->to_email
                    ]);
                    $bounced++;
                    continue;
                }

                // Skip if not eligible
                if (!$email->canReceiveFollowUp()) {
                    $skipped++;
                    continue;
                }

                $delay += rand(2, 4);
                ObanService::insertFollowUpJob($email->id, $delay);
                $dispatched++;
            }
        }

        // Refresh campaign stats
        $campaign->refreshStats();

        return response()->json([
            'success'    => true,
            'message'    => "{$dispatched} follow-ups queued!",
            'dispatched' => $dispatched,
            'skipped'    => $skipped,
            'bounced'    => $bounced,
            'estimate'   => $this->estimateTime($dispatched),
        ]);
    }

    // ============================================================
    // GET BOUNCED EMAILS FROM ALL GMAIL ACCOUNTS
    // ============================================================
    private function getBounceEmails($emails): array
    {
        $bounced = [];

        // Get unique Gmail accounts used in this campaign
        $accountIds = $emails->pluck('gmail_account_id')->unique();
        $accounts   = GmailAccount::whereIn('id', $accountIds)->get();

        foreach ($accounts as $account) {
            try {
                $gmail          = new GmailService($account);
                $accountBounces = $gmail->checkBounces();
                $bounced        = array_merge($bounced, $accountBounces);

                Log::info('Bounce check for account', [
                    'account' => $account->email,
                    'found'   => count($accountBounces),
                ]);
            } catch (\Exception $e) {
                Log::error('Bounce check failed for account', [
                    'account' => $account->email,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        return array_map('strtolower', array_unique($bounced));
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
            $gmailService = new GmailService($email->gmailAccount);

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
    // PROGRESS
    // ============================================================
    public function progress($campaignId)
    {
        $campaign = Campaign::where('user_id', Auth::id())
                            ->findOrFail($campaignId);

        $emails = CampaignEmail::where('campaign_id', $campaign->id)
                               ->where('status', 'sent')
                               ->get();

        $inQueue = \DB::table('oban_jobs')
            ->whereIn('state', ['available', 'scheduled', 'executing'])
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
                'id'              => $email->id,
                'to_email'        => $email->to_email,
                'from_email'      => $email->from_email,
                'follow_up_count' => $email->follow_up_count,
                'has_reply'       => $email->has_reply,
                'last_follow_up'  => $email->last_follow_up_at
                    ? $email->last_follow_up_at->diffForHumans()
                    : null,
                'next_type'       => $email->nextFollowUpType(),
                'eligible'        => $email->canReceiveFollowUp(),
            ];
        });

        return response()->json([
            'success'    => true,
            'total'      => $emails->count(),
            'eligible'   => $emails->filter(fn($e) => $e->canReceiveFollowUp())->count(),
            'replied'    => $emails->where('has_reply', true)->count(),
            'in_queue'   => $inQueue,
            'follow_ups' => $followUps,
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
}