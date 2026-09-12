<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Eurocode\Cover\CoverCalculationResult;

/** Détermine d depuis l'enrobage EC2-05 et le centre du seul lit tendu du MVP. */
final class BeamEffectiveDepthCalculator
{
    public function calculate(
        BeamCalculationMode $mode,
        BeamGeometry $geometry,
        CoverCalculationResult $cover,
        BeamFlexuralDetailingAssumptions $detailing,
        ?BeamLongitudinalReinforcement $reinforcement = null,
    ): BeamEffectiveDepthResult {
        $this->ensurePositiveFinite($geometry->height, BeamEffectiveDepthRejectionReason::INVALID_OVERALL_DEPTH);
        $this->ensureCover($cover);
        $this->ensurePositiveFinite($detailing->transverseReinforcementDiameter, BeamEffectiveDepthRejectionReason::INVALID_TRANSVERSE_REINFORCEMENT_DIAMETER);

        [$longitudinalDiameter, $diameterSource] = $this->longitudinalDiameter($mode, $detailing, $reinforcement);
        $centroidOffset = $cover->cNom + $detailing->transverseReinforcementDiameter + $longitudinalDiameter / 2;
        $effectiveDepth = $geometry->height - $centroidOffset;

        if (! is_finite($effectiveDepth) || $effectiveDepth <= 0) {
            throw new BeamEffectiveDepthException(BeamEffectiveDepthRejectionReason::NON_POSITIVE_EFFECTIVE_DEPTH);
        }

        return new BeamEffectiveDepthResult(
            mode: $mode,
            overallDepth: $geometry->height,
            nominalCover: $cover->cNom,
            transverseBarDiameter: $detailing->transverseReinforcementDiameter,
            longitudinalBarDiameter: $longitudinalDiameter,
            longitudinalBarDiameterSource: $diameterSource,
            tensionSteelCentroidOffset: $centroidOffset,
            effectiveDepth: $effectiveDepth,
        );
    }

    /** @return array{float, LongitudinalBarDiameterSource} */
    private function longitudinalDiameter(
        BeamCalculationMode $mode,
        BeamFlexuralDetailingAssumptions $detailing,
        ?BeamLongitudinalReinforcement $reinforcement,
    ): array {
        if ($mode === BeamCalculationMode::DESIGN) {
            $this->ensurePositiveFinite($detailing->designTensionBarDiameter, BeamEffectiveDepthRejectionReason::INVALID_DESIGN_TENSION_BAR_DIAMETER);

            return [$detailing->designTensionBarDiameter, LongitudinalBarDiameterSource::CONFIG];
        }

        if ($reinforcement === null) {
            throw new BeamEffectiveDepthException(BeamEffectiveDepthRejectionReason::MISSING_VERIFICATION_REINFORCEMENT);
        }
        if ($reinforcement->tensionRebarLayers !== 1) {
            throw new BeamEffectiveDepthException(BeamEffectiveDepthRejectionReason::UNSUPPORTED_TENSION_REINFORCEMENT_LAYERS);
        }
        $this->ensurePositiveFinite($reinforcement->tensionBarDiameter, BeamEffectiveDepthRejectionReason::INVALID_VERIFICATION_TENSION_BAR_DIAMETER);

        return [$reinforcement->tensionBarDiameter, LongitudinalBarDiameterSource::USER];
    }

    private function ensureCover(CoverCalculationResult $cover): void
    {
        if ($cover->unit !== BeamEffectiveDepthResult::UNIT) {
            throw new BeamEffectiveDepthException(BeamEffectiveDepthRejectionReason::INVALID_NOMINAL_COVER_UNIT);
        }
        if (! is_finite($cover->cNom) || $cover->cNom < 0) {
            throw new BeamEffectiveDepthException(BeamEffectiveDepthRejectionReason::INVALID_NOMINAL_COVER);
        }
    }

    private function ensurePositiveFinite(float $value, BeamEffectiveDepthRejectionReason $reason): void
    {
        if (! is_finite($value) || $value <= 0) {
            throw new BeamEffectiveDepthException($reason);
        }
    }
}
