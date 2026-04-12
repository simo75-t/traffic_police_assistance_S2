<?php

namespace App\Http\Services\Admin;

use App\Models\ViolationType;
use Illuminate\Database\Eloquent\Collection;

class ViolationTypeService
{
    /**
     * @param array{name: string, description?: string|null, fine_amount: int|float|string} $attrs
     */
    public function createViolationType(array $attrs): ViolationType
    {
        return ViolationType::create([
            'name' => $attrs['name'],
            'description' => $attrs['description'] ?? null,
            'fine_amount' => $attrs['fine_amount'],
        ]);
    }

    /**
     * @return Collection<int, ViolationType>
     */
    public function getViolationTypeList(): Collection
    {
        return ViolationType::query()->orderBy('name')->get();
    }
}
