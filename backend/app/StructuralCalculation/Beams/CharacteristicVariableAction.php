<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Eurocode\Profiles\VariableActionCategory;

/** Action variable caractéristique conservée sans facteur de combinaison. */
final readonly class CharacteristicVariableAction
{
    public const UNIT = 'kN/m';

    public function __construct(
        public VariableActionCategory $category,
        public float $characteristicLoad,
    ) {}
}
