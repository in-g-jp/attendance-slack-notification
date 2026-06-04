<?php

namespace App\Services\Attendance;

use App\Repositories\CalendarRepository;
use App\Services\Calendar\GoogleCalendarService;
use App\Services\Slack\SlackNotifier;
use Illuminate\Support\Carbon;
use Throwable;

class AttendanceNotifier
{
    public function __construct(
        protected CalendarRepository $calendarRepo,
        protected GoogleCalendarService $googleService,
        protected EventParser $parser,
        protected SlackNotifier $slack
    ) {
    }

    public function notifyDaily(?Carbon $today = null): void
    {
        $today = $today?->timezone('Asia/Tokyo') ?? now()->timezone('Asia/Tokyo');
        $calendars = $this->calendarRepo->getActiveOrdered();

        if ($calendars->isEmpty()) {
            return;
        }

        $members = [];
        foreach ($calendars as $calendar) {
            try {
                $events = $this->googleService->getEventsForRange(
                    $calendar->calendar_id,
                    $today->copy()->startOfDay(),
                    $today->copy()->endOfDay()
                );

                $items = $events->getItems() ?? [];
                $parsed = $this->parser->parseDaily($items, $calendar->role);
                if ($parsed) {
                    $members[] = [
                        'name' => $calendar->user_name,
                        'role' => $calendar->role,
                        'status' => $parsed['status'],
                        'workTime' => $parsed['workTime'],
                    ];
                }
            } catch (Throwable) {
                // ignore individual failures
            }
        }

        $parsedMembers = collect($members);

        $text = view('slack.attendance', [
            'today' => $today,
            'dateLine' => $today->format('Y年n月j日') . '(' . ['日', '月', '火', '水', '木', '金', '土'][$today->dayOfWeek] . ')',
            'roles' => ['インターン', '社員'],
            'parsedMembers' => $parsedMembers,
            'groupedMembers' => $parsedMembers->groupBy('role'),
        ])->render();

        $this->slack->sendText($text);
    }
}
