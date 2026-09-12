<?php

namespace App\StructuralCalculation\Beams;

/** Provenance du diamètre utilisé pour placer le centre de gravité des aciers tendus. */
enum LongitudinalBarDiameterSource: string
{
    case USER = 'USER';
    case CONFIG = 'CONFIG';
}
