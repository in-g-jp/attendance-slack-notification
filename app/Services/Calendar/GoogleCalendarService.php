<?php

namespace App\Services\Calendar;

use App\Services\GoogleApiService;
use Illuminate\Support\Carbon;

class GoogleCalendarService
{
    private GoogleApiService $api;

    public function __construct(GoogleApiService $api)
    {
        $this->api = $api;
    }

    public function getEventsForRange(string $calendarId, Carbon $start, Carbon $end)
    {
        return $this->api->getEvents($calendarId, $start, $end);
    }
}
