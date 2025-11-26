<?php

declare(strict_types=1);

namespace App\Enum;

enum FrenchHoliday: string
{
    case NewYear = 'new_year';
    case EasterMonday = 'easter_monday';
    case LaborDay = 'labor_day';
    case VictoryDay = 'victory_day';
    case Ascension = 'ascension';
    case WhitMonday = 'whit_monday';
    case BastilleDay = 'bastille_day';
    case Assumption = 'assumption';
    case AllSaints = 'all_saints';
    case Armistice = 'armistice';
    case Christmas = 'christmas';

    public function getKeyword(): string
    {
        return $this->value;
    }

    public function isFixedDate(): bool
    {
        return match ($this) {
            self::EasterMonday, self::Ascension, self::WhitMonday => false,
            default => true,
        };
    }

    public function getFixedMonthDay(): ?string
    {
        return match ($this) {
            self::NewYear => '01-01',
            self::LaborDay => '05-01',
            self::VictoryDay => '05-08',
            self::BastilleDay => '07-14',
            self::Assumption => '08-15',
            self::AllSaints => '11-01',
            self::Armistice => '11-11',
            self::Christmas => '12-25',
            default => null,
        };
    }

    public function getName(): string
    {
        return match ($this) {
            self::NewYear => 'New Year\'s Day',
            self::EasterMonday => 'Easter Monday',
            self::LaborDay => 'Labor Day',
            self::VictoryDay => 'Victory in Europe Day',
            self::Ascension => 'Ascension Day',
            self::WhitMonday => 'Whit Monday',
            self::BastilleDay => 'Bastille Day',
            self::Assumption => 'Assumption of Mary',
            self::AllSaints => 'All Saints\' Day',
            self::Armistice => 'Armistice Day',
            self::Christmas => 'Christmas',
        };
    }

    public static function fromKeyword(string $keyword): ?self
    {
        return self::tryFrom(strtolower($keyword));
    }

    /**
     * @return array<FrenchHoliday>
     */
    public static function all(): array
    {
        return self::cases();
    }
}
