<?php

namespace App\StructuralCalculation\Slabs;

enum SlabMainReinforcementProposalStatus: string
{
    case REINFORCEMENT_PROPOSAL_FOUND = 'REINFORCEMENT_PROPOSAL_FOUND';
    case NO_VALID_REINFORCEMENT_PROPOSAL = 'NO_VALID_REINFORCEMENT_PROPOSAL';
}
