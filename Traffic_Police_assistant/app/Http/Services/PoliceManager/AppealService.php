<?php

namespace App\Http\Services\PoliceManager;

use App\Models\Appeal;
use Illuminate\Support\Collection;

class AppealService
{
    /**
     * @return Collection<int, Appeal>
     */
    public function getAll(): Collection
    {
        return Appeal::query()->latest()->get();
    }

    public function updateStatus(Appeal $appeal, string $status): Appeal
    {
        $appeal->status = $status;
        $appeal->save();

        return $appeal;
    }
}

