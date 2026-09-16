<?php

namespace App\StructuralCalculation\Beams;

/** Face de la section où se situent les armatures longitudinales tendues. */
enum BeamTensionFace: string
{
    case TOP = 'TOP';
    case BOTTOM = 'BOTTOM';

    public function reinforcementPosition(): BeamReinforcementPosition
    {
        return match ($this) {
            self::TOP => BeamReinforcementPosition::TOP,
            self::BOTTOM => BeamReinforcementPosition::BOTTOM,
        };
    }
}
