<?php

namespace App\StructuralCalculation\Beams;

final readonly class BeamGoverningVerificationExclusion
{
    public function __construct(public string $identifier, public string $reason) {}
}
