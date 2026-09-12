<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Eurocode\Concrete\ConcreteRectangularStressBlockParametersCalculator;

/** Résout ξ puis x pour la branche physique d'une poutre rectangulaire simplement armée. */
final readonly class BeamNeutralAxisCalculator
{
    public function __construct(private ConcreteRectangularStressBlockParametersCalculator $stressBlockParametersCalculator) {}

    public function calculate(
        BeamReducedMomentResult $reducedMoment,
        BeamEffectiveDepthResult $effectiveDepth,
        BeamFlexuralConcreteDesignStrength $concreteDesignStrength,
    ): BeamNeutralAxisResult {
        $this->ensureNonNegativeFinite($reducedMoment->reducedDesignMoment, BeamNeutralAxisRejectionReason::INVALID_REDUCED_DESIGN_MOMENT);
        $this->ensurePositiveFinite($effectiveDepth->effectiveDepth, BeamNeutralAxisRejectionReason::INVALID_EFFECTIVE_DEPTH);

        $stressBlock = $this->stressBlockParametersCalculator->calculate($concreteDesignStrength->fck);
        $this->ensurePositiveFinite($stressBlock->lambda, BeamNeutralAxisRejectionReason::INVALID_STRESS_BLOCK_LAMBDA);
        $this->ensurePositiveFinite($stressBlock->eta, BeamNeutralAxisRejectionReason::INVALID_STRESS_BLOCK_ETA);

        $radicand = 1 - 2 * $reducedMoment->reducedDesignMoment / $stressBlock->eta;

        if (! is_finite($radicand) || $radicand < 0) {
            throw new BeamNeutralAxisException(BeamNeutralAxisRejectionReason::INVALID_NEUTRAL_AXIS_RADICAND);
        }

        $neutralAxisRatio = (1 - sqrt($radicand)) / $stressBlock->lambda;

        return new BeamNeutralAxisResult(
            reducedDesignMoment: $reducedMoment->reducedDesignMoment,
            characteristicConcreteStrength: $stressBlock->fck,
            lambda: $stressBlock->lambda,
            eta: $stressBlock->eta,
            radicand: $radicand,
            neutralAxisRatio: $neutralAxisRatio,
            effectiveDepth: $effectiveDepth->effectiveDepth,
            neutralAxisDepth: $neutralAxisRatio * $effectiveDepth->effectiveDepth,
        );
    }

    private function ensureNonNegativeFinite(float $value, BeamNeutralAxisRejectionReason $reason): void
    {
        if (! is_finite($value) || $value < 0) {
            throw new BeamNeutralAxisException($reason);
        }
    }

    private function ensurePositiveFinite(float $value, BeamNeutralAxisRejectionReason $reason): void
    {
        if (! is_finite($value) || $value <= 0) {
            throw new BeamNeutralAxisException($reason);
        }
    }
}
