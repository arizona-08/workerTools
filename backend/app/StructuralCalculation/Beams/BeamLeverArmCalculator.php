<?php

namespace App\StructuralCalculation\Beams;

/** Détermine z depuis le bloc comprimé λx de l'axe neutre déjà résolu. */
final class BeamLeverArmCalculator
{
    public function calculate(BeamEffectiveDepthResult $effectiveDepth, BeamNeutralAxisResult $neutralAxis): BeamLeverArmResult
    {
        $this->ensurePositiveFinite($effectiveDepth->effectiveDepth, BeamLeverArmRejectionReason::INVALID_EFFECTIVE_DEPTH);
        if ($neutralAxis->effectiveDepth !== $effectiveDepth->effectiveDepth) {
            throw new BeamLeverArmException(BeamLeverArmRejectionReason::INCONSISTENT_EFFECTIVE_DEPTH);
        }
        $this->ensureNonNegativeFinite($neutralAxis->neutralAxisDepth, BeamLeverArmRejectionReason::INVALID_NEUTRAL_AXIS_DEPTH);
        if ($neutralAxis->neutralAxisDepth > $effectiveDepth->effectiveDepth) {
            throw new BeamLeverArmException(BeamLeverArmRejectionReason::NEUTRAL_AXIS_BEYOND_EFFECTIVE_DEPTH);
        }
        $this->ensurePositiveFinite($neutralAxis->lambda, BeamLeverArmRejectionReason::INVALID_STRESS_BLOCK_LAMBDA);

        $compressionBlockDepth = $neutralAxis->lambda * $neutralAxis->neutralAxisDepth;
        $compressionResultantDepth = $compressionBlockDepth / 2;
        $leverArm = $effectiveDepth->effectiveDepth - $compressionResultantDepth;
        $this->ensurePositiveFinite($leverArm, BeamLeverArmRejectionReason::NON_POSITIVE_LEVER_ARM);

        return new BeamLeverArmResult(
            effectiveDepth: $effectiveDepth->effectiveDepth,
            neutralAxisDepth: $neutralAxis->neutralAxisDepth,
            neutralAxisRatio: $neutralAxis->neutralAxisRatio,
            lambda: $neutralAxis->lambda,
            compressionBlockDepth: $compressionBlockDepth,
            compressionResultantDepth: $compressionResultantDepth,
            leverArm: $leverArm,
        );
    }

    private function ensurePositiveFinite(float $value, BeamLeverArmRejectionReason $reason): void
    {
        if (! is_finite($value) || $value <= 0) {
            throw new BeamLeverArmException($reason);
        }
    }

    private function ensureNonNegativeFinite(float $value, BeamLeverArmRejectionReason $reason): void
    {
        if (! is_finite($value) || $value < 0) {
            throw new BeamLeverArmException($reason);
        }
    }
}
