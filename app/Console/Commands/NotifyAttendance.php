<?php

namespace App\Console\Commands;

use App\Services\Attendance\AttendanceNotifier;
use Illuminate\Console\Command;

class NotifyAttendance extends Command
{
    protected $signature = 'app:notify-attendance';

    public function handle(AttendanceNotifier $notifier): int
    {
        $notifier->notifyDaily();
        return self::SUCCESS;
    }
}
