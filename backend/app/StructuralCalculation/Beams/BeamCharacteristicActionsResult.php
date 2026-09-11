<?php

namespace App\StructuralCalculation\Beams;

/** Ensemble des seules actions caractéristiques de la poutre MVP. */
final readonly class BeamCharacteristicActionsResult
{
    public function __construct(
        public CharacteristicPermanentActions $permanent,
        public CharacteristicVariableAction $variable,
    ) {}
}
