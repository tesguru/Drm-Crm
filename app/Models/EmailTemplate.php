<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'category',
        'type',
        'subject_template',
        'body_template',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ============================================================
    // RELATIONSHIPS
    // ============================================================
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ============================================================
    // GET RANDOM TEMPLATE BY TYPE
    // Smart fallback — if followup_5 not found
    // tries followup_4, followup_3 etc until found
    // ============================================================
    public static function getRandomByType(
        int $userId,
        string $type,
        ?int $excludeNumber = null
    ): ?self {
        // Try exact type first
        $query = self::where('user_id', $userId)
                     ->where('type', $type)
                     ->where('is_active', true);

        if ($excludeNumber) {
            $query->where('id', '!=', $excludeNumber);
        }

        $template = $query->inRandomOrder()->first();

        // If found return it immediately
        if ($template) return $template;

        // Fallback for follow-ups
        // Try previous levels going down
        if (str_starts_with($type, 'followup_')) {
            $level = (int) str_replace('followup_', '', $type);

            while ($level > 0) {
                $level--;

                $fallback = self::where('user_id', $userId)
                                ->where('type', 'followup_' . $level)
                                ->where('is_active', true)
                                ->when($excludeNumber, function($q) use ($excludeNumber) {
                                    $q->where('id', '!=', $excludeNumber);
                                })
                                ->inRandomOrder()
                                ->first();

                if ($fallback) return $fallback;
            }
        }

        return null;
    }

    // ============================================================
    // PERSONALIZE TEMPLATE WITH VARIABLES
    // Supports: {company} {domain} {price} {firstName} {yourName}
    // ============================================================
    public function personalize(array $variables): array
    {
        $keys = [
            '{company}',
            '{domain}',
            '{price}',
            '{firstName}',
            '{yourName}',
        ];

        $values = [
            $variables['company']   ?? '',
            $variables['domain']    ?? '',
            $variables['price']     ?? '',
            $variables['firstName'] ?? '',
            $variables['yourName']  ?? '',
        ];

        return [
            'subject' => str_replace(
                $keys,
                $values,
                $this->subject_template
            ),
            'body' => str_replace(
                $keys,
                $values,
                $this->body_template
            ),
        ];
    }
}