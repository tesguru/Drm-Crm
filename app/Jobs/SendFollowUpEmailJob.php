<?php

namespace App\Jobs;

use App\Models\CampaignEmail;
use App\Models\EmailTemplate;
use App\Services\GmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendFollowUpEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 120;
    public int $timeout = 60;

    public function __construct(
        public int $campaignEmailId
    ) {}

    public function handle(): void
    {
        $campaignEmail = CampaignEmail::with([
            'campaign',
            'gmailAccount'
        ])->find($this->campaignEmailId);

        if (!$campaignEmail) return;

        // Skip if replied — most important check
        if ($campaignEmail->has_reply) {
            Log::info('Skipping follow-up — has reply', [
                'to' => $campaignEmail->to_email
            ]);
            return;
        }

        // Skip if not sent
        if ($campaignEmail->status !== 'sent') {
            Log::info('Skipping follow-up — not sent yet', [
                'to' => $campaignEmail->to_email
            ]);
            return;
        }

        // Get correct follow-up type
        // followup_1, followup_2, or followup_3 (unlimited reuse)
        $followUpType = $campaignEmail->nextFollowUpType();

        // Get random template
        $template = EmailTemplate::getRandomByType(
            userId: $campaignEmail->user_id,
            type: $followUpType,
            excludeNumber: $campaignEmail->template_number
        );

        if (!$template) {
            Log::warning('No template found for follow-up', [
                'type' => $followUpType,
                'to'   => $campaignEmail->to_email
            ]);
            return;
        }

        // Personalize
        $personalized = $template->personalize([
            'company'   => $campaignEmail->company_name,
            'domain'    => $campaignEmail->campaign->domain,
            'price'     => $campaignEmail->campaign->price,
            'firstName' => $campaignEmail->first_name,
            'yourName'  => $campaignEmail->campaign->your_name,
        ]);

        // Use SAME Gmail account that sent original
        $gmailAccount = $campaignEmail->gmailAccount;

        if (!$gmailAccount->is_active) {
            Log::warning('Gmail account inactive', [
                'account' => $gmailAccount->email
            ]);
            return;
        }

        $gmailService = new GmailService($gmailAccount);

        // Double check reply status via Gmail API
        $hasReply = $gmailService->threadHasReply(
            $campaignEmail->gmail_thread_id,
            $campaignEmail->to_email
        );

        if ($hasReply) {
            $campaignEmail->markAsReplied();
            Log::info('Reply detected via API — skipping', [
                'to' => $campaignEmail->to_email
            ]);
            return;
        }

        // Send follow-up in same thread
        $result = $gmailService->sendFollowUp(
            to: $campaignEmail->to_email,
            originalSubject: $campaignEmail->subject,
            body: $personalized['body'],
            threadId: $campaignEmail->gmail_thread_id,
            originalMessageId: $campaignEmail->gmail_message_id,
            labelId: $campaignEmail->gmail_label_id
        );

        if ($result['success']) {
            $campaignEmail->incrementFollowUp();
            $campaignEmail->update([
                'template_number' => $template->id,
                'template_type'   => $followUpType,
            ]);
            $campaignEmail->campaign->refreshStats();

            Log::info('Follow-up sent successfully', [
                'to'             => $campaignEmail->to_email,
                'follow_up_no'   => $campaignEmail->follow_up_count,
                'type'           => $followUpType,
                'thread_id'      => $campaignEmail->gmail_thread_id,
            ]);

        } else {
            Log::error('Follow-up send failed', [
                'to'    => $campaignEmail->to_email,
                'error' => $result['error'],
            ]);
            throw new \Exception($result['error']);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Follow-up job permanently failed', [
            'campaign_email_id' => $this->campaignEmailId,
            'error'             => $exception->getMessage(),
        ]);
    }
}