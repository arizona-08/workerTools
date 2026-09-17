<?php

namespace App\StructuralCalculation\Slabs;

use App\StructuralCalculation\Beams\BeamGoverningVerificationResolver;
use App\StructuralCalculation\Beams\BeamVerificationComponent;
use App\StructuralCalculation\Beams\BeamVerificationStatus;
use App\StructuralCalculation\Beams\BeamVerificationStatusAggregator;

/** Assemble SLAB-07 à SLAB-10 sans exécuter de formule ni modifier leurs résultats. */
final readonly class SlabCalculationResultAssembler
{
    public function __construct(
        private BeamVerificationStatusAggregator $statuses,
        private BeamGoverningVerificationResolver $governingResolver,
    ) {}

    public function assemble(SlabCalculationInput $input, SlabCharacteristicActions $actions, SlabActionCombinations $combinations, SlabStripAnalysis $analysis, SlabUlsFlexureResult $initialFlexure, SlabMainReinforcementProposalResult $main, SlabSecondaryReinforcementResult $secondary, SlabServiceabilityResult $serviceability): SlabCalculationResult
    {
        $finalFlexure = $main->proposal?->recalculatedFlexure ?? $initialFlexure;
        $flexureStatus = $main->proposal === null ? BeamVerificationStatus::NOT_CHECKED : BeamVerificationStatus::COMPLIANT;
        $flexure = new BeamVerificationComponent('FLEXURE', $flexureStatus, null, $finalFlexure->designMoment, null, 'ULS_FLEXURE', $main->proposal === null ? ['MISSING_MAIN_REINFORCEMENT_PROPOSAL'] : []);
        $mainVerification = new BeamVerificationComponent('MAIN_REINFORCEMENT', $this->mainStatus($main), null, $main->proposal?->providedAreaPerMeter, $main->proposal?->recalculatedFlexure->designReinforcementArea, 'SLAB_08', $main->proposal === null ? ['NO_VALID_REINFORCEMENT_PROPOSAL'] : []);
        $secondaryVerification = new BeamVerificationComponent('SECONDARY_REINFORCEMENT', $this->secondaryStatus($secondary), null, $secondary->proposal?->providedAreaPerMeter, $secondary->minimumRequiredAreaPerMeter, 'SLAB_09', $secondary->proposal === null ? ['NO_VALID_SECONDARY_REINFORCEMENT_PROPOSAL'] : []);
        $crack = new BeamVerificationComponent('CRACK', BeamVerificationStatus::from($serviceability->crackVerification->status->value), $serviceability->crackVerification->utilization, $serviceability->crackVerification->crackWidth, $serviceability->crackVerification->crackWidthLimit, $serviceability->crackVerification->method, $serviceability->crackVerification->warnings, null, $serviceability->crackVerification->loadCombination);
        $deflection = new BeamVerificationComponent('DEFLECTION', BeamVerificationStatus::from($serviceability->deflectionVerification->status->value), $serviceability->deflectionVerification->utilization, $serviceability->deflectionVerification->actualSpanDepthRatio, $serviceability->deflectionVerification->allowableSpanDepthRatio, $serviceability->deflectionVerification->method, $serviceability->deflectionVerification->warnings);
        $verifications = [$flexure, $mainVerification, $secondaryVerification, $crack, $deflection];
        $ulsStatus = $this->statuses->aggregate([$flexure, $mainVerification, $secondaryVerification]);
        $slsStatus = $this->statuses->aggregate([$crack, $deflection]);
        $overallStatus = $this->statuses->aggregate($verifications);
        $governing = $this->governingResolver->resolveComponents($verifications);
        $warnings = array_values(array_unique(array_merge(...array_map(fn (BeamVerificationComponent $verification): array => $verification->warnings, $verifications))));

        return new SlabCalculationResult(
            $overallStatus,
            new SlabResultSummary($overallStatus, $governing->governingVerification?->utilization, $governing->governingVerification?->identifier, $finalFlexure->designMoment, $finalFlexure->effectiveDepth->effectiveDepth, $finalFlexure->requiredReinforcementArea, $finalFlexure->minimumReinforcementArea, $main->proposal === null ? null : new SlabResultSummaryReinforcement($main->proposal->barDiameter, $main->proposal->spacing, $main->proposal->providedAreaPerMeter), $secondary->proposal === null ? null : new SlabResultSummaryReinforcement($secondary->proposal->barDiameter, $secondary->proposal->spacing, $secondary->proposal->providedAreaPerMeter)),
            $verifications,
            new SlabCalculationDetails(
                $overallStatus, $ulsStatus, $slsStatus, $governing->governingVerification,
                ['configuration' => $input->configuration, 'geometry' => $input->geometry, 'materials' => $input->materials],
                $actions,
                ['uls' => $combinations->uls, 'slsCharacteristic' => $combinations->slsCharacteristic, 'slsFrequent' => $combinations->slsFrequent, 'slsQuasiPermanent' => $combinations->slsQuasiPermanent],
                ['linearLoads' => $analysis->linearLoads, 'internalForces' => $analysis->internalForces],
                ['initial' => $initialFlexure, 'final' => $finalFlexure],
                ['proposal' => $main], ['proposal' => $secondary], ['crack' => $serviceability->crackVerification, 'deflection' => $serviceability->deflectionVerification], $warnings,
            ),
        );
    }

    private function mainStatus(SlabMainReinforcementProposalResult $main): BeamVerificationStatus
    {
        // Une flexion calculable sans proposition SLAB-08 est une insuffisance de
        // ferraillage disponible, pas une vérification simplement non exécutée.
        return $main->proposal === null ? BeamVerificationStatus::NOT_COMPLIANT : BeamVerificationStatus::COMPLIANT;
    }

    private function secondaryStatus(SlabSecondaryReinforcementResult $secondary): BeamVerificationStatus
    {
        return $secondary->proposal === null ? BeamVerificationStatus::NOT_CHECKED : BeamVerificationStatus::COMPLIANT;
    }
}
