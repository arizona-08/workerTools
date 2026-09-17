<?php

namespace App\StructuralCalculation\Slabs;

use App\StructuralCalculation\Beams\BeamVerificationComponent;
use App\StructuralCalculation\Beams\BeamVerificationStatus;
use JsonSerializable;

/** Contrat public final SLAB-11 : statut, synthèse, vérifications et détails projetés. */
final readonly class SlabCalculationResult implements JsonSerializable
{
    /** @param list<BeamVerificationComponent> $verifications */
    public function __construct(
        public BeamVerificationStatus $status,
        public SlabResultSummary $summary,
        public array $verifications,
        public SlabCalculationDetails $details,
    ) {}

    public function jsonSerialize(): array
    {
        return ['status' => $this->status->value, 'summary' => $this->summary, 'verifications' => $this->verifications, 'details' => $this->details];
    }
}
