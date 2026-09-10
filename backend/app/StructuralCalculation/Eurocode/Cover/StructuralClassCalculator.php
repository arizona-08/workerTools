<?php

namespace App\StructuralCalculation\Eurocode\Cover;

use App\StructuralCalculation\Eurocode\Profiles\DesignCodeProfile;
use App\StructuralCalculation\Materials\Concrete\ConcreteStrengthClass;
use App\StructuralCalculation\Materials\Exposure\ExposureClassCode;

final class StructuralClassCalculator
{
    public function calculate(
        DesignCodeProfile $profile,
        ExposureClassCode $exposureClass,
        ConcreteStrengthClass $concreteClass,
        int $designWorkingLifeYears,
        bool $compactCover,
    ): StructuralClassResult {
        $requirements = $profile->coverRequirements;
        $modifiers = [
            new StructuralClassModifier('workingLife', $requirements->workingLifeModifier($designWorkingLifeYears)),
            new StructuralClassModifier('concreteStrength', $requirements->concreteStrengthModifier($concreteClass, $exposureClass)),
            new StructuralClassModifier('compactCover', $requirements->compactCoverModifier($compactCover)),
        ];
        $finalClass = $requirements->initialStructuralClass->withModifier(array_sum(array_map(
            fn (StructuralClassModifier $modifier): int => $modifier->value,
            $modifiers,
        )));

        if ($finalClass === null) {
            throw new CoverCalculationException(CoverCalculationRejectionReason::STRUCTURAL_CLASS_RULE_NOT_SUPPORTED);
        }

        return new StructuralClassResult($requirements->initialStructuralClass, $modifiers, $finalClass);
    }
}
