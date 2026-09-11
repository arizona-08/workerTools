<?php

namespace App\StructuralCalculation\Eurocode\Actions;

/** Poids volumique d'un matériau, distinct de ses propriétés mécaniques EC2. */
final readonly class ReinforcedConcreteUnitWeight
{
    public const UNIT = 'kN/m³';

    public function __construct(public float $value) {}
}
