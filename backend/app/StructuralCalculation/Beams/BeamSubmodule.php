<?php

namespace App\StructuralCalculation\Beams;

/**
 * Identifie le cas de calcul Poutre indépendamment du type d'élément et de
 * son système statique. Le moteur de console n'est pas encore implémenté.
 */
enum BeamSubmodule: string
{
    case BEAM_SIMPLE_RECTANGULAR = 'BEAM_SIMPLE_RECTANGULAR';
    case BEAM_CANTILEVER_RECTANGULAR = 'BEAM_CANTILEVER_RECTANGULAR';

    public function supportSystem(): BeamSupportSystem
    {
        return match ($this) {
            self::BEAM_SIMPLE_RECTANGULAR => BeamSupportSystem::SIMPLY_SUPPORTED,
            self::BEAM_CANTILEVER_RECTANGULAR => BeamSupportSystem::CANTILEVER,
        };
    }

    public function tensionFace(): BeamTensionFace
    {
        return match ($this) {
            self::BEAM_SIMPLE_RECTANGULAR => BeamTensionFace::BOTTOM,
            self::BEAM_CANTILEVER_RECTANGULAR => BeamTensionFace::TOP,
        };
    }
}
