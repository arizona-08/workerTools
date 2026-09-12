<?php

namespace App\StructuralCalculation\Beams;

use DomainException;

final class BeamLeverArmException extends DomainException
{
    public function __construct(public readonly BeamLeverArmRejectionReason $reason)
    {
        parent::__construct($reason->value);
    }
}
