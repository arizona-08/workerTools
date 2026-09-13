<?php

namespace App\StructuralCalculation\Beams;

/** Contrôle local traçable d'une contrainte ELS. */
final readonly class BeamServiceStressCheck
{
    public function __construct(
        public string $name,
        public ?float $stress,
        public ?float $limit,
        public ?float $utilization,
        public BeamServiceStressCheckStatus $status,
        public string $combination,
        public ?float $moment,
        public string $method,
    ) {}
}
