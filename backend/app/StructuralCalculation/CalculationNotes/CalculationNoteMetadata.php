<?php

namespace App\StructuralCalculation\CalculationNotes;

use App\StructuralCalculation\ElementType;
use DateTimeImmutable;

/** Métadonnées stables du document, indépendantes de son futur rendu PDF. */
final readonly class CalculationNoteMetadata
{
    public function __construct(
        public string $title,
        public ElementType $calculationType,
        public DateTimeImmutable $generatedAt,
        public string $designCodeProfile,
        public string $schemaVersion = '1.0',
    ) {}
}
