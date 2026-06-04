<?php

namespace App\Repositories;

use App\Models\Calendar;
use Illuminate\Support\Collection;

class CalendarRepository
{
    public function getActiveOrdered(): Collection
    {
        return Calendar::where('is_active', true)
            ->orderBy('role')
            ->orderBy('user_name')
            ->get();
    }
}
