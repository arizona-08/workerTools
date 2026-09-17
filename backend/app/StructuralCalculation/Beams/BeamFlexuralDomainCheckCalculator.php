<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Eurocode\Concrete\ConcreteUltimateStrainParametersCalculator;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelProperties;

/** Vérifie que la compatibilité des déformations autorise l'hypothèse σs = fyd. */
final readonly class BeamFlexuralDomainCheckCalculator
{
    public const STRAIN_COMPARISON_TOLERANCE = 1.0e-12;

    public function __construct(private ConcreteUltimateStrainParametersCalculator $concreteUltimateStrains) {}

    public function calculate(
        BeamEffectiveDepthResult $effectiveDepth,
        BeamNeutralAxisResult $neutralAxis,
        BeamFlexuralConcreteDesignStrength $concreteDesignStrength,
        BeamFlexuralSteelDesignStrength $steelDesignStrength,
        ReinforcementSteelProperties $steel,
    ): BeamFlexuralDomainCheckResult {
        $this->ensurePositiveFinite($effectiveDepth->effectiveDepth, BeamFlexuralDomainCheckRejectionReason::INVALID_EFFECTIVE_DEPTH);
        if ($neutralAxis->effectiveDepth !== $effectiveDepth->effectiveDepth) {
            throw new BeamFlexuralDomainCheckException(BeamFlexuralDomainCheckRejectionReason::INCONSISTENT_EFFECTIVE_DEPTH);
        }
        $this->ensureNonNegativeFinite($neutralAxis->neutralAxisDepth, BeamFlexuralDomainCheckRejectionReason::INVALID_NEUTRAL_AXIS_DEPTH);
        if ($neutralAxis->neutralAxisDepth > $effectiveDepth->effectiveDepth) {
            throw new BeamFlexuralDomainCheckException(BeamFlexuralDomainCheckRejectionReason::NEUTRAL_AXIS_BEYOND_EFFECTIVE_DEPTH);
        }
        $this->ensureNonNegativeFinite($neutralAxis->neutralAxisRatio, BeamFlexuralDomainCheckRejectionReason::INVALID_NEUTRAL_AXIS_RATIO);
        if ($neutralAxis->neutralAxisRatio > 1) {
            throw new BeamFlexuralDomainCheckException(BeamFlexuralDomainCheckRejectionReason::INVALID_NEUTRAL_AXIS_RATIO);
        }
        if (abs($neutralAxis->neutralAxisRatio - $neutralAxis->neutralAxisDepth / $effectiveDepth->effectiveDepth) > self::STRAIN_COMPARISON_TOLERANCE) {
            throw new BeamFlexuralDomainCheckException(BeamFlexuralDomainCheckRejectionReason::INCONSISTENT_NEUTRAL_AXIS);
        }
        $this->ensurePositiveFinite($steelDesignStrength->fyd, BeamFlexuralDomainCheckRejectionReason::INVALID_STEEL_DESIGN_STRENGTH);
        $this->ensurePositiveFinite($steel->es, BeamFlexuralDomainCheckRejectionReason::INVALID_STEEL_ELASTIC_MODULUS);

        $concreteUltimateStrain = $this->concreteUltimateStrains->calculate($concreteDesignStrength->fck)->ultimateStrain;
        $steelDesignYieldStrain = $steelDesignStrength->fyd / $steel->es;
        $yieldingNeutralAxisLimit = $concreteUltimateStrain / ($concreteUltimateStrain + $steelDesignYieldStrain);

        if ($neutralAxis->neutralAxisDepth === 0.0) {
            return new BeamFlexuralDomainCheckResult(
                characteristicConcreteStrength: $concreteDesignStrength->fck,
                concreteUltimateStrain: $concreteUltimateStrain,
                steelDesignStrength: $steelDesignStrength->fyd,
                steelElasticModulus: $steel->es,
                steelDesignYieldStrain: $steelDesignYieldStrain,
                effectiveDepth: $effectiveDepth->effectiveDepth,
                neutralAxisDepth: 0.0,
                neutralAxisRatio: 0.0,
                tensionSteelStrain: null,
                yieldingNeutralAxisLimit: $yieldingNeutralAxisLimit,
                tensionSteelReachesDesignYield: null,
                singlyReinforcedModelValid: true,
            );
        }

        $tensionSteelStrain = $concreteUltimateStrain * (1 - $neutralAxis->neutralAxisRatio) / $neutralAxis->neutralAxisRatio;
        $tensionSteelReachesDesignYield = $tensionSteelStrain + self::STRAIN_COMPARISON_TOLERANCE >= $steelDesignYieldStrain;

        return new BeamFlexuralDomainCheckResult(
            characteristicConcreteStrength: $concreteDesignStrength->fck,
            concreteUltimateStrain: $concreteUltimateStrain,
            steelDesignStrength: $steelDesignStrength->fyd,
            steelElasticModulus: $steel->es,
            steelDesignYieldStrain: $steelDesignYieldStrain,
            effectiveDepth: $effectiveDepth->effectiveDepth,
            neutralAxisDepth: $neutralAxis->neutralAxisDepth,
            neutralAxisRatio: $neutralAxis->neutralAxisRatio,
            tensionSteelStrain: $tensionSteelStrain,
            yieldingNeutralAxisLimit: $yieldingNeutralAxisLimit,
            tensionSteelReachesDesignYield: $tensionSteelReachesDesignYield,
            singlyReinforcedModelValid: $tensionSteelReachesDesignYield,
        );
    }

    private function ensureNonNegativeFinite(float $value, BeamFlexuralDomainCheckRejectionReason $reason): void
    {
        if (! is_finite($value) || $value < 0) {
            throw new BeamFlexuralDomainCheckException($reason);
        }
    }

    private function ensurePositiveFinite(float $value, BeamFlexuralDomainCheckRejectionReason $reason): void
    {
        if (! is_finite($value) || $value <= 0) {
            throw new BeamFlexuralDomainCheckException($reason);
        }
    }
}
