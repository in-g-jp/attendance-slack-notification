<?php

namespace App\Console\Commands;

use App\Services\Attendance\WeeklyReportNotifier;
use Illuminate\Console\Command;

class NotifyWeeklyReport extends Command
{
    protected $signature = 'app:notify-weekly-report';

    public function handle(WeeklyReportNotifier $notifier): int
    {
        $notifier->notifyWeekly();
        return self::SUCCESS;
    }
}
