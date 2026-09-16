<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Eurocode\Actions\ReinforcedConcreteUnitWeightRepository;
use App\StructuralCalculation\Eurocode\Cover\CoverCalculationInput;
use App\StructuralCalculation\Eurocode\Cover\CoverMode;
use App\StructuralCalculation\Eurocode\Cover\NominalCoverCalculator;
use App\StructuralCalculation\Eurocode\Profiles\DesignCodeProfile;
use App\StructuralCalculation\Eurocode\Profiles\FrenchEurocodeProfileRepository;
use App\StructuralCalculation\Materials\Concrete\ConcreteClassRepository;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGradeRepository;
use LogicException;

/**
 * Point d'entrée du moteur Poutre V1.
 *
 * Il compose exclusivement les résultats des étapes précédentes : aucune formule
 * mécanique, règle normative ou décision de conformité n'est définie ici.
 */
final readonly class BeamCalculationOrchestrator
{
    public function __construct(
        private BeamCalculationCapabilities $capabilities,
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
        private CantileverBeamBendingMomentCalculator $cantileverBendingMomentCalculator,
        private CantileverBeamShearForceCalculator $cantileverShearForceCalculator,
        private CantileverBeamShearVerificationScope $cantileverShearVerificationScope,
        private CantileverBeamCrackVerificationScope $cantileverCrackVerificationScope,
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
        return match ($setup->configuration->submodule) {
            BeamSubmodule::BEAM_SIMPLE_RECTANGULAR => $this->calculateSimplySupported($setup),
            BeamSubmodule::BEAM_CANTILEVER_RECTANGULAR => $this->calculateCantileverAnalysis($setup),
        };
    }

    /** Assemble les résultats console disponibles sans transformer les limites de méthode en conformité. */
    private function calculateCantileverAnalysis(BeamCalculationSetup $setup): BeamCalculationResponse
    {
        if ($setup->materials === null || $setup->permanentLoads === null || $setup->variableLoad === null) {
            throw new LogicException('INCOMPLETE_BEAM_CALCULATION_SETUP');
        }
        $this->capabilities->validate($setup->materials);

        $profile = $this->profiles->get();
        $selfWeight = $this->selfWeightCalculator->calculate($setup->geometry, $setup->permanentLoads->includeSelfWeight, $this->unitWeights->normalWeightReinforcedConcrete());
        $actions = $this->characteristicActionsCalculator->calculate($selfWeight, $setup->permanentLoads, $setup->variableLoad);
        $ultimate = $this->ultimateCombinationCalculator->calculate($actions, $profile);
        $serviceability = $this->serviceabilityCombinationCalculator->calculate($actions, $profile);

        $moments = $this->cantileverBendingMomentCalculator->calculate($setup->configuration, $setup->geometry, $ultimate, $serviceability);
        $shears = $this->cantileverShearForceCalculator->calculate($setup->configuration, $setup->geometry, $ultimate, $serviceability);
        $flexure = $this->calculateFlexure($setup, $profile, $moments->ultimate);
        $shearScope = $this->cantileverShearVerificationScope->assess($setup->configuration, $shears->ultimate, $flexure->selectedCandidate->originalCandidate);
        $concrete = $this->concreteClasses->get($setup->materials->concreteClass);
        $steel = $this->steelGrades->get($setup->materials->steelGrade);
        $stresses = $this->serviceStressCalculator->calculate($moments, $setup->geometry, $flexure->selectedCandidate->effectiveDepth, $concrete, $steel, $flexure->selectedCandidate->providedArea, $profile);
        $crackScope = $this->cantileverCrackVerificationScope->assess($flexure->selectedCandidate->originalCandidate, $setup->configuration->tensionFace());
        $deflection = $this->deflectionCalculator->calculate($setup->configuration, $setup->geometry, $flexure->selectedCandidate, $concrete, $steel, $profile);

        $aggregation = $this->aggregationService->aggregate(
            $this->aggregationService->flexure($flexure->selectedCandidate),
            $this->aggregationService->methodNotSupported('SHEAR', 'CANTILEVER_FIXED_END_SCOPE', [$shearScope->limitation], $shearScope->designShearForce->maximumAbsoluteShear),
            $this->aggregationService->stress($stresses),
            $this->aggregationService->methodNotSupported('CRACK', 'CANTILEVER_FIXED_END_SCOPE', [$crackScope->limitation]),
            $this->aggregationService->deflection($deflection),
        );
        $governing = $this->governingResolver->resolve($aggregation);
        $summary = $this->summaryBuilder->build($aggregation, $governing, $moments->ultimate, $flexure->selectedCandidate->effectiveDepth, $flexure->selectedCandidate->requiredArea, $flexure->selectedCandidate->originalCandidate, $setup->configuration->calculationMode, $setup->configuration, $shears->ultimate);
        $details = $this->detailsBuilder->build(
            $aggregation,
            $governing,
            $summary,
            [
                'configuration' => $setup->configuration,
                'geometry' => $setup->geometry,
                'materials' => $setup->materials,
                'concreteClass' => $setup->materials->concreteClass->value,
                'steelGrade' => $setup->materials->steelGrade->value,
                'exposureClass' => $setup->materials->exposureClasses[0]->value,
                'cover' => $flexure->cover,
                'tensionFace' => $setup->configuration->tensionFace(),
            ],
            ['characteristicActions' => $actions, 'ultimate' => $ultimate, 'serviceability' => $serviceability],
            ['bendingMoments' => $moments, 'shearForces' => $shears, 'criticalSectionLocation' => BeamShearCriticalSectionLocation::FIXED_END],
            ['designStrengths' => $flexure->designStrengths, 'initialEffectiveDepth' => $flexure->initialEffectiveDepth, 'reducedMoment' => $flexure->reducedMoment, 'neutralAxis' => $flexure->neutralAxis, 'leverArm' => $flexure->leverArm, 'domain' => $flexure->domain],
            ['targetArea' => $flexure->targetArea, 'generatedCandidates' => $flexure->generatedCandidates, 'geometryCandidates' => $flexure->geometryCandidates, 'selectedCandidate' => $flexure->selectedCandidate],
            ['scope' => $shearScope],
            ['stress' => $stresses, 'crack' => $crackScope, 'deflection' => $deflection, 'warnings' => [...$aggregation->warnings]],
        );

        return new BeamCalculationResponse($summary, $aggregation, $details);
    }

    /** Le pipeline existant est réservé explicitement au sous-module simplement appuyé. */
    private function calculateSimplySupported(BeamCalculationSetup $setup): BeamCalculationResponse
    {
        if ($setup->materials === null || $setup->permanentLoads === null || $setup->variableLoad === null) {
            throw new LogicException('INCOMPLETE_BEAM_CALCULATION_SETUP');
        }
        $this->capabilities->validate($setup->materials);

        $profile = $this->profiles->get();
        $selfWeight = $this->selfWeightCalculator->calculate($setup->geometry, $setup->permanentLoads->includeSelfWeight, $this->unitWeights->normalWeightReinforcedConcrete());
        $actions = $this->characteristicActionsCalculator->calculate($selfWeight, $setup->permanentLoads, $setup->variableLoad);
        $ultimate = $this->ultimateCombinationCalculator->calculate($actions, $profile);
        $serviceability = $this->serviceabilityCombinationCalculator->calculate($actions, $profile);
        $moments = $this->bendingMomentCalculator->calculate($setup->configuration, $setup->geometry, $ultimate, $serviceability);
        $shears = $this->shearForceCalculator->calculate($setup->configuration, $setup->geometry, $ultimate, $serviceability);

        $flexure = $this->calculateFlexure($setup, $profile, $moments->ultimate);
        $concrete = $this->concreteClasses->get($setup->materials->concreteClass);
        $steel = $this->steelGrades->get($setup->materials->steelGrade);
        $cover = $flexure->cover;
        $initialDepth = $flexure->initialEffectiveDepth;
        $strengths = $flexure->designStrengths;
        $reduced = $flexure->reducedMoment;
        $neutral = $flexure->neutralAxis;
        $lever = $flexure->leverArm;
        $required = $flexure->requiredArea;
        $domain = $flexure->domain;
        $target = $flexure->targetArea;
        $candidates = $flexure->generatedCandidates;
        $geometricallyAdmissible = $flexure->geometryCandidates;
        $selected = $flexure->selectedCandidate;

        $concreteShear = $this->concreteShearCalculator->calculate($shears->ultimate, $setup->configuration, $setup->geometry, $selected->effectiveDepth, $concrete, $strengths->concrete, $selected->providedArea, $profile);
        $shearDesign = $this->shearReinforcementCalculator->calculate($concreteShear, $selected->leverArm, $concrete, $steel, $profile, BeamShearDesignAssumptions::supported());
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
        $summary = $this->summaryBuilder->build($aggregation, $governing, $moments->ultimate, $selected->effectiveDepth, $selected->requiredArea, $selected->originalCandidate, $setup->configuration->calculationMode, $setup->configuration, $shears->ultimate);
        $details = $this->detailsBuilder->build(
            $aggregation,
            $governing,
            $summary,
            [
                'configuration' => $setup->configuration,
                'geometry' => $setup->geometry,
                'materials' => $setup->materials,
                'concreteClass' => $setup->materials->concreteClass->value,
                'steelGrade' => $setup->materials->steelGrade->value,
                'exposureClass' => $setup->materials->exposureClasses[0]->value,
                'cover' => $cover,
            ],
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
            $setup->configuration->tensionFace()->reinforcementPosition(),
        );

        return new BeamReinforcementCandidatesResult($target->targetArea, [$reinforcement->tensionBarDiameter], $reinforcement->tensionBarCount, $reinforcement->tensionBarCount, BeamReinforcementCandidatesStatus::CANDIDATES_AVAILABLE, [$candidate], 1);
    }

    /** Réutilisé par les deux sous-modules : le signe de MEd est porté par BeamBendingMoment. */
    private function calculateFlexure(BeamCalculationSetup $setup, DesignCodeProfile $profile, BeamBendingMoment $ultimateMoment): BeamFlexuralDesignResult
    {
        $materials = $setup->materials ?? throw new LogicException('INCOMPLETE_BEAM_CALCULATION_SETUP');
        $concrete = $this->concreteClasses->get($materials->concreteClass);
        $steel = $this->steelGrades->get($materials->steelGrade);
        $detailing = BeamFlexuralDetailingAssumptions::supported();
        $reinforcementDetailing = new BeamReinforcementDetailingAssumptions;
        $tensionFace = $setup->configuration->tensionFace();
        $cover = $this->coverCalculator->calculate(new CoverCalculationInput(CoverMode::AUTO, $materials->exposureClasses, $materials->concreteClass, 50, $detailing->transverseReinforcementDiameter), $profile);
        $initialDepth = $this->effectiveDepthCalculator->calculate($setup->configuration->calculationMode, $setup->geometry, $cover, $detailing, $setup->longitudinalReinforcement, $tensionFace);
        $strengths = $this->designStrengthsCalculator->calculate($materials, $profile);
        $reduced = $this->reducedMomentCalculator->calculate($ultimateMoment, $setup->geometry, $initialDepth, $strengths->concrete);
        $neutral = $this->neutralAxisCalculator->calculate($reduced, $initialDepth, $strengths->concrete);
        $lever = $this->leverArmCalculator->calculate($initialDepth, $neutral);
        $required = $this->requiredReinforcementCalculator->calculate($ultimateMoment, $strengths->steel, $lever);
        $minimum = $this->minimumReinforcementCalculator->calculate($concrete, $steel, $setup->geometry, $initialDepth, $profile->beamLongitudinalReinforcementRequirements);
        $domain = $this->flexuralDomainCheckCalculator->calculate($initialDepth, $neutral, $strengths->concrete, $strengths->steel, $steel);
        $target = $this->reinforcementTargetCalculator->calculate($required, $minimum, $domain);
        $candidates = $setup->configuration->calculationMode === BeamCalculationMode::DESIGN
            ? $this->reinforcementCandidatesGenerator->generate($target, $tensionFace->reinforcementPosition())
            : $this->providedReinforcementCandidate($setup, $target);
        $geometryCandidates = $this->reinforcementGeometryFilter->filter($candidates, $setup->geometry, $cover, $detailing, $reinforcementDetailing, $profile->reinforcementSpacingRequirements);
        $recalculated = $this->reinforcementCandidateRecalculator->recalculate($geometryCandidates, $ultimateMoment, $setup->geometry, $cover, $detailing, $strengths, $concrete, $steel, $profile, $initialDepth, $required);
        $selected = $recalculated->validCandidates[0] ?? throw new LogicException('NO_VALID_LONGITUDINAL_REINFORCEMENT_CANDIDATE');

        return new BeamFlexuralDesignResult($cover, $initialDepth, $strengths, $reduced, $neutral, $lever, $required, $minimum, $domain, $target, $candidates, $geometryCandidates, $recalculated, $selected);
    }
}
