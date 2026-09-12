<?php

namespace App\StructuralCalculation\Beams;

/** Hypothèse de composition béton MVP utilisée exclusivement pour le placement des barres. */
final readonly class BeamReinforcementDetailingAssumptions
{
    public const MVP_MAXIMUM_AGGREGATE_SIZE = 20.0;

    public function __construct(public float $maximumAggregateSize = self::MVP_MAXIMUM_AGGREGATE_SIZE) {}
}
