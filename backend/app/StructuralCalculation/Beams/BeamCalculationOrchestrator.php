<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Eurocode\Actions\ReinforcedConcreteUnitWeightRepository;
use App\StructuralCalculation\Eurocode\Cover\CoverCalculationInput;
use App\StructuralCalculation\Eurocode\Cover\CoverMode;
use App\StructuralCalculation\Eurocode\Cover\NominalCoverCalculator;
use App\StructuralCalculation\Eurocode\Profiles\FrenchEurocodeProfileRepository;
use App\StructuralCalculation\Materials\Concrete\ConcreteClassRepository;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGradeRepository;
use LogicException;

/**
 * Point d'entrée du moteur Poutre MVP.
 *
 * Il compose exclusivement les résultats des étapes précédentes : aucune formule
 * mécanique, règle normative ou décision de conformité n'est définie ici.
 */
final readonly class BeamCalculationOrchestrator
{
    public function __construct(
        private FrenchEurocodeProfileRepository $profiles,
        private ReinforcedConcreteUnitWeightRepository $unitWeights,
        private ConcreteClassRepository $concreteClasses,
        private ReinforcementSteelGradeRepository $steelGrades,
        private SelfWeightCalculator $selfWeightCalculator,
        private CharacteristicActionsCalculator $characteristicActionsCalculator,
        private BeamUltimateCombinationCalculator $ultimateCombinationCalculator,
        private BeamServiceabilityCombinationCalculator $serviceabilityCombinationCalculator,
        private SimplySupportedBeamBendingMomentCalculator $bendingMomentCalculator,
        private SimplySupportedBeamShearForceCalculator $shearForceCalculator,
        private NominalCoverCalculator $coverCalculator,
        private BeamEffectiveDepthCalculator $effectiveDepthCalculator,
        private BeamFlexuralDesignStrengthsCalculator $designStrengthsCalculator,
        private BeamReducedMomentCalculator $reducedMomentCalculator,
        private BeamNeutralAxisCalculator $neutralAxisCalculator,
        private BeamLeverArmCalculator $leverArmCalculator,
        private BeamRequiredTensionReinforcementCalculator $requiredReinforcementCalculator,
        private BeamMinimumTensionReinforcementCalculator $minimumReinforcementCalculator,
        private BeamFlexuralDomainCheckCalculator $flexuralDomainCheckCalculator,
        private BeamReinforcementTargetCalculator $reinforcementTargetCalculator,
        private BeamReinforcementCandidatesGenerator $reinforcementCandidatesGenerator,
        private BeamReinforcementGeometryFilter $reinforcementGeometryFilter,
        private BeamReinforcementCandidateRecalculator $reinforcementCandidateRecalculator,
        private BeamConcreteShearResistanceCalculator $concreteShearCalculator,
        private BeamShearReinforcementDesignCalculator $shearReinforcementCalculator,
        private BeamMaximumShearResistanceCalculator $maximumShearCalculator,
        private BeamStirrupProposalGenerator $stirrupProposalGenerator,
        private BeamServiceStressVerificationCalculator $serviceStressCalculator,
        private BeamCrackVerificationCalculator $crackCalculator,
        private BeamDeflectionVerificationCalculator $deflectionCalculator,
        private BeamVerificationAggregationService $aggregationService,
        private BeamGoverningVerificationResolver $governingResolver,
        private BeamResultSummaryBuilder $summaryBuilder,
        private BeamResultDetailsBuilder $detailsBuilder,
    ) {}

    public function calculate(BeamCalculationSetup $setup): BeamCalculationResponse
    {
        if ($setup->materials === null || $setup->permanentLoads === null || $setup->variableLoad === null) {
            throw new LogicException('INCOMPLETE_BEAM_CALCULATION_SETUP');
        }

        $profile = $this->profiles->get();
        $concrete = $this->concreteClasses->get($setup->materials->concreteClass);
        $steel = $this->steelGrades->get($setup->materials->steelGrade);
        $flexuralDetailing = BeamFlexuralDetailingAssumptions::mvp();
        $reinforcementDetailing = new BeamReinforcementDetailingAssumptions;

        $selfWeight = $this->selfWeightCalculator->calculate($setup->geometry, $setup->permanentLoads->includeSelfWeight, $this->unitWeights->normalWeightReinforcedConcrete());
        $actions = $this->characteristicActionsCalculator->calculate($selfWeight, $setup->permanentLoads, $setup->variableLoad);
        $ultimate = $this->ultimateCombinationCalculator->calculate($actions, $profile);
        $serviceability = $this->serviceabilityCombinationCalculator->calculate($actions, $profile);
        $moments = $this->bendingMomentCalculator->calculate($setup->configuration, $setup->geometry, $ultimate, $serviceability);
        $shears = $this->shearForceCalculator->calculate($setup->configuration, $setup->geometry, $ultimate, $serviceability);

        $cover = $this->coverCalculator->calculate(new CoverCalculationInput(
            CoverMode::AUTO,
            $setup->materials->exposureClasses,
            $setup->materials->concreteClass,
            50,
            $flexuralDetailing->transverseReinforcementDiameter,
        ), $profile);
        $initialDepth = $this->effectiveDepthCalculator->calculate($setup->configuration->calculationMode, $setup->geometry, $cover, $flexuralDetailing, $setup->longitudinalReinforcement);
        $strengths = $this->designStrengthsCalculator->calculate($setup->materials, $profile);
        $reduced = $this->reducedMomentCalculator->calculate($moments->ultimate, $setup->geometry, $initialDepth, $strengths->concrete);
        $neutral = $this->neutralAxisCalculator->calculate($reduced, $initialDepth, $strengths->concrete);
        $lever = $this->leverArmCalculator->calculate($initialDepth, $neutral);
        $required = $this->requiredReinforcementCalculator->calculate($moments->ultimate, $strengths->steel, $lever);
        $minimum = $this->minimumReinforcementCalculator->calculate($concrete, $steel, $setup->geometry, $initialDepth, $profile->beamLongitudinalReinforcementRequirements);
        $domain = $this->flexuralDomainCheckCalculator->calculate($initialDepth, $neutral, $strengths->concrete, $strengths->steel, $steel);
        $target = $this->reinforcementTargetCalculator->calculate($required, $minimum, $domain);

        $candidates = $setup->configuration->calculationMode === BeamCalculationMode::DESIGN
            ? $this->reinforcementCandidatesGenerator->generate($target)
            : $this->providedReinforcementCandidate($setup, $target);
        $geometricallyAdmissible = $this->reinforcementGeometryFilter->filter($candidates, $setup->geometry, $cover, $flexuralDetailing, $reinforcementDetailing, $profile->reinforcementSpacingRequirements);
        $recalculated = $this->reinforcementCandidateRecalculator->recalculate(
            $geometricallyAdmissible,
            $moments->ultimate,
            $setup->geometry,
            $cover,
            $flexuralDetailing,
            $strengths,
            $concrete,
            $steel,
            $profile,
            $initialDepth,
            $required,
        );
        $selected = $recalculated->validCandidates[0] ?? throw new LogicException('NO_VALID_LONGITUDINAL_REINFORCEMENT_CANDIDATE');

        $concreteShear = $this->concreteShearCalculator->calculate($shears->ultimate, $setup->configuration, $setup->geometry, $selected->effectiveDepth, $concrete, $strengths->concrete, $selected->providedArea, $profile);
        $shearDesign = $this->shearReinforcementCalculator->calculate($concreteShear, $selected->leverArm, $concrete, $steel, $profile, BeamShearDesignAssumptions::mvp());
        $maximumShear = $this->maximumShearCalculator->calculate($shearDesign, $concrete, $strengths->concrete, $profile);
        $stirrups = $this->stirrupProposalGenerator->generate($shearDesign, $maximumShear, $selected->effectiveDepth, $cover, $profile);
        $stirrup = $stirrups->recommendedCandidate ?? throw new LogicException('NO_VALID_STIRRUP_CANDIDATE');

        $stresses = $this->serviceStressCalculator->calculate($moments, $setup->geometry, $selected->effectiveDepth, $concrete, $steel, $selected->providedArea, $profile);
        $cracks = $this->crackCalculator->calculate($setup->geometry, $selected->effectiveDepth, $selected->originalCandidate, $stirrups, $stresses, $concrete, $steel, $setup->materials->exposureClasses[0], $profile);
        $deflection = $this->deflectionCalculator->calculate($setup->configuration, $setup->geometry, $selected, $concrete, $steel, $profile);

        $aggregation = $this->aggregationService->aggregate(
            $this->aggregationService->flexure($selected),
            $this->aggregationService->shear($maximumShear, $stirrups),
            $this->aggregationService->stress($stresses),
            $this->aggregationService->crack($cracks),
            $this->aggregationService->deflection($deflection),
        );
        $governing = $this->governingResolver->resolve($aggregation);
        $summary = $this->summaryBuilder->build($aggregation, $governing, $moments->ultimate, $selected->effectiveDepth, $selected->requiredArea, $selected->originalCandidate, $setup->configuration->calculationMode);
        $details = $this->detailsBuilder->build(
            $aggregation,
            $governing,
            $summary,
            ['configuration' => $setup->configuration, 'geometry' => $setup->geometry, 'materials' => $setup->materials, 'cover' => $cover],
            ['characteristicActions' => $actions, 'ultimate' => $ultimate, 'serviceability' => $serviceability],
            ['bendingMoments' => $moments, 'shearForces' => $shears],
            ['designStrengths' => $strengths, 'initialEffectiveDepth' => $initialDepth, 'reducedMoment' => $reduced, 'neutralAxis' => $neutral, 'leverArm' => $lever, 'domain' => $domain],
            ['targetArea' => $target, 'generatedCandidates' => $candidates, 'geometryCandidates' => $geometricallyAdmissible, 'selectedCandidate' => $selected],
            ['concreteResistance' => $concreteShear, 'reinforcementDesign' => $shearDesign, 'maximumResistance' => $maximumShear, 'stirrups' => $stirrups, 'recommendedStirrup' => $stirrup],
            ['stress' => $stresses, 'crack' => $cracks, 'deflection' => $deflection, 'warnings' => [...$deflection->warnings]],
        );

        return new BeamCalculationResponse($summary, $aggregation, $details);
    }

    private function providedReinforcementCandidate(BeamCalculationSetup $setup, BeamRequiredReinforcementAreaResult $target): BeamReinforcementCandidatesResult
    {
        $reinforcement = $setup->longitudinalReinforcement ?? throw new LogicException('MISSING_VERIFICATION_REINFORCEMENT');
        $providedArea = $reinforcement->providedSteelArea;
        $candidate = new BeamReinforcementProposalCandidate(
            $reinforcement->tensionBarCount,
            $reinforcement->tensionBarDiameter,
            $providedArea / $reinforcement->tensionBarCount,
            $providedArea,
            $target->targetArea,
            $providedArea - $target->targetArea,
            $target->targetArea / $providedArea,
        );

        return new BeamReinforcementCandidatesResult($target->targetArea, [$reinforcement->tensionBarDiameter], $reinforcement->tensionBarCount, $reinforcement->tensionBarCount, BeamReinforcementCandidatesStatus::CANDIDATES_AVAILABLE, [$candidate], 1);
    }
}
