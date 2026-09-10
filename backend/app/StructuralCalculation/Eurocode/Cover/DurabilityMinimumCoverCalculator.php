<?php

namespace App\StructuralCalculation\Eurocode\Cover;

use App\StructuralCalculation\Eurocode\Profiles\DesignCodeProfile;
use App\StructuralCalculation\Materials\Exposure\ExposureClassCode;

final class DurabilityMinimumCoverCalculator
{
    public function calculate(DesignCodeProfile $profile, ExposureClassCode $exposureClass, StructuralClassResult $structuralClass): DurabilityExposureResult
    {
        $minimumCover = $profile->coverRequirements->minimumDurabilityCoverFor($structuralClass->finalStructuralClass, $exposureClass);

        if ($minimumCover === null) {
            throw new CoverCalculationException(CoverCalculationRejectionReason::UNSUPPORTED_EXPOSURE_CLASS);
        }

        return new DurabilityExposureResult($exposureClass, $structuralClass, $minimumCover);
    }
}
