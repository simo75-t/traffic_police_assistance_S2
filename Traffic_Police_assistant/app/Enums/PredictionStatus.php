<?php

namespace App\Enums;

enum PredictionStatus: string
{
    case Queued = 'queued';
    case Pending = 'pending';
    case Processing = 'processing';
    case Failed = 'failed';
    case Done = 'done';
    case Success = 'success';

    public static function normalize(?string $status): self
    {
        return match ((string) $status) {
            self::Done->value,
            self::Success->value => self::Done,
            self::Failed->value => self::Failed,
            default => self::Processing,
        };
    }

    public static function fromJobResult(array $data): self
    {
        return (($data['status'] ?? '') === self::Success->value)
            ? self::Success
            : self::Failed;
    }
}
