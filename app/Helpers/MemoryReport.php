<?php

namespace App\Helpers;

class MemoryReport
{
    private const UNITS = [
        'b',
        'kb',
        'mb',
        'gb',
        'tb',
        'pb'
    ];

    public static function getRawValue(): int
    {
        return memory_get_usage();
    }

    public static function getFormattedValue(): string
    {
        $memoryUsage = memory_get_usage();

        $unitIndex = $i = floor(log($memoryUsage, 1024));

        return @round($memoryUsage / pow(1024, $unitIndex), 2) . ' ' . self::UNITS[$unitIndex];
    }
}
