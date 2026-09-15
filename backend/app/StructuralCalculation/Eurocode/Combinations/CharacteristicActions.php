<?php

namespace App\StructuralCalculation\Eurocode\Combinations;

use App\StructuralCalculation\Eurocode\Profiles\VariableActionCategory;

/** Paire Gk/Qk indépendante de l'élément et de l'unité de charge. */
final readonly class CharacteristicActions
{
    public function __construct(
        public float $permanentTotal,
        public float $variableCharacteristic,
        public VariableActionCategory $variableActionCategory,
    ) {}
}
