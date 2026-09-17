<?php

namespace App\StructuralCalculation\CalculationNotes;

/** @param list<CalculationNoteValue> $items @param list<CalculationNoteStep> $steps @param list<CalculationNoteSection> $subsections */
final readonly class CalculationNoteSection
{
    public function __construct(
        public string $key,
        public string $title,
        public array $items = [],
        public array $steps = [],
        public array $subsections = [],
    ) {}
}
