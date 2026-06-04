<?php

namespace App\Services\Attendance;

use Illuminate\Support\Carbon;

class EventParser
{
    /**
     * Parse events for daily attendance status.
     * Returns ['status' => string, 'workTime' => ?string] or null when no status
     */
    public function parseDaily(array $items, string $role): ?array
    {
        $status = null;
        $workTime = null;

        foreach ($items as $event) {
            if (($event->eventType ?? null) === 'outOfOffice') {
                $status = $event->getSummary() ?: '不在';
                break;
            }

            if (($event->eventType ?? null) === 'workingLocation' && $role === 'インターン') {
                $type = $event->getWorkingLocationProperties()?->getType();
                $status = ($type === 'homeOffice') ? 'リモート' : '出社';

                $start = $event->getStart()?->getDateTime();
                $end = $event->getEnd()?->getDateTime();
                if ($start && $end) {
                    $startDt = Carbon::parse($start)->timezone('Asia/Tokyo');
                    $endDt = Carbon::parse($end)->timezone('Asia/Tokyo');
                    $workTime = $startDt->format('H:i') . '-' . $endDt->format('H:i');
                }
            }
        }

        if (! $status) {
            return null;
        }

        return ['status' => $status, 'workTime' => $workTime];
    }

    /**
     * Parse events for weekly schedules. Returns array of schedules with 'start' and 'text'.
     */
    public function parseWeekly(array $items, string $role): array
    {
        $schedules = [];

        foreach ($items as $event) {
            $startAt = $event->getStart()?->getDateTime();
            $endAt = $event->getEnd()?->getDateTime();
            if (! $startAt || ! $endAt) {
                continue;
            }

            $startDt = Carbon::parse($startAt)->timezone('Asia/Tokyo');
            $endDt = Carbon::parse($endAt)->timezone('Asia/Tokyo');
            $status = null;

            if (($event->eventType ?? null) === 'outOfOffice') {
                $status = $event->getSummary() ?: '不在';
            } elseif (($event->eventType ?? null) === 'workingLocation' && $role === 'インターン') {
                $status = ($event->getWorkingLocationProperties()?->getType() === 'homeOffice') ? 'リモート' : '出社';
            }

            if ($status) {
                $schedules[] = [
                    'start' => $startDt,
                    'text' => $startDt->translatedFormat('n/j(D) H:i-') . $endDt->format('H:i') . ' ' . $status,
                ];
            }
        }

        usort($schedules, fn ($a, $b) => $a['start'] <=> $b['start']);

        return $schedules;
    }
}
