<?php

namespace App\StructuralCalculation\CalculationNotes;

use App\StructuralCalculation\Beams\BeamVerificationStatus;

/** Statut final reçu du moteur, jamais déduit à nouveau dans le modèle documentaire. */
final readonly class CalculationNoteFinalStatus
{
    public function __construct(
        public BeamVerificationStatus $status,
        public ?string $governingVerificationType = null,
        public ?float $governingUtilization = null,
        public array $summary = [],
    ) {}
}
