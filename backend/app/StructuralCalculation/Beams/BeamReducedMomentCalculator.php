<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Units\MomentConverter;

/** Calcule μEd = MEd / (b d² fcd), avant tout modèle de bloc comprimé EC2. */
final readonly class BeamReducedMomentCalculator
{
    public function __construct(private MomentConverter $momentConverter) {}

    public function calculate(
        BeamBendingMoment $ultimateMoment,
        BeamGeometry $geometry,
        BeamEffectiveDepthResult $effectiveDepth,
        BeamFlexuralConcreteDesignStrength $concreteDesignStrength,
    ): BeamReducedMomentResult {
        $designMomentMagnitude = $ultimateMoment->magnitude();
        $this->ensureNonNegativeFinite($designMomentMagnitude, BeamReducedMomentRejectionReason::INVALID_DESIGN_MOMENT);
        $this->ensurePositiveFinite($geometry->width, BeamReducedMomentRejectionReason::INVALID_SECTION_WIDTH);
        $this->ensurePositiveFinite($effectiveDepth->effectiveDepth, BeamReducedMomentRejectionReason::INVALID_EFFECTIVE_DEPTH);
        $this->ensurePositiveFinite($concreteDesignStrength->fcd, BeamReducedMomentRejectionReason::INVALID_CONCRETE_DESIGN_STRENGTH);

        $designMomentInNewtonMillimetres = $this->momentConverter->kilonewtonMetresToNewtonMillimetres($designMomentMagnitude);
        $normalizationTerm = $geometry->width * $effectiveDepth->effectiveDepth ** 2 * $concreteDesignStrength->fcd;
        $this->ensurePositiveFinite($normalizationTerm, BeamReducedMomentRejectionReason::INVALID_NORMALIZATION_TERM);

        return new BeamReducedMomentResult(
            designMoment: $designMomentMagnitude,
            designMomentInNewtonMillimetres: $designMomentInNewtonMillimetres,
            sectionWidth: $geometry->width,
            effectiveDepth: $effectiveDepth->effectiveDepth,
            concreteDesignStrength: $concreteDesignStrength->fcd,
            normalizationTerm: $normalizationTerm,
            reducedDesignMoment: $designMomentInNewtonMillimetres / $normalizationTerm,
            signedDesignMoment: $ultimateMoment->maximumMoment,
        );
    }

    private function ensureNonNegativeFinite(float $value, BeamReducedMomentRejectionReason $reason): void
    {
        if (! is_finite($value) || $value < 0) {
            throw new BeamReducedMomentException($reason);
        }
    }

    private function ensurePositiveFinite(float $value, BeamReducedMomentRejectionReason $reason): void
    {
        if (! is_finite($value) || $value <= 0) {
            throw new BeamReducedMomentException($reason);
        }
    }
}
