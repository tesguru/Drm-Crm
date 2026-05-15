<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AppScriptMailer
{
    protected string $url;
    protected string $secret;

    public function __construct(string $url)
    {
        $this->url    = $url;
        $this->secret = config('services.appscript.secret');
    }

    public function send(
        string $to,
        string $subject,
        string $body,
        string $senderName = 'Your Name'
    ): array {
        try {
            $response = Http::timeout(30)->post($this->url, [
                'secret'     => $this->secret,
                'to'         => $to,
                'subject'    => $subject,
                'body'       => $body,
                'senderName' => $senderName,
            ]);

            $result = $response->json();

            Log::info('AppScript send result', [
                'to'     => $to,
                'result' => $result,
            ]);

            return [
                'success'    => $result['success']   ?? false,
                'thread_id'  => $result['threadId']  ?? null,
                'message_id' => $result['messageId'] ?? null,
                'error'      => $result['error']      ?? null,
            ];

        } catch (\Exception $e) {
            Log::error('AppScript HTTP error', [
                'url'   => $this->url,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }
}