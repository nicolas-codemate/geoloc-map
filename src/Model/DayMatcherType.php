<?php

declare(strict_types=1);

namespace App\Model;

enum DayMatcherType
{
    case DayOfWeek;
    case FixedMonthDay;
    case FullDate;
    case Holiday;
    case AllFrenchHolidays;
}
