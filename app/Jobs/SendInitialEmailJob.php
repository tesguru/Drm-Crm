<?php

namespace App\Jobs;

use App\Models\CampaignEmail;
use App\Services\GmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendInitialEmailJob implements ShouldQueue
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

        // Skip if not found
        if (!$campaignEmail) {
            Log::warning('CampaignEmail not found', [
                'id' => $this->campaignEmailId
            ]);
            return;
        }

        // Skip if already sent
        if ($campaignEmail->status === 'sent') {
            return;
        }

        $gmailAccount = $campaignEmail->gmailAccount;

        // Skip if account inactive
        if (!$gmailAccount->is_active) {
            $campaignEmail->update(['status' => 'failed']);
            return;
        }

        // Skip if daily limit reached
        if ($gmailAccount->hasReachedDailyLimit()) {
            Log::warning('Daily limit reached', [
                'account' => $gmailAccount->email
            ]);
            $campaignEmail->update(['status' => 'failed']);
            return;
        }

        // Send email via Gmail Service
        $gmailService = new GmailService($gmailAccount);

        $result = $gmailService->sendEmail(
            to: $campaignEmail->to_email,
            subject: $campaignEmail->subject,
            body: $campaignEmail->body,
            labelId: $campaignEmail->gmail_label_id
        );

        if ($result['success']) {
            // Get real Message-ID from Gmail headers
            // This is critical for follow-up threading
            $realMessageId = $this->getRealMessageId(
                $gmailService,
                $result['message_id']
            );

            Log::info('Real Message-ID fetched', [
                'original'  => $result['message_id'],
                'real'      => $realMessageId,
            ]);

            $campaignEmail->update([
                'status'           => 'sent',
                'gmail_message_id' => $realMessageId
                    ?? $result['message_id'],
                'gmail_thread_id'  => $result['thread_id'],
                'sent_at'          => now(),
            ]);

            $campaignEmail->campaign->refreshStats();

            Log::info('Email sent successfully', [
                'to'         => $campaignEmail->to_email,
                'account'    => $gmailAccount->email,
                'thread_id'  => $result['thread_id'],
                'message_id' => $realMessageId,
            ]);

        } else {
            $campaignEmail->update(['status' => 'failed']);

            Log::error('Email send failed', [
                'to'    => $campaignEmail->to_email,
                'error' => $result['error'],
            ]);

            throw new \Exception($result['error']);
        }
    }

    // ============================================================
    // GET REAL MESSAGE-ID FROM GMAIL HEADERS
    // Gmail internal ID is different from RFC Message-ID
    // We need the RFC Message-ID for proper threading
    // ============================================================
    private function getRealMessageId(
        GmailService $gmailService,
        string $messageId
    ): ?string {
        try {
            $message = $gmailService->getMessage($messageId);

            if ($message['success']) {
                return $message['message_id'];
            }

            return null;

        } catch (\Exception $e) {
            Log::warning('Could not fetch real Message-ID', [
                'message_id' => $messageId,
                'error'      => $e->getMessage(),
            ]);
            return null;
        }
    }

    // ============================================================
    // CALLED WHEN ALL RETRIES EXHAUSTED
    // ============================================================
    public function failed(\Throwable $exception): void
    {
        CampaignEmail::where('id', $this->campaignEmailId)
                     ->update(['status' => 'failed']);

        Log::error('Job permanently failed', [
            'campaign_email_id' => $this->campaignEmailId,
            'error'             => $exception->getMessage(),
        ]);
    }
}