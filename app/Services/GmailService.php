<?php

namespace App\Services;

use App\Models\GmailAccount;
use Google\Client as GoogleClient;
use Google\Service\Gmail;
use Google\Service\Gmail\Label;
use Google\Service\Gmail\Message;
use Illuminate\Support\Facades\Log;

class GmailService
{
    protected GoogleClient $client;
    protected Gmail $gmail;
    protected GmailAccount $account;

    public function __construct(GmailAccount $account)
    {
        $this->account = $account;
        $this->client  = $this->buildClient();
        $this->gmail   = new Gmail($this->client);
    }

    // ============================================================
    // BUILD CLIENT + AUTO REFRESH TOKEN
    // ============================================================
    protected function buildClient(): GoogleClient
    {
        $client = new GoogleClient();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));

        $token = json_decode($this->account->google_token, true);
        $client->setAccessToken($token);

        if ($client->isAccessTokenExpired()) {
            if ($this->account->google_refresh_token) {
                $newToken = $client->fetchAccessTokenWithRefreshToken(
                    $this->account->google_refresh_token
                );

                $this->account->update([
                    'google_token' => json_encode(array_merge(
                        $newToken,
                        ['created' => time()]
                    ))
                ]);

                $client->setAccessToken($newToken);
            }
        }

        return $client;
    }

    // ============================================================
    // SEND INITIAL EMAIL  (mirrors Elixir send_email + prepare_email)
    // ============================================================
    public function sendEmail(
        string $to,
        string $subject,
        string $body,
        ?string $labelId = null,
        bool $html = false
    ): array {
        try {
            $this->refreshIfExpired();
        $cleanSubject = self::cleanContent($subject);
        $cleanBody    = self::cleanContent($body);
            $raw = $this->buildRawMessage(
                from:       $this->account->email,
                to:         $to,
                subject:    $subject,
                body:       $body,
                senderName: $this->getSenderName(),
                html:       $html,
            );

            $message = new Message();
            $message->setRaw($raw);

            $sent = $this->gmail->users_messages->send('me', $message);

            if ($labelId && $sent->getThreadId()) {
                $this->applyLabelToThread($sent->getThreadId(), $labelId);
            }

            $this->account->incrementSent();

            return [
                'success'    => true,
                'message_id' => $sent->getId(),
                'thread_id'  => $sent->getThreadId(),
            ];

        } catch (\Exception $e) {
            Log::error('Gmail send failed', [
                'account' => $this->account->email,
                'to'      => $to,
                'error'   => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    // ============================================================
    // SEND FOLLOW-UP  (mirrors Elixir send_follow_up + do_send_email)
    // ============================================================
    public function sendFollowUp(
        string $to,
        string $originalSubject,
        string $body,
        string $threadId,
        string $originalMessageId,
        ?string $labelId = null,
        bool $html = false
    ): array {
        try {
            $this->refreshIfExpired();

            // Step 1: try real Message-ID from the original message
            // mirrors Elixir: get_message_headers → extract_message_id
            $realMessageId = $this->getMessageIdFromMessage($originalMessageId);
      $cleanSubject = self::cleanContent($originalSubject);
        $cleanBody    = self::cleanContent($body);
            if (!$realMessageId) {
                $realMessageId = $this->getMessageIdFromThread($threadId);
            }

            if (!$realMessageId) {
                return [
                    'success' => false,
                    'error'   => 'Could not resolve any valid Message-ID',
                ];
            }

            $raw = $this->buildRawMessage(
                from:       $this->account->email,
                to:         $to,
                subject:    $originalSubject,
                body:       $body,
                senderName: $this->getSenderName(),
                inReplyTo:  $realMessageId,
                references: $realMessageId,
                html:       $html,
            );

            $message = new Message();
            $message->setRaw($raw);
            $message->setThreadId($threadId);

            $sent = $this->gmail->users_messages->send('me', $message);

            if ($labelId && $sent->getThreadId()) {
                $this->applyLabelToThread($sent->getThreadId(), $labelId);
            }

            $this->account->incrementSent();

            return [
                'success'    => true,
                'message_id' => $sent->getId(),
                'thread_id'  => $sent->getThreadId(),
            ];

        } catch (\Exception $e) {
            Log::error('Gmail follow-up failed', [
                'account'   => $this->account->email,
                'to'        => $to,
                'thread_id' => $threadId,
                'error'     => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    // ============================================================
    // CHECK IF THREAD HAS REPLY FROM RECIPIENT
    // ============================================================
    public function threadHasReply(
        string $threadId,
        string $recipientEmail
    ): bool {
        try {
            $this->refreshIfExpired();

            $thread   = $this->gmail->users_threads->get('me', $threadId);
            $messages = $thread->getMessages();

            foreach ($messages as $message) {
                $headers = $message->getPayload()->getHeaders();

                foreach ($headers as $header) {
                    if (
                        strtolower($header->getName()) === 'from' &&
                        str_contains(
                            strtolower($header->getValue()),
                            strtolower($recipientEmail)
                        )
                    ) {
                        return true;
                    }
                }
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Thread reply check failed', [
                'thread_id' => $threadId,
                'error'     => $e->getMessage(),
            ]);
            return false;
        }
    }

    // ============================================================
    // CREATE OR GET GMAIL LABEL
    // ============================================================
    public function getOrCreateLabel(string $labelName): array
    {
        try {
            $this->refreshIfExpired();

            $labels = $this->gmail->users_labels->listUsersLabels('me');

            foreach ($labels->getLabels() as $label) {
                if (strtolower($label->getName()) === strtolower($labelName)) {
                    return [
                        'success'  => true,
                        'label_id' => $label->getId(),
                    ];
                }
            }

            $label = new Label();
            $label->setName($labelName);
            $label->setLabelListVisibility('labelShow');
            $label->setMessageListVisibility('show');

            $created = $this->gmail->users_labels->create('me', $label);

            return [
                'success'  => true,
                'label_id' => $created->getId(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    // ============================================================
    // APPLY LABEL TO THREAD
    // ============================================================
    public function applyLabelToThread(
        string $threadId,
        string $labelId
    ): void {
        try {
            $request = new \Google\Service\Gmail\ModifyThreadRequest();
            $request->setAddLabelIds([$labelId]);
            $this->gmail->users_threads->modify('me', $threadId, $request);
        } catch (\Exception $e) {
            Log::warning('Label apply failed', [
                'thread_id' => $threadId,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    // ============================================================
    // DELETE GMAIL LABEL
    // ============================================================
    public function deleteLabel(string $labelName): array
    {
        try {
            $this->refreshIfExpired();

            $labels = $this->gmail->users_labels->listUsersLabels('me');

            foreach ($labels->getLabels() as $label) {
                if (strtolower($label->getName()) === strtolower($labelName)) {
                    $this->gmail->users_labels->delete('me', $label->getId());
                    return ['success' => true];
                }
            }

            return [
                'success' => false,
                'error'   => 'Label not found',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    // ============================================================
    // GET MESSAGE BY ID
    // ============================================================
    public function getMessage(string $messageId): array
    {
        try {
            $this->refreshIfExpired();

            $message = $this->gmail->users_messages->get(
                'me',
                $messageId,
                [
                    'format'          => 'metadata',
                    'metadataHeaders' => ['Message-ID'],
                ]
            );

            $realMessageId = null;

            foreach ($message->getPayload()->getHeaders() as $header) {
                if (strtolower($header->getName()) === 'message-id') {
                    $realMessageId = $header->getValue();
                    break;
                }
            }

            return [
                'success'    => true,
                'message_id' => $realMessageId,
                'thread_id'  => $message->getThreadId(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    // ============================================================
    // EXTRACT NAMES FROM EMAIL ADDRESS
    // ============================================================
   public static function extractNamesFromEmail(
    string $email,
    ?string $domainFallback = null  // Keep parameter but don't use it
): array {
    $parts = explode('@', strtolower($email));
    $localPart = $parts[0];
    $domain = $parts[1] ?? '';
    
    // Free email providers list
    $freeDomains = [
        'gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com', 
        'aol.com', 'icloud.com', 'protonmail.com', 'zoho.com', 
        'yandex.com', 'mail.com'
    ];
    
    // Clean the local part (username) to extract first name
    $name = str_replace(['.', '_', '-', '+'], ' ', $localPart);
    $name = preg_replace('/[0-9]+/', '', $name);
    $name = trim(preg_replace('/\s+/', ' ', $name));
    
    $nameParts = explode(' ', $name);
    $firstName = ucfirst(strtolower($nameParts[0] ?? ''));
    
    // Determine company name based on email type
    $isFreeEmail = in_array($domain, $freeDomains);
    
    if ($isFreeEmail) {
        // For free emails (Gmail, etc.): use username as company name
        $cleanUsername = preg_replace('/[0-9._-]+/', ' ', $localPart);
        $company = ucwords(strtolower(trim($cleanUsername ?: $localPart)));
    } else {
        // ✅ CHANGED: Always use the actual domain from email, ignore fallback
        $domainParts = explode('.', $domain);
        $company = ucwords(str_replace(['-', '_'], ' ', $domainParts[0] ?? ''));
    }
    
    // Fallbacks
    if (!$firstName || strlen($firstName) < 2) {
        $firstName = 'there';
    }
    
    if (!$company) {
        $company = 'Your Company';
    }
    
    return [
        'first_name' => $firstName,
        'company_name' => $company,
    ];
}

   
    protected function refreshIfExpired(): void
    {
        if ($this->client->isAccessTokenExpired()) {
            if ($this->account->google_refresh_token) {
                $newToken = $this->client->fetchAccessTokenWithRefreshToken(
                    $this->account->google_refresh_token
                );

                $this->account->update([
                    'google_token' => json_encode(array_merge(
                        $newToken,
                        ['created' => time()]
                    ))
                ]);

                $this->client->setAccessToken($newToken);
            }
        }
    }

    // ============================================================
    // GET SENDER NAME  (mirrors Elixir's get_sender_name/1)
    // Elixir does Repo.get_by(User, email: email) because it has no
    // account object in scope. We already have $this->account injected
    // with name "Janet Aliya" on it — use it directly, no extra query.
    // Falls back to "Your Name" exactly like Elixir.
    // ============================================================
    protected function getSenderName(): string
    {
        $name = $this->account->name ?? null;

        return (is_string($name) && !empty($name)) ? $name : 'Your Name';
    }

    // ============================================================
    // BUILD RAW EMAIL MESSAGE  (mirrors Elixir prepare_email + do_send_email)
    // Plain subject, no Content-Transfer-Encoding, url-safe base64
    // with no padding — exactly like Elixir.
    // ============================================================
    protected function buildRawMessage(
        string $from,
        string $to,
        string $subject,
        string $body,
        string $senderName,
        ?string $inReplyTo  = null,
        ?string $references = null,
        bool $html = false
    ): string {
        // Produces: "Janet Aliya" <janetaliyainc@gmail.com>
        $fromHeader  = "\"{$senderName}\" <{$from}>";
        $contentType = $html
            ? 'text/html; charset="UTF-8"'
            : 'text/plain; charset="UTF-8"';

        // Re: prefix only on follow-ups
        $finalSubject = $inReplyTo
            ? (str_starts_with($subject, 'Re: ') ? $subject : 'Re: ' . $subject)
            : $subject;

        $mime  = "From: {$fromHeader}\r\n";
        $mime .= "To: {$to}\r\n";
        $mime .= "Subject: {$finalSubject}\r\n";

        if ($inReplyTo) {
            $mime .= "In-Reply-To: <{$inReplyTo}>\r\n";
        }

        if ($references) {
            $mime .= "References: <{$references}>\r\n";
        }

        $mime .= "MIME-Version: 1.0\r\n";
        $mime .= "Content-Type: {$contentType}\r\n";
        $mime .= "\r\n";
        $mime .= $body;

        // url-safe base64, no padding — exactly like Elixir
        return rtrim(strtr(base64_encode($mime), '+/', '-_'), '=');
    }

    // ============================================================
    // GET REAL Message-ID FROM A SPECIFIC MESSAGE
    // (mirrors Elixir's get_message_headers + extract_message_id)
    // ============================================================
    protected function getMessageIdFromMessage(string $gmailMessageId): ?string
    {
        try {
            $message = $this->gmail->users_messages->get(
                'me',
                $gmailMessageId,
                [
                    'format'          => 'metadata',
                    'metadataHeaders' => ['Message-ID'],
                ]
            );

            foreach ($message->getPayload()->getHeaders() as $header) {
                if (strtolower($header->getName()) === 'message-id') {
                    // strip angle brackets like Elixir's extract_message_id
                    return trim($header->getValue(), '<>');
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::warning('Could not fetch message headers', [
                'message_id' => $gmailMessageId,
                'error'      => $e->getMessage(),
            ]);
            return null;
        }
    }

    // ============================================================
    // GET REAL Message-ID FROM FIRST MESSAGE IN THREAD
    // (mirrors Elixir's get_thread_message_id fallback)
    // ============================================================
    protected function getMessageIdFromThread(string $threadId): ?string
    {
        try {
            $thread   = $this->gmail->users_threads->get('me', $threadId, [
                'format'          => 'metadata',
                'metadataHeaders' => ['Message-ID'],
            ]);
            $messages = $thread->getMessages();

            if (empty($messages)) {
                return null;
            }

            foreach ($messages[0]->getPayload()->getHeaders() as $header) {
                if (strtolower($header->getName()) === 'message-id') {
                    return trim($header->getValue(), '<>');
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::warning('Could not fetch thread message ID', [
                'thread_id' => $threadId,
                'error'     => $e->getMessage(),
            ]);
            return null;
        }
    }

   
public static function cleanContent(string $text): string
{
    // ✅ FIX ENCODING ISSUES FIRST (Added this)
    // Fix common UTF-8 corruption issues
    $text = mb_convert_encoding($text, 'UTF-8', 'auto');
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    // Fix corrupted dashes and special characters
    $corruptions = [
        'Ã¢Â€Â”' => '—',  // Em dash
        'Ã¢Â€Â™' => "'",  // Apostrophe
        'Ã¢Â€Âœ' => '"',  // Left double quote
        'Ã¢Â€Â' => '"',  // Right double quote
        'Ã¢Â€Â“' => '-',  // En dash
        'Ã¢Â€Â¦' => '…',  // Ellipsis
        'ÃƒÂ©' => 'é',    // e with accent
        'ÃƒÂ±' => 'ñ',    // n with tilde
        'Ã¢Â€Â°' => '‰',  // Per mille
        'Ã¢Â€Â¢' => '•',  // Bullet point
    ];
    
    foreach ($corruptions as $corrupted => $fixed) {
        $text = str_replace($corrupted, $fixed, $text);
    }
    
    // Optional: Convert smart quotes and dashes to standard ASCII if needed
    $smartChars = [
        '—' => '-',   // Em dash to hyphen
        '–' => '-',   // En dash to hyphen
        '"' => '"',    // Keep as is
        '"' => '"',    // Keep as is
        '' => "'",     // Left single quote
        '' => "'",     // Right single quote
        '…' => '...',  // Ellipsis to three dots
    ];
    
    // Uncomment if you want standard ASCII only
    // foreach ($smartChars as $smart => $standard) {
    //     $text = str_replace($smart, $standard, $text);
    // }
    
    // Remove multiple spaces
    $text = preg_replace('/\s+/', ' ', $text);

    // Remove multiple blank lines
    $text = preg_replace('/\n{3,}/', "\n\n", $text);

    // Remove spam trigger characters
    $text = str_replace([
        '!!!',
        '???',
        '##',
        '$$',
        '%%',
        '**',
        '==',
        '>>',
    ], '', $text);

    // Remove common spam words
    $spamWords = [
        'click here',
        'buy now',
        'free offer',
        'limited time',
        'act now',
        'guaranteed',
        'no obligation',
        'winner',
        'congratulations',
        'earn money',
        'make money',
        'cash',
        'discount',
        'urgent',
        '100% free',
    ];

    foreach ($spamWords as $word) {
        $text = str_ireplace(
            $word,
            '',
            $text
        );
    }

    // Remove extra whitespace from cleanup
    $text = preg_replace('/\s+/', ' ', $text);
    $text = preg_replace('/\n\s+\n/', "\n\n", $text);

    return trim($text);
}

public function checkBounces(): array
{
    try {
        $this->refreshIfExpired();

        // ✅ Comprehensive bounce detection query
        $results = $this->gmail->users_messages->listUsersMessages('me', [
            'q' => implode(' OR ', [
                'from:mailer-daemon',
                'from:postmaster',
                'from:Mail_Delivery_Subsystem',
                'subject:"Delivery Status Notification"',
                'subject:"Mail delivery failed"',
                'subject:"Undeliverable"',
                'subject:"Delivery Failure"',
                'subject:"Failed to deliver"',
                'subject:"returned mail"',
                'subject:"delivery failed"',
                'subject:"Unable to deliver"',
                'subject:"Mail System Error"',
                'subject:"Returned mail"',
            ]) . ' newer_than:7d',
            'maxResults' => 200,
        ]);

        $messages = $results->getMessages() ?? [];
        $bounced  = [];

        foreach ($messages as $msg) {
            $full = $this->gmail->users_messages->get(
                'me',
                $msg->getId(),
                [
                    'format'          => 'full',
                    'metadataHeaders' => ['Subject', 'From', 'To'],
                ]
            );

            $headers = $full->getPayload()->getHeaders();
            $subject = '';
            $from    = '';

            foreach ($headers as $header) {
                $name = strtolower($header->getName());
                if ($name === 'subject') $subject = $header->getValue();
                if ($name === 'from')    $from    = $header->getValue();
            }

            // ✅ Extract bounced email from subject
            $extracted = $this->extractBouncedEmails($subject, $from);
            $bounced   = array_merge($bounced, $extracted);

            // ✅ Also check email body for bounced address
            $body = $this->getEmailBody($full);
            if ($body) {
                $fromBody = $this->extractBouncedEmails($body, '');
                $bounced  = array_merge($bounced, $fromBody);
            }
        }

        // Remove our own email from list
        $bounced = array_filter($bounced, function($email) {
            return $email !== strtolower($this->account->email);
        });

        return array_values(array_unique($bounced));

    } catch (\Exception $e) {
        Log::error('Bounce check failed', [
            'account' => $this->account->email,
            'error'   => $e->getMessage(),
        ]);
        return [];
    }
}

// ============================================================
// EXTRACT BOUNCED EMAILS FROM TEXT
// ============================================================
private function extractBouncedEmails(string $text, string $from): array
{
    $bounced  = [];
    $ourEmail = strtolower($this->account->email);

    // Common bounce subject patterns
    $patterns = [
        // "Delivery failed for user@domain.com"
        '/(?:failed|failure|undeliverable|returned|bounced)\s+(?:to|for|message to)?\s*([\w.+-]+@[\w-]+\.[\w.]+)/i',
        
        // "user@domain.com: User unknown"
        '/([\w.+-]+@[\w-]+\.[\w.]+)\s*[:\-]\s*(?:user unknown|no such user|invalid|does not exist|mailbox full)/i',
        
        // Any email in bounce context
        '/(?:recipient|address|email).*?([\w.+-]+@[\w-]+\.[\w.]+)/i',
        
        // Fallback — any email in text
        '/([\w.+-]+@[\w-]+\.[\w.]+)/i',
    ];

    foreach ($patterns as $pattern) {
        if (preg_match_all($pattern, $text, $matches)) {
            foreach ($matches[1] as $email) {
                $email = strtolower(trim($email));
                // Skip our own email and common system emails
                if ($email !== $ourEmail &&
                    !str_contains($email, 'mailer-daemon') &&
                    !str_contains($email, 'postmaster') &&
                    !str_contains($email, 'noreply') &&
                    !str_contains($email, 'no-reply')) {
                    $bounced[] = $email;
                }
            }
        }
    }

    return $bounced;
}

// ============================================================
// GET EMAIL BODY TEXT
// ============================================================
private function getEmailBody($message): ?string
{
    try {
        $payload = $message->getPayload();
        
        // Try direct body first
        $body = $payload->getBody()->getData();
        if ($body) {
            return base64_decode(strtr($body, '-_', '+/'));
        }

        // Try parts
        $parts = $payload->getParts() ?? [];
        foreach ($parts as $part) {
            if ($part->getMimeType() === 'text/plain') {
                $data = $part->getBody()->getData();
                if ($data) {
                    return base64_decode(strtr($data, '-_', '+/'));
                }
            }
        }

        return null;
    } catch (\Exception $e) {
        return null;
    }
}


public function threadHasBounce(string $threadId): bool
{
    try {
        $this->refreshIfExpired();

        $thread   = $this->gmail->users_threads->get('me', $threadId);
        $messages = $thread->getMessages();

        foreach ($messages as $message) {
            $headers = $message->getPayload()->getHeaders();

            foreach ($headers as $header) {
                $name  = strtolower($header->getName());
                $value = strtolower($header->getValue());

                // Check FROM header for bounce senders
                if ($name === 'from') {
                    if (
                        str_contains($value, 'mailer-daemon') ||
                        str_contains($value, 'postmaster')    ||
                        str_contains($value, 'mail delivery') ||
                        str_contains($value, 'delivery subsystem')
                    ) {
                        Log::info('Bounce sender detected', [
                            'thread_id' => $threadId,
                            'from'      => $value,
                        ]);
                        return true;
                    }
                }

                // Check SUBJECT header for bounce keywords
                if ($name === 'subject') {
                    if (
                        str_contains($value, 'delivery failed')              ||
                        str_contains($value, 'undeliverable')                ||
                        str_contains($value, 'mail delivery failed')         ||
                        str_contains($value, 'delivery status notification') ||
                        str_contains($value, 'returned mail')                ||
                        str_contains($value, 'failed to deliver')            ||
                        str_contains($value, 'unable to deliver')
                    ) {
                        Log::info('Bounce subject detected', [
                            'thread_id' => $threadId,
                            'subject'   => $value,
                        ]);
                        return true;
                    }
                }
            }
        }

        return false;

    } catch (\Exception $e) {
        Log::error('threadHasBounce failed', [
            'thread_id' => $threadId,
            'error'     => $e->getMessage(),
        ]);
        return false;
    }
}


}