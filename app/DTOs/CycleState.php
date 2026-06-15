<?php

namespace App\DTOs;

/**
 * Represents the current state of the monitoring cycle.
 */
class CycleState
{
    public function __construct(
        public readonly int $countdownSeconds,
        public readonly ?\DateTimeInterface $lastCheckAt,
        public readonly bool $cycleInProgress,
        public readonly int $cycleIntervalMinutes,
    ) {}
}
