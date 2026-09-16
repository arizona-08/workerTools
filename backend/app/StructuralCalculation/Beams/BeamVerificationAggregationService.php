<?php

namespace App\StructuralCalculation\Beams;

/** Agrège les sorties des EPIC précédentes ; aucun effort ni résistance n'est recalculé. */
final readonly class BeamVerificationAggregationService
{
    public function __construct(private BeamVerificationStatusAggregator $statuses) {}

    public function aggregate(?BeamVerificationComponent $flexure, ?BeamVerificationComponent $shear, ?BeamVerificationComponent $stress, ?BeamVerificationComponent $crack, ?BeamVerificationComponent $deflection): BeamVerificationAggregationResult
    {
        $flexure ??= $this->missing('FLEXURE');
        $shear ??= $this->missing('SHEAR');
        $stress ??= $this->missing('STRESS');
        $crack ??= $this->missing('CRACK');
        $deflection ??= $this->missing('DEFLECTION');
        $uls = $this->statuses->aggregate([$flexure, $shear]);
        $sls = $this->statuses->aggregate([$stress, $crack, $deflection]);
        $warnings = array_values(array_unique(array_merge($flexure->warnings, $shear->warnings, $stress->warnings, $crack->warnings, $deflection->warnings)));

        return new BeamVerificationAggregationResult($this->statuses->aggregate([$flexure, $shear, $stress, $crack, $deflection]), $uls, $sls, $flexure, $shear, $stress, $crack, $deflection, $warnings);
    }

    public function flexure(BeamReinforcementCandidateRecalculationResult $result): BeamVerificationComponent
    {
        $status = match ($result->status) {
            BeamReinforcementCandidateRecalculationStatus::VALID_AFTER_RECALCULATION => BeamVerificationStatus::COMPLIANT,
            BeamReinforcementCandidateRecalculationStatus::INSUFFICIENT_AFTER_RECALCULATION => BeamVerificationStatus::NOT_COMPLIANT,
            BeamReinforcementCandidateRecalculationStatus::INVALID_SINGLY_REINFORCED_DOMAIN => BeamVerificationStatus::CALCULATION_METHOD_NOT_SUPPORTED,
        };

        return new BeamVerificationComponent('FLEXURE', $status, $result->requiredArea->requiredReinforcementArea / $result->providedArea, $result->providedArea, $result->requiredArea->requiredReinforcementArea, 'CANDIDATE_RECALCULATION');
    }

    public function shear(BeamMaximumShearResistanceResult $maximum, BeamStirrupProposalResult $proposals): BeamVerificationComponent
    {
        $status = $maximum->status === BeamMaximumShearResistanceStatus::MAXIMUM_SHEAR_RESISTANCE_OK && $proposals->recommendedCandidate !== null ? BeamVerificationStatus::COMPLIANT : BeamVerificationStatus::NOT_COMPLIANT;

        return new BeamVerificationComponent('SHEAR', $status, $maximum->utilizationMaximumShear, $maximum->designShearForce, $maximum->maximumShearResistance, 'SHEAR_CHAIN');
    }

    public function stress(BeamServiceStressVerificationResult $result): BeamVerificationComponent
    {
        $checks = [$result->concreteCharacteristic, $result->steelCharacteristic, $result->concreteQuasiPermanent];
        $governing = collect($checks)->first(fn (BeamServiceStressCheck $check): bool => $check->name === $result->governingStressCheck);

        return new BeamVerificationComponent('STRESS', BeamVerificationStatus::from($result->status->value), $governing?->utilization, $governing?->stress, $governing?->limit, $result->sectionModel, [], $governing?->name, $governing?->combination);
    }

    public function crack(BeamCrackVerificationResult $result): BeamVerificationComponent
    {
        return new BeamVerificationComponent('CRACK', BeamVerificationStatus::from($result->status->value), $result->utilization, $result->crackWidth, $result->crackWidthLimit, $result->sectionModel);
    }

    public function deflection(BeamDeflectionVerificationResult $result): BeamVerificationComponent
    {
        return new BeamVerificationComponent('DEFLECTION', BeamVerificationStatus::from($result->status->value), $result->utilization, $result->actualSpanDepthRatio, $result->allowableSpanDepthRatio, $result->method->value, $result->warnings);
    }

    /** Représente une limite explicitement déclarée par un sous-module, sans inventer de ratio. */
    public function methodNotSupported(string $identifier, string $method, array $warnings = [], ?float $governingValue = null): BeamVerificationComponent
    {
        return new BeamVerificationComponent($identifier, BeamVerificationStatus::CALCULATION_METHOD_NOT_SUPPORTED, null, $governingValue, null, $method, $warnings);
    }

    private function missing(string $identifier): BeamVerificationComponent
    {
        return new BeamVerificationComponent($identifier, BeamVerificationStatus::NOT_CHECKED, warnings: ['REQUIRED_VERIFICATION_MISSING']);
    }
}
