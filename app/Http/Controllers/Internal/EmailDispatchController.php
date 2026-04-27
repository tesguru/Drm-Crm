<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\CampaignEmail;
use App\Models\EmailTemplate;
use App\Services\GmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmailDispatchController extends Controller
{
    // ============================================================
    // SEND INITIAL EMAIL — called by Elixir Oban worker
    // ============================================================
    public function sendInitial(Request $request)
    {
        $email = CampaignEmail::with(['campaign', 'gmailAccount'])
                              ->find($request->campaign_email_id);

        if (!$email) {
            return response()->json(['error' => 'Email not found'], 404);
        }

        // Already sent — Oban can skip
        if ($email->status === 'sent') {
            return response()->json(['status' => 'already_sent']);
        }

        $account = $email->gmailAccount;

        if (!$account || !$account->is_active) {
            $email->update(['status' => 'failed']);
            return response()->json(['error' => 'Account inactive'], 400);
        }

        // Check daily limit
        if ($account->hasReachedDailyLimit()) {
            $email->update(['status' => 'failed']);
            return response()->json(['error' => 'Daily limit reached'], 400);
        }

        $gmail  = new GmailService($account);
        $result = $gmail->sendEmail(
            $email->to_email,
            $email->subject,
            $email->body,
            $email->gmail_label_id
        );

        if ($result['success']) {
            // Get real RFC Message-ID for threading
            $realMessageId = null;
            $message       = $gmail->getMessage($result['message_id']);
            if ($message['success']) {
                $realMessageId = $message['message_id'];
            }

            $email->update([
                'status'           => 'sent',
                'gmail_message_id' => $realMessageId ?? $result['message_id'],
                'gmail_thread_id'  => $result['thread_id'],
                'sent_at'          => now(),
            ]);

            $email->campaign->refreshStats();

            Log::info('Initial email sent', [
                'to'         => $email->to_email,
                'account'    => $account->email,
                'message_id' => $realMessageId,
                'thread_id'  => $result['thread_id'],
            ]);

            return response()->json(['status' => 'sent']);
        }

        $email->update(['status' => 'failed']);

        Log::error('Initial email failed', [
            'campaign_email_id' => $email->id,
            'error'             => $result['error'],
        ]);

        // Return 500 so Oban retries the job
        return response()->json(['error' => $result['error']], 500);
    }

    // ============================================================
    // SEND FOLLOW-UP EMAIL — called by Elixir Oban worker
    // ============================================================
    public function sendFollowUp(Request $request)
    {
        $email = CampaignEmail::with(['campaign', 'gmailAccount'])
                              ->find($request->campaign_email_id);

        if (!$email) {
            return response()->json(['error' => 'Email not found'], 404);
        }

        // Skip if replied
        if ($email->has_reply) {
            return response()->json(['status' => 'has_reply_skipped']);
        }

        // Skip if not sent yet
        if ($email->status !== 'sent') {
            return response()->json(['error' => 'Not sent yet'], 400);
        }

        $account = $email->gmailAccount;

        if (!$account || !$account->is_active) {
            return response()->json(['error' => 'Account inactive'], 400);
        }

        $gmail = new GmailService($account);

        // Check bounce first
        if ($email->gmail_thread_id) {
            $isBounced = $gmail->threadHasBounce($email->gmail_thread_id);
            if ($isBounced) {
                $email->markAsBounced();
                $email->campaign->refreshStats();
                Log::info("Bounce detected — stopping follow-ups", ["to" => $email->to_email]);
                return response()->json(["status" => "bounced_skipped"]);
            }
        }

        // Double check reply via Gmail API
        if ($gmail->threadHasReply($email->gmail_thread_id, $email->to_email)) {
            $email->markAsReplied();
            $email->campaign->refreshStats(); 
            Log::info('Reply detected — skipping follow-up', [
                'to' => $email->to_email
            ]);
            return response()->json(['status' => 'reply_detected_skipped']);
        }

        // Get follow-up template
        $followUpType = $email->nextFollowUpType();
        $template     = EmailTemplate::getRandomByType(
            userId: $email->user_id,
            type: $followUpType,
            excludeNumber: $email->template_number
        );

        if (!$template) {
            Log::warning('No follow-up template found', [
                'type' => $followUpType,
                'to'   => $email->to_email,
            ]);
            return response()->json(['error' => 'No template found'], 400);
        }

        // Personalize template
        $personalized = $template->personalize([
            'company'   => $email->company_name,
            'domain'    => $email->campaign->domain,
            'price'     => $email->campaign->price,
            'firstName' => $email->first_name,
            'yourName'  => $email->campaign->your_name,
        ]);

        $result = $gmail->sendFollowUp(
            $email->to_email,
            $email->subject,
            $personalized['body'],
            $email->gmail_thread_id,
            $email->gmail_message_id,
            $email->gmail_label_id
        );

        if ($result['success']) {
            $email->incrementFollowUp();
            $email->update([
                'template_number' => $template->id,
                'template_type'   => $followUpType,
            ]);
            $email->campaign->refreshStats();

            Log::info('Follow-up sent', [
                'to'           => $email->to_email,
                'follow_up_no' => $email->follow_up_count,
                'type'         => $followUpType,
                'thread_id'    => $email->gmail_thread_id,
            ]);

            return response()->json(['status' => 'followup_sent']);
        }

        Log::error('Follow-up failed', [
            'campaign_email_id' => $email->id,
            'error'             => $result['error'],
        ]);

        // Return 500 so Oban retries
        return response()->json(['error' => $result['error']], 500);
    }
}