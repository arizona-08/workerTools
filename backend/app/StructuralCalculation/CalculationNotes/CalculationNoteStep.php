<?php

namespace App\StructuralCalculation\CalculationNotes;

/** Étape existante de calcul transportée telle quelle pour un renderer ultérieur. */
final readonly class CalculationNoteStep
{
    public function __construct(
        public string $name,
        public ?string $formula = null,
        public ?string $substitution = null,
        public string|int|float|null $result = null,
    ) {}
}
