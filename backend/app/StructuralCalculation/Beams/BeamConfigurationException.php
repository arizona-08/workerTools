<?php

namespace App\StructuralCalculation\Beams;

use DomainException;

final class BeamConfigurationException extends DomainException
{
    public function __construct(public readonly BeamConfigurationRejectionReason $reason)
    {
        parent::__construct($reason->value);
    }
}
