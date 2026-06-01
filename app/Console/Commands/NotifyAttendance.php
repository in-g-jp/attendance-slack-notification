<?php

namespace App\Console\Commands;

use App\Models\Calendar;
use App\Services\GoogleApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class NotifyAttendance extends Command
{
    protected $signature = 'app:notify-attendance';

    public function handle(GoogleApiService $service): int
    {
        $calendars = Calendar::where('is_active', true)->orderBy('role')->orderBy('user_name')->get();
        $webhookUrl = config('services.slack.webhook_url');

        if ($calendars->isEmpty() || !$webhookUrl) {
            return self::SUCCESS;
        }

        $today = now()->timezone('Asia/Tokyo');
        $members = $calendars->map(function ($calendar) use ($service, $today) {
            try {
                return [
                    'name' => $calendar->user_name,
                    'role' => $calendar->role,
                    'events' => $service->getEvents($calendar->calendar_id, $today->copy()->startOfDay(), $today->copy()->endOfDay())->getItems(),
                ];
            } catch (Throwable) {
                return null;
            }
        })->filter();

        $parsedMembers = $members->map(function ($member) {
            $status = null;
            $workTime = null;

            foreach ($member['events'] as $event) {
                if ($event->eventType === 'outOfOffice') {
                    $status = $event->getSummary() ?: '不在';
                    break;
                }

                if ($event->eventType === 'workingLocation' && $member['role'] === 'インターン') {
                    $type = $event->getWorkingLocationProperties()?->getType();
                    $status = ($type === 'homeOffice') ? 'リモート' : '出社';

                    $start = $event->getStart()?->getDateTime();
                    $end = $event->getEnd()?->getDateTime();
                    if ($start && $end) {
                        $startDt = \Illuminate\Support\Carbon::parse($start)->timezone('Asia/Tokyo');
                        $endDt = \Illuminate\Support\Carbon::parse($end)->timezone('Asia/Tokyo');
                        $workTime = $startDt->format('H:i') . '-' . $endDt->format('H:i');
                    }
                }
            }

            if (! $status) {
                return null;
            }

            return [
                'name' => $member['name'],
                'role' => $member['role'],
                'status' => $status,
                'workTime' => $workTime,
            ];
        })->filter()->values();

        $text = view('slack.attendance', [
            'today' => $today,
            'dateLine' => $today->format('Y年n月j日') . '(' . ['日', '月', '火', '水', '木', '金', '土'][$today->dayOfWeek] . ')',
            'roles' => ['インターン', '社員'],
            'parsedMembers' => $parsedMembers,
            'groupedMembers' => $parsedMembers->groupBy('role'),
        ])->render();

        if (trim($text)) {
            Http::post($webhookUrl, ['text' => $text]);
        }

        return self::SUCCESS;
    }
}
