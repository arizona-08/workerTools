<?php

namespace App\StructuralCalculation\Eurocode\Profiles;

final readonly class CombinationFactors
{
    public function __construct(
        public float $psi0,
        public float $psi1,
        public float $psi2,
    ) {}
}
