<?php

namespace App\StructuralCalculation\Beams;

/** Hypothèse de composition béton V1 utilisée exclusivement pour le placement des barres. */
final readonly class BeamReinforcementDetailingAssumptions
{
    public const DEFAULT_MAXIMUM_AGGREGATE_SIZE = 20.0;

    public function __construct(public float $maximumAggregateSize = self::DEFAULT_MAXIMUM_AGGREGATE_SIZE) {}
}
