<?php

namespace App\StructuralCalculation\CalculationNotes;

/** @param list<CalculationNoteValue> $details */
final readonly class CalculationNoteReinforcement
{
    public function __construct(
        public string $type,
        public string $label,
        public ?string $designation = null,
        public ?float $diameter = null,
        public ?int $count = null,
        public ?float $spacing = null,
        public ?float $providedArea = null,
        public ?float $requiredArea = null,
        public ?string $unit = null,
        public array $details = [],
    ) {}
}
