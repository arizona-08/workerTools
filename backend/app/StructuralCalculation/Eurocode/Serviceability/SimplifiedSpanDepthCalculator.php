<?php

namespace App\StructuralCalculation\Eurocode\Serviceability;

use App\StructuralCalculation\Eurocode\Beams\BeamDeflectionRequirements;

/** Noyau commun du contrôle simplifié de portée sur hauteur utile EC2 §7.4.2. */
final class SimplifiedSpanDepthCalculator
{
    public function calculate(float $span, float $width, float $effectiveDepth, float $fck, float $fyk, float $requiredArea, float $providedArea, float $structuralFactor, BeamDeflectionRequirements $requirements): SimplifiedSpanDepthResult
    {
        foreach ([$span, $width, $effectiveDepth, $fck, $fyk, $requiredArea, $providedArea, $structuralFactor] as $value) {
            if (! is_finite($value) || $value <= 0) {
                throw new \InvalidArgumentException('The simplified span-depth input is invalid.');
            }
        }
        if ($providedArea < $requiredArea) {
            throw new \InvalidArgumentException('The longitudinal reinforcement is insufficient.');
        }

        $actualRatio = $span / $effectiveDepth;
        $reinforcementRatio = $requiredArea / ($width * $effectiveDepth);
        $referenceRatio = sqrt($fck) * $requirements->referenceReinforcementRatioFactor;
        $formulaBranch = $reinforcementRatio <= $referenceRatio ? 'LOW_REINFORCEMENT_RATIO' : 'HIGH_REINFORCEMENT_RATIO';
        $strengthRoot = sqrt($fck);
        $baseRatio = $formulaBranch === 'LOW_REINFORCEMENT_RATIO'
            ? $structuralFactor * ($requirements->baseRatioConstant
                + $requirements->lowReinforcementCoefficient * $strengthRoot * ($referenceRatio / $reinforcementRatio)
                + $requirements->lowReinforcementAdditionalCoefficient * $strengthRoot * ($referenceRatio / $reinforcementRatio - 1) ** 1.5)
            : $structuralFactor * ($requirements->baseRatioConstant
                + $requirements->lowReinforcementCoefficient * $strengthRoot * $referenceRatio / $reinforcementRatio);
        $steelStressCorrection = ($requirements->referenceSteelStrength / $fyk) * ($providedArea / $requiredArea);
        $allowableRatio = $baseRatio * $steelStressCorrection;
        if (! is_finite($allowableRatio) || $allowableRatio <= 0) {
            throw new \InvalidArgumentException('The simplified span-depth result is invalid.');
        }

        return new SimplifiedSpanDepthResult($actualRatio, $reinforcementRatio, $referenceRatio, $formulaBranch, $baseRatio, $steelStressCorrection, $allowableRatio, $actualRatio / $allowableRatio);
    }
}
