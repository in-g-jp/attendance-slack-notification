<?php

namespace App\Services\Slack;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class SlackNotifier
{
    public function sendText(string $text): void
    {
        $webhookUrl = config('services.slack.webhook_url');
        if (! $webhookUrl) {
            return;
        }

        if (trim($text) === '') {
            return;
        }

        Http::post($webhookUrl, ['text' => $text]);
    }
}
