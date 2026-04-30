<?php

namespace App\DTOs;

final class HeatmapJobData
{
    private const DEFAULT_GRID_SIZE_METERS = 300;

    public function __construct(
        public readonly string $city,
        public readonly string $dateFrom,
        public readonly string $dateTo,
        public readonly ?int $violationTypeId,
        public readonly string $timeBucket,
        public readonly int $gridSizeMeters,
        public readonly bool $includeRanking,
        public readonly bool $includeTrend,
        public readonly bool $includeSynthetic,
        public readonly string $comparisonMode,
    ) {
    }

    public static function fromValidated(array $validated): self
    {
        $includeTrend = (bool) ($validated['include_trend'] ?? false);

        return new self(
            city: trim((string) $validated['city']),
            dateFrom: (string) $validated['date_from'],
            dateTo: (string) $validated['date_to'],
            violationTypeId: isset($validated['violation_type_id']) ? (int) $validated['violation_type_id'] : null,
            timeBucket: (string) ($validated['time_bucket'] ?? ''),
            gridSizeMeters: (int) ($validated['grid_size_meters'] ?? self::DEFAULT_GRID_SIZE_METERS),
            includeRanking: (bool) ($validated['include_ranking'] ?? false),
            includeTrend: $includeTrend,
            includeSynthetic: (bool) ($validated['include_synthetic'] ?? true),
            comparisonMode: $includeTrend ? (string) ($validated['comparison_mode'] ?? '') : '',
        );
    }

    public function toPayload(string $jobId, string $correlationId): array
    {
        return [
            'job_type' => 'generate_heatmap',
            'request_id' => $jobId,
            'job_id' => $jobId,
            'correlation_id' => $correlationId,
            'city' => $this->city,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'violation_type_id' => $this->violationTypeId,
            'time_bucket' => $this->timeBucket,
            'grid_size_meters' => $this->gridSizeMeters,
            'include_ranking' => $this->includeRanking,
            'include_trend' => $this->includeTrend,
            'include_synthetic' => $this->includeSynthetic,
            'comparison_mode' => $this->comparisonMode,
        ];
    }
}
