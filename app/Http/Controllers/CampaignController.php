<?php

namespace App\Http\Controllers;

use App\Jobs\SendInitialEmailJob;
use App\Models\Campaign;
use App\Models\CampaignEmail;
use App\Models\EmailTemplate;
use App\Models\GmailAccount;
use App\Services\GmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\ObanService;
class CampaignController extends Controller
{
    // Get all campaigns
    public function index()
    {
        $campaigns = Campaign::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($campaign) {
                return [
                    'id'             => $campaign->id,
                    'name'           => $campaign->name,
                    'domain'         => $campaign->domain,
                    'price'          => $campaign->price,
                    'your_name'      => $campaign->your_name,
                    'label_name'     => $campaign->label_name,
                    'status'         => $campaign->status,
                    'total_emails'   => $campaign->total_emails,
                    'sent_count'     => $campaign->sent_count,
                    'replied_count'  => $campaign->replied_count,
                    'follow_up_count'=> $campaign->follow_up_count,
                    'bounce_count'   => $campaign->bounce_count,
                    'pending_count'  => $campaign->emails()
                                                  ->where('status', 'pending')
                                                  ->count(),
                    'failed_count'   => $campaign->emails()
                                                  ->where('status', 'failed')
                                                  ->count(),
                    'created_at'     => $campaign->created_at,
                ];
            });

        return response()->json([
            'success'   => true,
            'campaigns' => $campaigns
        ]);
    }

    // Get single campaign details
    public function show($id)
    {
        $campaign = Campaign::where('user_id', Auth::id())
            ->with(['emails.gmailAccount'])
            ->findOrFail($id);

        $emails = $campaign->emails->map(function ($email) {
            return [
                'id'              => $email->id,
                'to_email'        => $email->to_email,
                'from_email'      => $email->from_email,
                'first_name'      => $email->first_name,
                'company_name'    => $email->company_name,
                'subject'         => $email->subject,
                'status'          => $email->status,
                'has_reply'       => $email->has_reply,
                'replied_at'      => $email->replied_at,
                'follow_up_count' => $email->follow_up_count,
                'template_type'   => $email->template_type,
                'sent_at'         => $email->sent_at,
                'gmail_account'   => $email->gmailAccount?->email,
            ];
        });

        return response()->json([
            'success'  => true,
            'campaign' => [
                'id'             => $campaign->id,
                'name'           => $campaign->name,
                'domain'         => $campaign->domain,
                'price'          => $campaign->price,
                'your_name'      => $campaign->your_name,
                'label_name'     => $campaign->label_name,
                'status'         => $campaign->status,
                'total_emails'   => $campaign->total_emails,
                'sent_count'     => $campaign->sent_count,
                'replied_count'  => $campaign->replied_count,
                'bounce_count'   => $campaign->bounce_count,
                'follow_up_count'=> $campaign->follow_up_count,
                'emails'         => $emails,
            ]
        ]);
    }

    public function store(Request $request)
{
    $request->validate([
        'name'             => 'required|string|max:255',
        'domain'           => 'required|string',
        'price'            => 'required|string',
        'your_name'        => 'required|string',
        'recipients'       => 'required|string',
        'gmail_accounts'   => 'required|array|min:1',
        'gmail_accounts.*' => 'exists:gmail_accounts,id',
        'split_mode'       => 'required|in:equal,custom',
        'custom_splits'    => 'nullable|array',
    ]);

    // Check duplicate campaign name
    $exists = Campaign::where('user_id', Auth::id())
                      ->where('name', $request->name)
                      ->exists();

    if ($exists) {
        return response()->json([
            'success' => false,
            'error'   => "Campaign \"{$request->name}\" already exists."
        ]);
    }

    // Parse recipients
    $recipients = preg_split('/[\n,;]+/', $request->recipients);
    $recipients = array_values(array_filter(
        array_map('trim', $recipients),
        fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL)
    ));

    if (empty($recipients)) {
        return response()->json([
            'success' => false,
            'error'   => 'No valid email addresses found'
        ]);
    }

    // Get Gmail accounts
    $accounts = GmailAccount::where('user_id', Auth::id())
                            ->whereIn('id', $request->gmail_accounts)
                            ->where('is_active', true)
                            ->get();

    if ($accounts->isEmpty()) {
        return response()->json([
            'success' => false,
            'error'   => 'No active Gmail accounts found'
        ]);
    }

    // Check templates exist
    $templateCount = EmailTemplate::where('user_id', Auth::id())
                                  ->where('type', 'bulk_template')
                                  ->where('is_active', true)
                                  ->count();

    if ($templateCount === 0) {
        return response()->json([
            'success' => false,
            'error'   => 'No bulk_template templates found. Please create at least one.'
        ]);
    }

    // Split recipients across accounts
    $splits = $this->splitRecipients(
        $recipients,
        $accounts,
        $request->split_mode,
        $request->custom_splits ?? []
    );

    // Create Gmail label using first account
    $firstAccount = $accounts->first();
    $gmailService = new GmailService($firstAccount);
    $labelName    = "Outbound - {$request->domain}";
    $labelResult  = $gmailService->getOrCreateLabel($labelName);
    $labelId      = $labelResult['success'] ? $labelResult['label_id'] : null;

    // Create campaign
    $campaign = Campaign::create([
        'user_id'        => Auth::id(),
        'name'           => $request->name,
        'domain'         => $request->domain,
        'price'          => $request->price,
        'your_name'      => $request->your_name,
        'label_name'     => $labelName,
        'gmail_label_id' => $labelId,
        'status'         => 'active',
        'total_emails'   => count($recipients),
    ]);

    // Create email records + dispatch jobs
    $jobsCreated = 0;

    foreach ($splits as $accountId => $accountRecipients) {
        $account = $accounts->firstWhere('id', $accountId);
        $delay   = 0; // ✅ each account gets its own delay — runs in parallel

        foreach ($accountRecipients as $recipientEmail) {
            // Get random template
            $template = EmailTemplate::getRandomByType(
                userId: Auth::id(),
                type: 'bulk_template'
            );

            if (!$template) continue;

            // Extract names
            $names = GmailService::extractNamesFromEmail(
                $recipientEmail,
                $request->domain
            );

            // Personalize
            $personalized = $template->personalize([
                'company'   => $names['company_name'],
                'domain'    => $request->domain,
                'price'     => $request->price,
                'firstName' => $names['first_name'],
                'yourName'  => $request->your_name,
            ]);

            // Create email record
            $campaignEmail = CampaignEmail::create([
                'campaign_id'      => $campaign->id,
                'user_id'          => Auth::id(),
                'gmail_account_id' => $account->id,
                'to_email'         => $recipientEmail,
                'from_email'       => $account->email,
                'first_name'       => $names['first_name'],
                'company_name'     => $names['company_name'],
                'subject'          => $personalized['subject'],
                'body'             => $personalized['body'],
                'gmail_label_id'   => $labelId,
                'template_type'    => 'bulk_template',
                'template_number'  => $template->id,
                'status'           => 'pending',
            ]);

            // ✅ 2-4 mins after previous email on THIS account only
            $delay += rand(2, 4);

            ObanService::insertEmailJob($campaignEmail->id, $delay);

            $jobsCreated++;
        }
    }

    return response()->json([
        'success'  => true,
        'message'  => "Campaign created! {$jobsCreated} emails queued.",
        'campaign' => [
            'id'           => $campaign->id,
            'name'         => $campaign->name,
            'total_emails' => count($recipients),
            'jobs_queued'  => $jobsCreated,
            'label'        => $labelName,
        ]
    ]);
}
    // Create campaign and dispatch send jobs
    public function storejj(Request $request)
    {
        $request->validate([
            'name'            => 'required|string|max:255',
            'domain'          => 'required|string',
            'price'           => 'required|string',
            'your_name'       => 'required|string',
            'recipients'      => 'required|string',
            'gmail_accounts'  => 'required|array|min:1',
            'gmail_accounts.*'=> 'exists:gmail_accounts,id',
            'split_mode'      => 'required|in:equal,custom',
            'custom_splits'   => 'nullable|array',
        ]);

        // Check duplicate campaign name
        $exists = Campaign::where('user_id', Auth::id())
                          ->where('name', $request->name)
                          ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'error'   => "Campaign \"{$request->name}\" already exists."
            ]);
        }

        // Parse recipients
        $recipients = preg_split('/[\n,;]+/', $request->recipients);
        $recipients = array_values(array_filter(
            array_map('trim', $recipients),
            fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL)
        ));

        if (empty($recipients)) {
            return response()->json([
                'success' => false,
                'error'   => 'No valid email addresses found'
            ]);
        }

        // Get Gmail accounts
        $accounts = GmailAccount::where('user_id', Auth::id())
                                ->whereIn('id', $request->gmail_accounts)
                                ->where('is_active', true)
                                ->get();

        if ($accounts->isEmpty()) {
            return response()->json([
                'success' => false,
                'error'   => 'No active Gmail accounts found'
            ]);
        }

        // Check templates exist
        $templateCount = EmailTemplate::where('user_id', Auth::id())
                                      ->where('type', 'bulk_template')
                                      ->where('is_active', true)
                                      ->count();

        if ($templateCount === 0) {
            return response()->json([
                'success' => false,
                'error'   => 'No bulk_template templates found. Please create at least one.'
            ]);
        }

        // Split recipients across accounts
        $splits = $this->splitRecipients(
            $recipients,
            $accounts,
            $request->split_mode,
            $request->custom_splits ?? []
        );

        // Create Gmail label using first account
        $firstAccount  = $accounts->first();
        $gmailService  = new GmailService($firstAccount);
        $labelName     = "Outbound - {$request->domain}";
        $labelResult   = $gmailService->getOrCreateLabel($labelName);
        $labelId       = $labelResult['success'] ? $labelResult['label_id'] : null;

        // Create campaign
        $campaign = Campaign::create([
            'user_id'        => Auth::id(),
            'name'           => $request->name,
            'domain'         => $request->domain,
            'price'          => $request->price,
            'your_name'      => $request->your_name,
            'label_name'     => $labelName,
            'gmail_label_id' => $labelId,
            'status'         => 'active',
            'total_emails'   => count($recipients),
        ]);

        // Create email records + dispatch jobs
        $delay       = 0;
        $jobsCreated = 0;

        foreach ($splits as $accountId => $accountRecipients) {
            $account  = $accounts->firstWhere('id', $accountId);

            foreach ($accountRecipients as $recipientEmail) {
                // Get random template
                $template = EmailTemplate::getRandomByType(
                    userId: Auth::id(),
                    type: 'bulk_template'
                );

                if (!$template) continue;

                // Extract names
                $names = GmailService::extractNamesFromEmail(
                    $recipientEmail,
                    $request->domain
                );

                // Personalize
                $personalized = $template->personalize([
                    'company'   => $names['company_name'],
                    'domain'    => $request->domain,
                    'price'     => $request->price,
                    'firstName' => $names['first_name'],
                    'yourName'  => $request->your_name,
                ]);

                // Create email record
                $campaignEmail = CampaignEmail::create([
                    'campaign_id'      => $campaign->id,
                    'user_id'          => Auth::id(),
                    'gmail_account_id' => $account->id,
                    'to_email'         => $recipientEmail,
                    'from_email'       => $account->email,
                    'first_name'       => $names['first_name'],
                    'company_name'     => $names['company_name'],
                    'subject'          => $personalized['subject'],
                    'body'             => $personalized['body'],
                    'gmail_label_id'   => $labelId,
                    'template_type'    => 'bulk_template',
                    'template_number'  => $template->id,
                    'status'           => 'pending',
                ]);

              
                $delayMinutes = ($jobsCreated + 1) * rand(2, 4);

// dispatch(new SendInitialEmailJob($campaignEmail->id))
//     ->delay(now()->addMinutes($delayMinutes));
$delay += rand(2, 4); // ✅ just 2-4 mins after previous

ObanService::insertEmailJob($campaignEmail->id, $delay);
            }
        }

 

        return response()->json([
            'success'    => true,
            'message'    => "Campaign created! {$jobsCreated} emails queued.",
            'campaign'   => [
                'id'           => $campaign->id,
                'name'         => $campaign->name,
                'total_emails' => count($recipients),
                'jobs_queued'  => $jobsCreated,
                'label'        => $labelName,
            ]
        ]);
    }

    // Preview split before sending
    public function previewSplit(Request $request)
    {
        $request->validate([
            'recipients'      => 'required|string',
            'gmail_accounts'  => 'required|array',
            'split_mode'      => 'required|in:equal,custom',
            'custom_splits'   => 'nullable|array',
        ]);

        $recipients = preg_split('/[\n,;]+/', $request->recipients);
        $recipients = array_values(array_filter(
            array_map('trim', $recipients),
            fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL)
        ));

        $accounts = GmailAccount::where('user_id', Auth::id())
                                ->whereIn('id', $request->gmail_accounts)
                                ->where('is_active', true)
                                ->get();

        $splits = $this->splitRecipients(
            $recipients,
            $accounts,
            $request->split_mode,
            $request->custom_splits ?? []
        );

        $preview = [];
        foreach ($splits as $accountId => $accountRecipients) {
            $account   = $accounts->firstWhere('id', $accountId);
            $preview[] = [
                'account'   => $account->email,
                'count'     => count($accountRecipients),
                'remaining' => $account->remainingToday(),
                'emails'    => array_slice($accountRecipients, 0, 3),
            ];
        }

        return response()->json([
            'success'     => true,
            'total'       => count($recipients),
            'preview'     => $preview,
            'total_time'  => $this->estimateTime(count($recipients)),
        ]);
    }

    // Retry failed emails
    public function retryFailed($id)
    {
        $campaign = Campaign::where('user_id', Auth::id())
                            ->findOrFail($id);

        $failedEmails = CampaignEmail::where('campaign_id', $campaign->id)
                                     ->where('status', 'failed')
                                     ->get();

        $delay = 0;
        foreach ($failedEmails as $email) {
            $email->update(['status' => 'pending']);
            $delay += rand(5, 12);
            dispatch(new SendInitialEmailJob($email->id))
                ->delay(now()->addMinutes($delay));
        }

        return response()->json([
            'success' => true,
            'message' => "{$failedEmails->count()} emails requeued"
        ]);
    }

    // Delete campaign
    public function destroy($id)
    {
        $campaign = Campaign::where('user_id', Auth::id())
                            ->findOrFail($id);

        $campaign->delete();

        return response()->json([
            'success' => true,
            'message' => 'Campaign deleted'
        ]);
    }

    // ============================================================
    // SPLIT RECIPIENTS ACROSS ACCOUNTS
    // ============================================================
    private function splitRecipients(
        array $recipients,
        $accounts,
        string $mode,
        array $customSplits
    ): array {
        $splits = [];
        $total  = count($recipients);
        $count  = $accounts->count();

        if ($mode === 'equal') {
            $perAccount = (int) ceil($total / $count);
            $offset     = 0;

            foreach ($accounts as $account) {
                $splits[$account->id] = array_slice(
                    $recipients,
                    $offset,
                    $perAccount
                );
                $offset += $perAccount;
            }
        } else {
            // Custom split
            $offset = 0;
            foreach ($accounts as $account) {
                $amount = $customSplits[$account->id]
                    ?? (int) ceil($total / $count);
                $splits[$account->id] = array_slice(
                    $recipients,
                    $offset,
                    $amount
                );
                $offset += $amount;
            }
        }

        return $splits;
    }

    // Estimate sending time
    private function estimateTime(int $count): string
    {
        $avgMinutes  = 8.5; // Average of 5-12
        $totalMins   = $count * $avgMinutes;
        $hours       = floor($totalMins / 60);
        $mins        = $totalMins % 60;

        if ($hours > 0) {
            return "{$hours}h {$mins}m";
        }
        return "{$mins} minutes";
    }
}