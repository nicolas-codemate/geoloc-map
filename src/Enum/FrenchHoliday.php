<?php

declare(strict_types=1);

namespace App\Enum;

use DateTimeImmutable;

enum FrenchHoliday: string
{
    case NEW_YEAR = 'new_year';
    case EASTER_MONDAY = 'easter_monday';
    case LABOR_DAY = 'labor_day';
    case VICTORY_DAY = 'victory_day';
    case ASCENSION = 'ascension';
    case WHIT_MONDAY = 'whit_monday';
    case BASTILLE_DAY = 'bastille_day';
    case ASSUMPTION = 'assumption';
    case ALL_SAINTS = 'all_saints';
    case ARMISTICE = 'armistice';
    case CHRISTMAS = 'christmas';

    public function getKeyword(): string
    {
        return $this->value;
    }

    public function isFixedDate(): bool
    {
        return match ($this) {
            self::EASTER_MONDAY, self::ASCENSION, self::WHIT_MONDAY => false,
            default => true,
        };
    }

    public function getFixedMonthDay(): ?string
    {
        return match ($this) {
            self::NEW_YEAR => '01-01',
            self::LABOR_DAY => '05-01',
            self::VICTORY_DAY => '05-08',
            self::BASTILLE_DAY => '07-14',
            self::ASSUMPTION => '08-15',
            self::ALL_SAINTS => '11-01',
            self::ARMISTICE => '11-11',
            self::CHRISTMAS => '12-25',
            default => null,
        };
    }

    public function getName(): string
    {
        return match ($this) {
            self::NEW_YEAR => 'New Year\'s Day',
            self::EASTER_MONDAY => 'Easter Monday',
            self::LABOR_DAY => 'Labor Day',
            self::VICTORY_DAY => 'Victory in Europe Day',
            self::ASCENSION => 'Ascension Day',
            self::WHIT_MONDAY => 'Whit Monday',
            self::BASTILLE_DAY => 'Bastille Day',
            self::ASSUMPTION => 'Assumption of Mary',
            self::ALL_SAINTS => 'All Saints\' Day',
            self::ARMISTICE => 'Armistice Day',
            self::CHRISTMAS => 'Christmas',
        };
    }

    public static function fromKeyword(string $keyword): ?self
    {
        return self::tryFrom(strtolower($keyword));
    }

    public static function all(): array
    {
        return self::cases();
    }
}
