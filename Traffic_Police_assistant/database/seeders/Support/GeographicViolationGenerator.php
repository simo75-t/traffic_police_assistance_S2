<?php

namespace Database\Seeders\Support;

use App\Models\Area;
use App\Models\Attachment;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Violation;
use App\Models\ViolationLocation;
use App\Models\ViolationType;
use Faker\Factory as FakerFactory;
use Illuminate\Support\Collection;

class GeographicViolationGenerator
{
    private const SYNTHETIC_ADDRESS_PREFIX = 'AUTO-GENERATED:';

    public static function seed(int $count, int $seed = 20260412, ?string $city = null): void
    {
        $faker = FakerFactory::create('en_US');
        $faker->seed($seed);
        $series = self::seriesKey($city);

        $areasQuery = Area::query()
            ->whereNotNull('center_lat')
            ->whereNotNull('center_lng')
            ->orderBy('city')
            ->orderBy('name');

        if ($city !== null && $city !== '') {
            $areasQuery->where('city', $city);
        }

        $areas = $areasQuery->get()->values();

        $reporters = User::query()
            ->where('role', 'Police_officer')
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->values();

        $types = ViolationType::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->values();

        if ($areas->isEmpty() || $reporters->isEmpty() || $types->isEmpty()) {
            return;
        }

        $existingLocations = ViolationLocation::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when($city !== null && $city !== '', fn ($query) => $query->where('city', $city))
            ->orderBy('area_id')
            ->orderBy('id')
            ->get()
            ->groupBy('area_id');

        $models = [
            'Kia Rio 2018',
            'Hyundai Accent 2020',
            'Toyota Corolla 2017',
            'Nissan Sunny 2019',
            'Toyota Yaris 2016',
            'Kia Cerato 2021',
            'Hyundai Elantra 2018',
            'Suzuki Swift 2015',
            'Renault Logan 2014',
            'Honda Civic 2022',
            'Peugeot 301 2019',
            'Toyota Camry 2020',
            'Mitsubishi Lancer 2013',
            'Hyundai Tucson 2021',
        ];

        $colors = ['White', 'Black', 'Silver', 'Gray', 'Blue', 'Red', 'Green', 'Yellow', 'Brown'];
        $sources = ['patrol', 'camera', 'citizen_report'];
        $statuses = ['issued', 'under_review', 'under_appeal'];
        $streetSuffixes = ['Main Street', 'Traffic Road', 'Central Avenue', 'Service Road', 'Roundabout Road'];
        $landmarkSuffixes = ['Traffic Node', 'Main Junction', 'Bridge Access', 'Commercial Strip'];

        $locationPools = [];
        foreach ($areas as $area) {
            $locationPools[$area->id] = self::buildAreaLocationPool(
                area: $area,
                seedLocations: $existingLocations->get($area->id)?->values() ?? collect(),
                faker: $faker,
                streetSuffixes: $streetSuffixes,
                landmarkSuffixes: $landmarkSuffixes,
            );
        }

        $areaSequence = self::buildAreaSequence($areas->pluck('id')->all(), $count, $faker);

        for ($index = 1; $index <= $count; $index++) {
            $areaId = $areaSequence[$index - 1];
            $area = $areas->firstWhere('id', $areaId);

            if (! $area) {
                continue;
            }

            /** @var Collection<int, ViolationLocation> $locationPool */
            $locationPool = $locationPools[$areaId];
            $location = $locationPool->get(($index - 1) % $locationPool->count());

            if (! $location) {
                continue;
            }

            $reporter = $reporters[($index - 1) % $reporters->count()];
            $type = $types[$faker->numberBetween(0, $types->count() - 1)];

            $hour = self::randomHour($faker);
            $occurredAt = now()
                ->copy()
                ->subDays($faker->numberBetween(0, 180))
                ->setTime($hour, $faker->numberBetween(0, 59), $faker->numberBetween(0, 59));

            $plateNumber = sprintf('SY-%03d-%03d', (($index - 1) % 40) + 1, $index);
            $vehicle = Vehicle::query()->updateOrCreate(
                ['plate_number' => $plateNumber],
                [
                    'owner_name' => $faker->name(),
                    'model' => $models[($index - 1) % count($models)],
                    'color' => $colors[($index - 1) % count($colors)],
                ]
            );

            $severity = self::severityFromWeight((float) ($type->severity_weight ?? 1));
            $source = $sources[$faker->numberBetween(0, count($sources) - 1)];
            $status = $statuses[$faker->numberBetween(0, count($statuses) - 1)];
            $plateSnapshot = sprintf('plates/faker-geography-%s-%03d.jpg', $series, $index);

            $violation = Violation::query()->updateOrCreate(
                ['plate_snapshot' => $plateSnapshot],
                [
                    'vehicle_id' => $vehicle->id,
                    'violation_type_id' => $type->id,
                    'violation_location_id' => $location->id,
                    'reported_by' => $reporter->id,
                    'source_report_id' => null,
                    'description' => self::buildSyntheticDescription(area: $area->name, city: $area->city, typeName: $type->name, faker: $faker),
                    'fine_amount' => $type->fine_amount,
                    'vehicle_snapshot' => json_encode(['plate_number' => $vehicle->plate_number]),
                    'owner_snapshot' => sprintf('owners/faker-owner-%s-%03d.jpg', $series, $index),
                    'occurred_at' => $occurredAt,
                    'created_at' => $occurredAt,
                    'data_source' => $source,
                    'is_synthetic' => true,
                    'severity_level' => $severity,
                    'status' => $status,
                ]
            );

            Attachment::query()->updateOrCreate(
                [
                    'violation_id' => $violation->id,
                    'file_path' => sprintf('attachments/faker-geography-%s-%03d.jpg', $series, $index),
                ],
                [
                    'file_type' => 'image/jpeg',
                    'uploaded_by' => $reporter->id,
                    'recorded_at' => $occurredAt,
                ]
            );
        }
    }

    public static function clear(): array
    {
        $violationIds = Violation::query()
            ->where('plate_snapshot', 'like', 'plates/faker-geography-%')
            ->pluck('id')
            ->all();

        $attachmentsDeleted = Attachment::query()
            ->whereIn('violation_id', $violationIds)
            ->delete();

        $violationsDeleted = Violation::query()
            ->whereIn('id', $violationIds)
            ->delete();

        $locationsDeleted = ViolationLocation::query()
            ->where(function ($query) {
                $query
                    ->where('address', 'like', self::SYNTHETIC_ADDRESS_PREFIX . '%')
                    ->orWhere('address', 'like', '%block %');
            })
            ->whereNotIn('id', function ($query) {
                $query->select('violation_location_id')
                    ->from('violations')
                    ->whereNotNull('violation_location_id');
            })
            ->delete();

        $vehiclesDeleted = Vehicle::query()
            ->where('plate_number', 'like', 'SY-%')
            ->delete();

        return [
            'attachments' => $attachmentsDeleted,
            'violations' => $violationsDeleted,
            'locations' => $locationsDeleted,
            'vehicles' => $vehiclesDeleted,
        ];
    }

    private static function buildAreaSequence(array $areaIds, int $count, \Faker\Generator $faker): array
    {
        $sequence = [];

        while (count($sequence) < $count) {
            $round = $areaIds;
            shuffle($round);

            foreach ($round as $areaId) {
                $sequence[] = $areaId;

                if (count($sequence) >= $count) {
                    break 2;
                }
            }

            if ($faker->boolean(25) && count($sequence) < $count) {
                $sequence[] = $round[$faker->numberBetween(0, count($round) - 1)];
            }
        }

        return array_slice($sequence, 0, $count);
    }

    private static function buildAreaLocationPool(
        Area $area,
        Collection $seedLocations,
        \Faker\Generator $faker,
        array $streetSuffixes,
        array $landmarkSuffixes,
    ): Collection {
        $realLocations = $seedLocations
            ->filter(fn (ViolationLocation $location) => ! str_starts_with((string) $location->address, self::SYNTHETIC_ADDRESS_PREFIX))
            ->take(2)
            ->values();

        $pool = collect($realLocations->all());
        $targetPoolSize = max(2, min(4, $realLocations->count() + 1));

        while ($pool->count() < $targetPoolSize) {
            $slot = $pool->count() + 1;
            $offsetLat = $faker->randomFloat(6, -0.0075, 0.0075);
            $offsetLng = $faker->randomFloat(6, -0.0075, 0.0075);

            $location = ViolationLocation::query()->updateOrCreate(
                ['address' => self::SYNTHETIC_ADDRESS_PREFIX . $area->id . ':' . $slot],
                [
                    'area_id' => $area->id,
                    'city' => $area->city,
                    'street_name' => $area->name . ' ' . $streetSuffixes[($slot - 1) % count($streetSuffixes)],
                    'landmark' => $area->name . ' ' . $landmarkSuffixes[($slot - 1) % count($landmarkSuffixes)] . ' ' . sprintf('%02d', $slot),
                    'address' => self::SYNTHETIC_ADDRESS_PREFIX . $area->id . ':' . $slot,
                    'latitude' => round((float) $area->center_lat + $offsetLat, 7),
                    'longitude' => round((float) $area->center_lng + $offsetLng, 7),
                ]
            );

            $pool->push($location);
        }

        return $pool->values();
    }

    private static function seriesKey(?string $city): string
    {
        if ($city === null || $city === '') {
            return 'all';
        }

        $series = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $city) ?? 'city');

        return trim($series, '-') ?: 'city';
    }

    private static function randomHour(\Faker\Generator $faker): int
    {
        $bucket = $faker->randomElement([
            [7, 10],
            [11, 15],
            [16, 20],
            [21, 23],
            [0, 2],
        ]);

        return $faker->numberBetween($bucket[0], $bucket[1]);
    }

    private static function severityFromWeight(float $weight): string
    {
        return match (true) {
            $weight >= 5 => 'critical',
            $weight >= 3.5 => 'high',
            $weight >= 2 => 'medium',
            default => 'low',
        };
    }

    private static function buildSyntheticDescription(string $area, string $city, string $typeName, \Faker\Generator $faker): string
    {
        $templates = [
            'Traffic patrol recorded %s near %s, %s during routine enforcement.',
            'Automated demo seed logged %s around %s in %s to enrich geographic analytics coverage.',
            'Field observation generated %s at %s, %s during a simulated patrol shift.',
        ];

        $template = $templates[$faker->numberBetween(0, count($templates) - 1)];

        return sprintf($template, $typeName, $area, $city);
    }
}
