<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\ReportLocation;
use App\Models\ViolationLocation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DamascusAreaCleanupSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->mergeArea(fromId: 14, toId: 8);
            $this->renameArea(id: 100, name: 'مركز دمشق');
        });
    }

    private function mergeArea(int $fromId, int $toId): void
    {
        $from = Area::query()->find($fromId);
        $to = Area::query()->find($toId);

        if (! $from || ! $to) {
            return;
        }

        ReportLocation::query()
            ->where('area_id', $fromId)
            ->update(['area_id' => $toId]);

        ViolationLocation::query()
            ->where('area_id', $fromId)
            ->update(['area_id' => $toId]);

        $from->delete();
    }

    private function renameArea(int $id, string $name): void
    {
        $area = Area::query()->find($id);

        if (! $area) {
            return;
        }

        $area->name = $name;
        $area->save();
    }
}
