<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'domain',
        'price',
        'your_name',
        'label_name',
        'gmail_label_id',
        'status',
        'total_emails',
        'sent_count',
        'replied_count',
        'follow_up_count',
        'bounce_count',
    ];

    // ============================================================
    // RELATIONSHIPS
    // ============================================================
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function emails()
    {
        return $this->hasMany(CampaignEmail::class);
    }

    // Sent emails without reply — eligible for follow-up
    public function emailsWithoutReply()
    {
        return $this->hasMany(CampaignEmail::class)
                    ->where('has_reply', false)
                    ->where('status', 'sent');
    }

    // Emails with reply
    public function emailsWithReply()
    {
        return $this->hasMany(CampaignEmail::class)
                    ->where('has_reply', true);
    }

    // ============================================================
    // REFRESH CAMPAIGN STATS
    // Call after every send or follow-up
    // ============================================================
  public function refreshStats(): void
{
    $this->update([
        'total_emails'    => $this->emails()->count(),
        'sent_count'      => $this->emails()
                                  ->where('status', 'sent')
                                  ->count(),
        'replied_count'   => $this->emails()
                                  ->where('has_reply', true)
                                  ->count(),
        'bounce_count'    => $this->emails()        // ← ADD
                                  ->where('has_bounce', true)
                                  ->count(),
        'follow_up_count' => $this->emails()
                                  ->sum('follow_up_count'),
    ]);
}
}