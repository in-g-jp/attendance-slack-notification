<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class NotifyWeeklyReminder extends Command
{
    protected $signature = 'app:notify-weekly-reminder';

    protected $description = '来週の予定登録を促すSlack通知を送信する';

    public function handle(): int
    {
        $webhookUrl = config('services.slack.webhook_url');

        if (empty($webhookUrl)) {
            $this->error('SLACK_WEBHOOK_URL が設定されていません。');
            return self::FAILURE;
        }

        $response = Http::post($webhookUrl, [
            'text' => "<!channel>\n来週の予定をGoogle Calenderに登録してください。"
        ]);

        if ($response->successful()) {
            $this->info('Slackへリマインドを通知しました。');
            return self::SUCCESS;
        }

        $this->error("Slack通知に失敗しました (HTTP {$response->status()})。");
        return self::FAILURE;
    }
}
