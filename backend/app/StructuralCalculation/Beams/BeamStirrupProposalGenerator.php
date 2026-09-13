<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Eurocode\Cover\CoverCalculationResult;
use App\StructuralCalculation\Eurocode\Profiles\DesignCodeProfile;

/** Génère, filtre et classe le catalogue discret MVP des étriers à deux branches. */
final readonly class BeamStirrupProposalGenerator
{
    public function __construct(private BeamShearReinforcementResistanceCalculator $resistanceCalculator) {}

    public function generate(
        BeamShearReinforcementDesignResult $design,
        BeamMaximumShearResistanceResult $maximumResistance,
        BeamEffectiveDepthResult $effectiveDepth,
        CoverCalculationResult $cover,
        DesignCodeProfile $profile,
        ?BeamStirrupProposalConfiguration $configuration = null,
    ): BeamStirrupProposalResult {
        $configuration ??= new BeamStirrupProposalConfiguration;
        $requirements = $profile->beamConcreteShearResistanceRequirements;
        $maxLongitudinalSpacing = $requirements->maximumLongitudinalStirrupSpacingFactor * $effectiveDepth->effectiveDepth;
        $maxTransverseSpacing = min($requirements->maximumTransverseLegSpacingFactor * $effectiveDepth->effectiveDepth, $requirements->absoluteMaximumTransverseLegSpacing);
        $accepted = [];
        $rejected = [];
        foreach ($configuration->diameters as $diameter) {
            foreach ($configuration->spacings as $spacing) {
                $candidate = $this->candidate($diameter, $configuration->stirrupLegs, $spacing, $design, $maximumResistance, $cover->cNom, $maxLongitudinalSpacing, $maxTransverseSpacing);
                if ($candidate->accepted) {
                    $accepted[] = $candidate;
                } else {
                    $rejected[] = $candidate;
                }
            }
        }
        usort($accepted, fn (BeamStirrupProposalCandidate $left, BeamStirrupProposalCandidate $right): int => $left->reinforcementExcess <=> $right->reinforcementExcess
            ?: $left->spacing <=> $right->spacing
            ?: $left->barDiameter <=> $right->barDiameter);

        return new BeamStirrupProposalResult($accepted[0] ?? null, $accepted, $rejected, $maximumResistance->status === BeamMaximumShearResistanceStatus::MAXIMUM_SHEAR_RESISTANCE_OK);
    }

    private function candidate(float $diameter, int $legs, float $spacing, BeamShearReinforcementDesignResult $design, BeamMaximumShearResistanceResult $maximum, float $cover, float $maxLongitudinalSpacing, float $maxTransverseSpacing): BeamStirrupProposalCandidate
    {
        $barArea = M_PI * $diameter ** 2 / 4;
        $providedArea = $legs * $barArea;
        $providedPerLength = $providedArea / $spacing;
        $requiredMaximumSpacing = $providedArea / $design->targetShearReinforcementPerLength;
        $transverseSpacing = $design->webWidth - 2 * ($cover + $diameter / 2);
        $providedResistance = $this->resistanceCalculator->calculate($providedPerLength, $design->leverArm, $design->stirrupSteelDesignStrength, $design->cotTheta);
        $reasons = [];
        if ($maximum->status !== BeamMaximumShearResistanceStatus::MAXIMUM_SHEAR_RESISTANCE_OK) {
            $reasons[] = BeamStirrupProposalRejectionReason::MAXIMUM_SHEAR_RESISTANCE_EXCEEDED;
        }
        if ($providedPerLength < $design->targetShearReinforcementPerLength) {
            $reasons[] = BeamStirrupProposalRejectionReason::INSUFFICIENT_REINFORCEMENT_PER_LENGTH;
        }
        if ($spacing > $maxLongitudinalSpacing) {
            $reasons[] = BeamStirrupProposalRejectionReason::LONGITUDINAL_SPACING_EXCEEDED;
        }
        if ($transverseSpacing > $maxTransverseSpacing) {
            $reasons[] = BeamStirrupProposalRejectionReason::TRANSVERSE_LEG_SPACING_EXCEEDED;
        }
        if ($design->requiredByShearDemand && $providedResistance < $design->designShearForce) {
            $reasons[] = BeamStirrupProposalRejectionReason::SHEAR_RESISTANCE_INSUFFICIENT;
        }

        return new BeamStirrupProposalCandidate($diameter, $legs, $barArea, $providedArea, $spacing, $providedPerLength, $design->targetShearReinforcementPerLength, $providedPerLength - $design->targetShearReinforcementPerLength, $design->targetShearReinforcementPerLength / $providedPerLength, $requiredMaximumSpacing, $maxLongitudinalSpacing, $transverseSpacing, $maxTransverseSpacing, $providedResistance, $reasons === [], $reasons);
    }
}
