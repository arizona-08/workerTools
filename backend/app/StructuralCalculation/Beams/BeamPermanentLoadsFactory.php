<?php

namespace App\StructuralCalculation\Beams;

/** Valide les valeurs du payload sans produire Gk,self ni Gk,total. */
final class BeamPermanentLoadsFactory
{
    public function fromValues(mixed $includeSelfWeight, mixed $additionalPermanentLoad): BeamPermanentLoads
    {
        if ($includeSelfWeight === null) {
            throw new BeamPermanentLoadsException(BeamPermanentLoadsRejectionReason::MISSING_INCLUDE_SELF_WEIGHT);
        }
        if (! is_bool($includeSelfWeight)) {
            throw new BeamPermanentLoadsException(BeamPermanentLoadsRejectionReason::INVALID_INCLUDE_SELF_WEIGHT);
        }
        if ($additionalPermanentLoad === null) {
            throw new BeamPermanentLoadsException(BeamPermanentLoadsRejectionReason::MISSING_ADDITIONAL_PERMANENT_LOAD);
        }
        if (is_bool($additionalPermanentLoad) || ! is_numeric($additionalPermanentLoad)) {
            throw new BeamPermanentLoadsException(BeamPermanentLoadsRejectionReason::INVALID_ADDITIONAL_PERMANENT_LOAD);
        }

        $load = (float) $additionalPermanentLoad;
        if (! is_finite($load) || $load < 0) {
            throw new BeamPermanentLoadsException(BeamPermanentLoadsRejectionReason::INVALID_ADDITIONAL_PERMANENT_LOAD);
        }

        return new BeamPermanentLoads($includeSelfWeight, $load);
    }
}
