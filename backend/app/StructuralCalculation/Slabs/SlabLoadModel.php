<?php

namespace App\StructuralCalculation\Slabs;

/** Modèle de charges explicitement couvert par le V1 Dalle. */
enum SlabLoadModel: string
{
    case VERTICAL_UNIFORMLY_DISTRIBUTED = 'VERTICAL_UNIFORMLY_DISTRIBUTED';
}
