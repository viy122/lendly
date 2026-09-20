<?php

namespace App\Enums;

enum ProjectType: string
{
    case HomeCleaning = 'home_cleaning';
    case HomeRenovation = 'home_renovation';
    case Moving = 'moving';
    case Gardening = 'gardening';
    case Event = 'event';
    case DiyProject = 'diy_project';

    public function label(): string
    {
        return match ($this) {
            self::HomeCleaning => 'Home Cleaning',
            self::HomeRenovation => 'Home Renovation',
            self::Moving => 'Moving',
            self::Gardening => 'Gardening',
            self::Event => 'Event',
            self::DiyProject => 'DIY Project',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::HomeCleaning => '🧹',
            self::HomeRenovation => '🔨',
            self::Moving => '📦',
            self::Gardening => '🌱',
            self::Event => '🎉',
            self::DiyProject => '🛠️',
        };
    }
}
