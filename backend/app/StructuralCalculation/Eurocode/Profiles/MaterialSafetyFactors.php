<?php

namespace App\StructuralCalculation\Eurocode\Profiles;

final readonly class MaterialSafetyFactors
{
    public function __construct(
        public float $gammaC,
        public float $gammaS,
        public float $alphaCc,
    ) {}
}
