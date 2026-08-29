<?php

namespace App\Services\Attendance;

use App\Repositories\CalendarRepository;
use App\Services\Calendar\GoogleCalendarService;
use App\Services\Slack\MentionBuilder;
use App\Services\Slack\SlackNotifier;
use Illuminate\Support\Carbon;
use Throwable;

class WeeklyReportNotifier
{
    public function __construct(
        protected CalendarRepository $calendarRepo,
        protected GoogleCalendarService $googleService,
        protected EventParser $parser,
        protected SlackNotifier $slack,
        protected MentionBuilder $mentionBuilder
    ) {
    }

    public function notifyWeekly(?Carbon $start = null): void
    {
        $start = $start ?? Carbon::now()->next(Carbon::SATURDAY)->startOfDay();
        $end = $start->copy()->addDays(6)->endOfDay();

        $calendars = $this->calendarRepo->getActiveOrdered();
        if ($calendars->isEmpty()) {
            return;
        }

        $members = [];
        foreach ($calendars as $calendar) {
            try {
                $events = $this->googleService->getEventsForRange($calendar->calendar_id, $start, $end);
                $items = $events->getItems() ?? [];
                $schedules = $this->parser->parseWeekly($items, $calendar->role);

                if (count($schedules) > 0) {
                    $members[] = [
                        'name' => $calendar->user_name,
                        'role' => $calendar->role,
                        'schedules' => $schedules,
                    ];
                }
            } catch (Throwable) {
                // Ignore failures
            }
        }

        $this->slack->sendText(view('slack.weekly_report', [
            'start' => $start,
            'roles' => ['インターン', '社員'],
            'groupedMembers' => collect($members)->groupBy('role'),
            'mentionLine' => $this->mentionBuilder->build(),
        ])->render());
    }
}
