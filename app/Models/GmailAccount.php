<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GmailAccount extends Model
{
    protected $fillable = [
        'user_id',
        'email',
        'name',
        'avatar',
        'google_token',
        'google_refresh_token',
        'daily_limit',
        'daily_sent',
        'total_sent',
        'is_active',
        'last_used_at',
        'daily_reset_at',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'last_used_at'   => 'datetime',
        'daily_reset_at' => 'datetime',
    ];

    // ============================================================
    // RELATIONSHIPS
    // ============================================================
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function campaignEmails()
    {
        return $this->hasMany(CampaignEmail::class);
    }

    // ============================================================
    // DAILY LIMIT CHECKS
    // ============================================================
    public function hasReachedDailyLimit(): bool
    {
        return $this->daily_sent >= $this->daily_limit;
    }

    public function remainingToday(): int
    {
        return max(0, $this->daily_limit - $this->daily_sent);
    }

    // ============================================================
    // RESET DAILY COUNT
    // Call this every midnight via scheduler
    // ============================================================
    public function resetDailyCount(): void
    {
        $this->update([
            'daily_sent'     => 0,
            'daily_reset_at' => now(),
        ]);
    }

    // ============================================================
    // INCREMENT SENT COUNT
    // Called after every successful send
    // ============================================================
    public function incrementSent(): void
    {
        $this->increment('daily_sent');
        $this->increment('total_sent');
        $this->update(['last_used_at' => now()]);
    }

    // ============================================================
    // TOKEN STATUS FOR UI
    // Returns: valid, expiring, critical, expired, unknown
    // ============================================================
    public function tokenStatus(): string
    {
        try {
            $token = json_decode($this->google_token, true);

            if (
                !isset($token['created']) ||
                !isset($token['expires_in'])
            ) {
                return 'unknown';
            }

            $expiresAt   = $token['created'] + $token['expires_in'];
            $minutesLeft = ($expiresAt - time()) / 60;

            if ($minutesLeft <= 0)   return 'expired';
            if ($minutesLeft <= 5)   return 'critical';
            if ($minutesLeft <= 20)  return 'expiring';

            return 'valid';

        } catch (\Exception $e) {
            return 'unknown';
        }
    }

    // ============================================================
    // TOKEN EXPIRY IN MINUTES
    // ============================================================
    public function tokenExpiresInMinutes(): int
    {
        try {
            $token     = json_decode($this->google_token, true);
            $expiresAt = ($token['created'] ?? 0)
                       + ($token['expires_in'] ?? 3600);

            return max(0, (int)(($expiresAt - time()) / 60));

        } catch (\Exception $e) {
            return 0;
        }
    }
}