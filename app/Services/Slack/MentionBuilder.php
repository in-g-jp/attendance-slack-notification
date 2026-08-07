<?php

namespace App\Services\Slack;

use Illuminate\Support\Facades\Log;

class MentionBuilder
{
    /**
     * Build the Slack mention line from SLACK_MENTION_USER_ID.
     * Returns an empty string when no user is configured.
     */
    public function build(): string
    {
        $userId = trim((string) config('services.slack.mention_user_id'));

        if ($userId === '') {
            return '';
        }

        // The mention is rendered unescaped in the Blade views, so only accept
        // the Slack member ID format (e.g. U01ABCDE23).
        if (! preg_match('/\A[A-Z0-9]+\z/', $userId)) {
            Log::warning('SLACK_MENTION_USER_ID is not a valid Slack member ID. Skipping mention.', [
                'slack_mention_user_id' => $userId,
            ]);

            return '';
        }

        return '<@' . $userId . '>';
    }
}
