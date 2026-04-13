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

class GeographicViolationGenerator
{
    public static function seed(int $count, int $seed = 20260412): void
    {
        $faker = FakerFactory::create('en_US');
        $faker->seed($seed);

        $areas = Area::query()
            ->whereNotNull('center_lat')
            ->whereNotNull('center_lng')
            ->orderBy('city')
            ->orderBy('name')
            ->get();

        $reporters = User::query()
            ->where('role', 'Police_officer')
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        $types = ViolationType::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        if ($areas->isEmpty() || $reporters->isEmpty() || $types->isEmpty()) {
            return;
        }

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
        $landmarkSuffixes = ['Traffic Signal', 'Main Junction', 'Bridge Access', 'Municipal Gate', 'Commercial Strip'];

        for ($index = 1; $index <= $count; $index++) {
            $area = $areas[($index - 1) % $areas->count()];
            $reporter = $reporters[($index - 1) % $reporters->count()];
            $type = $types[$faker->numberBetween(0, $types->count() - 1)];

            $lat = round((float) $area->center_lat + $faker->randomFloat(6, -0.022, 0.022), 7);
            $lng = round((float) $area->center_lng + $faker->randomFloat(6, -0.022, 0.022), 7);

            $hour = self::randomHour($faker);
            $occurredAt = now()
                ->copy()
                ->subDays($faker->numberBetween(0, 240))
                ->setTime($hour, $faker->numberBetween(0, 59), $faker->numberBetween(0, 59));

            $plateNumber = sprintf('SY-%03d-%03d', (($index - 1) % 14) + 1, $index);
            $vehicle = Vehicle::query()->updateOrCreate(
                ['plate_number' => $plateNumber],
                [
                    'owner_name' => $faker->name(),
                    'model' => $models[($index - 1) % count($models)],
                    'color' => $colors[($index - 1) % count($colors)],
                ]
            );

            $streetName = "{$area->name} {$streetSuffixes[$index % count($streetSuffixes)]}";
            $landmark = sprintf('%s %s %03d', $area->name, $landmarkSuffixes[$index % count($landmarkSuffixes)], $index);
            $address = sprintf('%s block %d, %s', $area->name, (($index - 1) % 12) + 1, $area->city);

            $location = ViolationLocation::query()->updateOrCreate(
                ['landmark' => $landmark],
                [
                    'area_id' => $area->id,
                    'city' => $area->city,
                    'street_name' => $streetName,
                    'address' => $address,
                    'latitude' => $lat,
                    'longitude' => $lng,
                ]
            );

            $severity = self::severityFromWeight((float) ($type->severity_weight ?? 1));
            $source = $sources[$index % count($sources)];
            $status = $statuses[$index % count($statuses)];
            $plateSnapshot = sprintf('plates/faker-geography-%03d.jpg', $index);

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
                    'owner_snapshot' => sprintf('owners/faker-owner-%03d.jpg', $index),
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
                    'file_path' => sprintf('attachments/faker-geography-%03d.jpg', $index),
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
        $violations = Violation::query()
            ->where('plate_snapshot', 'like', 'plates/faker-geography-%')
            ->get(['id', 'violation_location_id']);

        $violationIds = $violations->pluck('id')->all();
        $locationIds = $violations->pluck('violation_location_id')->filter()->unique()->values()->all();

        $attachmentsDeleted = Attachment::query()
            ->whereIn('violation_id', $violationIds)
            ->delete();

        $violationsDeleted = Violation::query()
            ->whereIn('id', $violationIds)
            ->delete();

        $locationsDeleted = ViolationLocation::query()
            ->whereIn('id', $locationIds)
            ->where('landmark', 'like', '% % %')
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
