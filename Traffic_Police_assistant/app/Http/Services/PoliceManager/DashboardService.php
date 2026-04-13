<?php

namespace App\Http\Services\PoliceManager;

use App\Enums\AppealStatus;
use App\Models\Appeal;
use App\Models\Violation;

class DashboardService
{
    /**
     * @return array{violationsCount: int, appealsCount: int, pendingAppealsCount: int}
     */
    public function getStats(): array
    {
        return [
            'violationsCount' => Violation::query()->count(),
            'appealsCount' => Appeal::query()->count(),
            'pendingAppealsCount' => Appeal::query()
                ->where('status', AppealStatus::Pending)
                ->count(),
        ];
    }
}

