<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignEmail extends Model
{
    protected $fillable = [
        'campaign_id',
        'user_id',
        'gmail_account_id',
        'to_email',
        'from_email',
        'first_name',
        'company_name',
        'subject',
        'body',
        'gmail_message_id',
        'gmail_thread_id',
        'gmail_label_id',
        'template_type',
        'template_number',
        'status',
        'has_reply',
        'replied_at',
        'follow_up_count',
        'last_follow_up_at',
        'sent_at',
    ];

    protected $casts = [
        'has_reply'         => 'boolean',
        'replied_at'        => 'datetime',
        'last_follow_up_at' => 'datetime',
        'sent_at'           => 'datetime',
    ];

    // ============================================================
    // RELATIONSHIPS
    // ============================================================
    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function gmailAccount()
    {
        return $this->belongsTo(GmailAccount::class);
    }

    // ============================================================
    // UNLIMITED FOLLOW-UPS
    // Only stops when recipient replies
    // ============================================================
    public function canReceiveFollowUp(): bool
    {
        return !$this->has_reply
            && $this->status === 'sent';
    }

    // ============================================================
    // NEXT FOLLOW-UP TYPE
    // Supports up to followup_20
    // After 20 keeps rotating followup_20
    // ============================================================
    public function nextFollowUpType(): string
    {
        $count = $this->follow_up_count;

        // followup_1 through followup_20
        if ($count >= 0 && $count < 20) {
            return 'followup_' . ($count + 1);
        }

        // After 20 keep using followup_20
        return 'followup_20';
    }

    // ============================================================
    // MARK AS REPLIED
    // ============================================================
    public function markAsReplied(): void
    {
        $this->update([
            'has_reply'  => true,
            'replied_at' => now(),
        ]);
    }

    // ============================================================
    // INCREMENT FOLLOW-UP COUNT
    // ============================================================
    public function incrementFollowUp(): void
    {
        $this->update([
            'follow_up_count'   => $this->follow_up_count + 1,
            'last_follow_up_at' => now(),
        ]);
    }
}