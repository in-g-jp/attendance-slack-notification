<?php

namespace App\Console\Commands;

use App\Models\Calendar;
use App\Services\GoogleApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Throwable;

class NotifyWeeklyReport extends Command
{
    protected $signature = 'app:notify-weekly-report';

    public function handle(GoogleApiService $service): int
    {
        $calendars = Calendar::where('is_active', true)
            ->orderBy('role')
            ->orderBy('user_name')
            ->get();

        $webhookUrl = config('services.slack.webhook_url');
        if ($calendars->isEmpty() || empty($webhookUrl)) {
            return self::SUCCESS;
        }

        $start = Carbon::now()->next(Carbon::SATURDAY)->startOfDay();
        $end = $start->copy()->addDays(6)->endOfDay();

        $members = [];
        foreach ($calendars as $calendar) {
            try {
                $events = $service->getEvents($calendar->calendar_id, $start, $end);
                $schedules = [];

                foreach ($events->getItems() as $event) {
                    $startAt = $event->getStart()?->getDateTime();
                    $endAt = $event->getEnd()?->getDateTime();
                    if (! $startAt || ! $endAt) {
                        continue;
                    }

                    $startDt = \Illuminate\Support\Carbon::parse($startAt)->timezone('Asia/Tokyo');
                    $endDt = \Illuminate\Support\Carbon::parse($endAt)->timezone('Asia/Tokyo');
                    $status = null;

                    if ($event->eventType === 'outOfOffice') {
                        $status = $event->getSummary() ?: '不在';
                    } elseif ($event->eventType === 'workingLocation' && $calendar->role === 'インターン') {
                        $status = ($event->getWorkingLocationProperties()?->getType() === 'homeOffice') ? 'リモート' : '出社';
                    }

                    if ($status) {
                        $schedules[] = [
                            'start' => $startDt,
                            'text' => $startDt->translatedFormat('n/j(D) H:i-') . $endDt->format('H:i') . ' ' . $status,
                        ];
                    }
                }

                if (count($schedules) > 0) {
                    usort($schedules, fn ($a, $b) => $a['start'] <=> $b['start']);
                    $members[] = [
                        'name' => $calendar->user_name,
                        'role' => $calendar->role,
                        'schedules' => $schedules,
                    ];
                }
            } catch (Throwable $e) {
                // Ignore failures
            }
        }

        Http::post($webhookUrl, [
            'text' => view('slack.weekly_report', [
                'members' => $members,
                'start' => $start,
                'roles' => ['インターン', '社員'],
                'groupedMembers' => collect($members)->groupBy('role'),
            ])->render()
        ]);

        return self::SUCCESS;
    }
}
