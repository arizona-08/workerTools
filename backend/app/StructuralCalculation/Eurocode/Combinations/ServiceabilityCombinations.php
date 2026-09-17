<?php

namespace App\StructuralCalculation\Eurocode\Combinations;

/** Les trois combinaisons ELS du cas à une action variable principale. */
final readonly class ServiceabilityCombinations
{
    public function __construct(
        public CombinedAction $characteristic,
        public CombinedAction $frequent,
        public CombinedAction $quasiPermanent,
    ) {}
}
