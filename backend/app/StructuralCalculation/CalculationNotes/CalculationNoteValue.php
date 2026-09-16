<?php

namespace App\StructuralCalculation\CalculationNotes;

/** Valeur structurée affichable ; aucune concaténation irréversible valeur/unité. */
final readonly class CalculationNoteValue
{
    public function __construct(
        public string $key,
        public string $label,
        public string|int|float|bool|null $value,
        public ?string $unit = null,
        public ?string $displayValue = null,
        public ?string $source = null,
    ) {}
}
