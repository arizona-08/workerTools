<?php

namespace App\StructuralCalculation\Beams;

/** Vérification la plus sollicitée par ratio existant, sans portée normative. */
final readonly class BeamGoverningVerificationResult
{
    /** @param list<BeamGoverningVerificationExclusion> $exclusions */
    public function __construct(public ?BeamVerificationComponent $governingVerification, public array $exclusions) {}
}
