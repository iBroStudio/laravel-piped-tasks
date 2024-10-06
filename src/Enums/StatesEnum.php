<?php

namespace IBroStudio\PipedTasks\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum StatesEnum: string implements HasColor, HasIcon, HasLabel
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDING => 'pending',
            self::PROCESSING => 'processing',
            self::COMPLETED => 'completed',
            self::FAILED => 'failed',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::PROCESSING => 'warning',
            self::COMPLETED => 'success',
            self::FAILED => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::PENDING => 'heroicon-m-clock',
            self::PROCESSING => 'heroicon-m-cog-6-tooth',
            self::COMPLETED => 'heroicon-m-check',
            self::FAILED => 'heroicon-m-x-mark',
        };
    }
}
