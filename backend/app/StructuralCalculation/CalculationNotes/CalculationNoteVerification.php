<?php

namespace App\StructuralCalculation\CalculationNotes;

use App\StructuralCalculation\Beams\BeamVerificationStatus;

/** @param list<CalculationNoteValue> $details */
final readonly class CalculationNoteVerification
{
    public function __construct(
        public string $type,
        public string $label,
        public BeamVerificationStatus $status,
        public ?float $utilization = null,
        public string|int|float|null $governingValue = null,
        public string|int|float|null $limitValue = null,
        public ?string $unit = null,
        public ?string $method = null,
        public array $details = [],
    ) {}
}
