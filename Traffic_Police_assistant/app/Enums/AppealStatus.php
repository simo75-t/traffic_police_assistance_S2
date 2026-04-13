<?php

namespace App\Enums;

enum AppealStatus
{
    public const Pending = 'pending';
    public const Accepted = 'accepted';
    public const Rejected = 'rejected';

    /**
     * Central list used by validation rules and form dropdowns.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return [
            self::Pending,
            self::Accepted,
            self::Rejected,
        ];
    }

    /**
     * Human-readable labels for the police manager interface.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::Pending => 'Pending',
            self::Accepted => 'Accepted',
            self::Rejected => 'Rejected',
        ];
    }
}
