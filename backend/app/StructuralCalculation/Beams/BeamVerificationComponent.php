<?php

namespace App\StructuralCalculation\Beams;

/** Adaptation sans recalcul d'une vérification locale au contrat d'agrégation. */
final readonly class BeamVerificationComponent
{
    /** @param list<string> $warnings */
    public function __construct(public string $identifier, public BeamVerificationStatus $status, public ?float $utilization = null, public ?float $governingValue = null, public ?float $limitValue = null, public ?string $method = null, public array $warnings = [], public ?string $subcheck = null, public ?string $loadCombination = null) {}
}
