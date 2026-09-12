<?php

namespace App\StructuralCalculation\Beams;

enum BeamReinforcementCandidatesStatus: string
{
    case CANDIDATES_AVAILABLE = 'CANDIDATES_AVAILABLE';
    case NO_REINFORCEMENT_CANDIDATE = 'NO_REINFORCEMENT_CANDIDATE';
}
