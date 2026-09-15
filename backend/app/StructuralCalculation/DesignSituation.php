<?php

namespace App\StructuralCalculation;

/** Situation de projet retenue par la configuration métier. */
enum DesignSituation: string
{
    case PERSISTENT_TRANSIENT = 'PERSISTENT_TRANSIENT';
}
