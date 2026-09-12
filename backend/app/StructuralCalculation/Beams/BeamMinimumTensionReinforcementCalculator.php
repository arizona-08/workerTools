<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Eurocode\Beams\BeamLongitudinalReinforcementRequirements;
use App\StructuralCalculation\Materials\Concrete\ConcreteProperties;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelProperties;

/** Calcule As_min d'une section rectangulaire MVP suivant EC2 §9.2.1.1(1). */
final class BeamMinimumTensionReinforcementCalculator
{
    public function calculate(
        ConcreteProperties $concrete,
        ReinforcementSteelProperties $steel,
        BeamGeometry $geometry,
        BeamEffectiveDepthResult $effectiveDepth,
        BeamLongitudinalReinforcementRequirements $requirements,
    ): BeamMinimumTensionReinforcementResult {
        $this->ensurePositiveFinite($concrete->fctm, BeamMinimumTensionReinforcementRejectionReason::INVALID_MEAN_TENSILE_CONCRETE_STRENGTH);
        $this->ensurePositiveFinite($steel->fyk, BeamMinimumTensionReinforcementRejectionReason::INVALID_CHARACTERISTIC_STEEL_STRENGTH);
        $this->ensurePositiveFinite($geometry->width, BeamMinimumTensionReinforcementRejectionReason::INVALID_TENSION_ZONE_MEAN_WIDTH);
        $this->ensurePositiveFinite($effectiveDepth->effectiveDepth, BeamMinimumTensionReinforcementRejectionReason::INVALID_EFFECTIVE_DEPTH);
        $this->ensurePositiveFinite($requirements->minimumReinforcementStrengthCoefficient, BeamMinimumTensionReinforcementRejectionReason::INVALID_STRENGTH_COEFFICIENT);
        $this->ensurePositiveFinite($requirements->minimumReinforcementRatio, BeamMinimumTensionReinforcementRejectionReason::INVALID_MINIMUM_REINFORCEMENT_RATIO);

        // Pour la seule section rectangulaire MVP en flexion positive, bt = b.
        $tensionZoneArea = $geometry->width * $effectiveDepth->effectiveDepth;
        $this->ensurePositiveFinite($tensionZoneArea, BeamMinimumTensionReinforcementRejectionReason::INVALID_TENSION_ZONE_AREA);

        $strengthBasedMinimum = $requirements->minimumReinforcementStrengthCoefficient
            * ($concrete->fctm / $steel->fyk)
            * $tensionZoneArea;
        $absoluteMinimum = $requirements->minimumReinforcementRatio * $tensionZoneArea;
        $this->ensurePositiveFinite($strengthBasedMinimum, BeamMinimumTensionReinforcementRejectionReason::INVALID_MINIMUM_REINFORCEMENT_AREA);
        $this->ensurePositiveFinite($absoluteMinimum, BeamMinimumTensionReinforcementRejectionReason::INVALID_MINIMUM_REINFORCEMENT_AREA);

        $governingCriterion = $strengthBasedMinimum >= $absoluteMinimum
            ? MinimumTensionReinforcementGoverningCriterion::FCTM_FYK
            : MinimumTensionReinforcementGoverningCriterion::ABSOLUTE_RATIO;

        return new BeamMinimumTensionReinforcementResult(
            concreteClass: $concrete->strengthClass,
            meanTensileConcreteStrength: $concrete->fctm,
            steelGrade: $steel->grade,
            characteristicSteelStrength: $steel->fyk,
            tensionZoneMeanWidth: $geometry->width,
            effectiveDepth: $effectiveDepth->effectiveDepth,
            strengthBasedMinimum: $strengthBasedMinimum,
            absoluteMinimum: $absoluteMinimum,
            requiredMinimum: max($strengthBasedMinimum, $absoluteMinimum),
            governingCriterion: $governingCriterion,
        );
    }

    private function ensurePositiveFinite(float $value, BeamMinimumTensionReinforcementRejectionReason $reason): void
    {
        if (! is_finite($value) || $value <= 0) {
            throw new BeamMinimumTensionReinforcementException($reason);
        }
    }
}
