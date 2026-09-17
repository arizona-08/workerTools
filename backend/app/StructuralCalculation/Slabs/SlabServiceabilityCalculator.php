<?php

namespace App\StructuralCalculation\Slabs;

use App\StructuralCalculation\Beams\BeamSupportSystem;
use App\StructuralCalculation\Eurocode\Profiles\DesignCodeProfile;
use App\StructuralCalculation\Eurocode\Profiles\FrenchEurocodeProfileRepository;
use App\StructuralCalculation\Eurocode\Serviceability\CrackedElasticSectionCalculator;
use App\StructuralCalculation\Eurocode\Serviceability\DirectCrackWidthCalculator;
use App\StructuralCalculation\Eurocode\Serviceability\SimplifiedSpanDepthCalculator;
use App\StructuralCalculation\Materials\Concrete\ConcreteClassRepository;
use App\StructuralCalculation\Materials\Concrete\ConcreteProperties;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGradeRepository;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelProperties;

/** Compose les contrôles ELS SLAB-10 à partir des résultats déjà calculés de SLAB-06 et SLAB-08. */
final readonly class SlabServiceabilityCalculator
{
    public function __construct(
        private FrenchEurocodeProfileRepository $profiles,
        private ConcreteClassRepository $concreteClasses,
        private ReinforcementSteelGradeRepository $steelGrades,
        private CrackedElasticSectionCalculator $crackedSection,
        private DirectCrackWidthCalculator $directCrackWidth,
        private SimplifiedSpanDepthCalculator $simplifiedSpanDepth,
    ) {}

    public function calculate(SlabCalculationInput $input, SlabStripAnalysis $analysis, SlabMainReinforcementProposalResult $main): SlabServiceabilityResult
    {
        if ($main->proposal === null) {
            return new SlabServiceabilityResult($this->notCheckedCrack(), $this->notCheckedDeflection());
        }

        $profile = $this->profiles->find($input->configuration->designCodeProfile->value);
        if ($profile === null) {
            return new SlabServiceabilityResult($this->unsupportedCrack('UNSUPPORTED_DESIGN_CODE_PROFILE'), $this->unsupportedDeflection('UNSUPPORTED_DESIGN_CODE_PROFILE'));
        }

        $concrete = $this->concreteClasses->get($input->materials->concreteClass);
        $steel = $this->steelGrades->get($input->materials->steelGrade);
        $proposal = $main->proposal;
        $flexure = $proposal->recalculatedFlexure;
        $depth = $flexure->effectiveDepth;
        $crack = $this->crack($input, $analysis, $proposal, $depth, $concrete, $steel, $profile);
        $deflection = $this->deflection($input, $proposal, $depth, $concrete, $steel, $profile);

        return new SlabServiceabilityResult($crack, $deflection);
    }

    private function crack(SlabCalculationInput $input, SlabStripAnalysis $analysis, SlabMainReinforcementProposal $proposal, SlabEffectiveDepthResult $depth, ConcreteProperties $concrete, ReinforcementSteelProperties $steel, DesignCodeProfile $profile): SlabCrackVerificationResult
    {
        $requirements = $profile->beamCrackWidthRequirements;
        $limit = $requirements->crackWidthLimitFor($input->materials->exposureClass);
        if ($limit === null) {
            return $this->unsupportedCrack('UNSUPPORTED_CRACK_WIDTH_EXPOSURE_CLASS');
        }

        $moment = $analysis->internalForces->slsQuasiPermanent->maximumMoment;
        try {
            $section = $this->crackedSection->calculate(SlabGeometry::CALCULATION_STRIP_WIDTH_MM, $depth->effectiveDepth, $proposal->providedAreaPerMeter, $concrete->ecm, $steel->es);
            $steelStress = $this->crackedSection->steelStress($moment, $depth->effectiveDepth, $section);
            $direct = $this->directCrackWidth->calculate(
                SlabGeometry::CALCULATION_STRIP_WIDTH_MM, $depth->overallDepth, $depth->effectiveDepth,
                $proposal->providedAreaPerMeter, $proposal->barDiameter, $proposal->spacing,
                $depth->nominalCover, $section->neutralAxisDepth, $section->modularRatio,
                $steelStress, $steel->es, $concrete->fctm, $requirements,
            );
        } catch (\InvalidArgumentException) {
            return $this->unsupportedCrack('INVALID_SERVICEABILITY_INPUT');
        }

        $utilization = $direct->crackWidth / $limit;

        return new SlabCrackVerificationResult(
            $direct->crackWidth <= $limit ? SlabCrackVerificationStatus::COMPLIANT : SlabCrackVerificationStatus::NOT_COMPLIANT,
            SlabCrackVerificationResult::METHOD, 'QUASI_PERMANENT', $moment, $section->modularRatio,
            $section->neutralAxisDepth, $section->secondMomentOfArea, $steelStress,
            $direct->effectiveTensionArea, $direct->effectiveReinforcementRatio, $direct->maximumCrackSpacing,
            $direct->strainDifference, $direct->crackWidth, $limit, $utilization,
        );
    }

    private function deflection(SlabCalculationInput $input, SlabMainReinforcementProposal $proposal, SlabEffectiveDepthResult $depth, ConcreteProperties $concrete, ReinforcementSteelProperties $steel, DesignCodeProfile $profile): SlabDeflectionVerificationResult
    {
        $structuralFactor = $profile->beamDeflectionRequirements->structuralFactorFor(BeamSupportSystem::SIMPLY_SUPPORTED);
        if ($structuralFactor === null) {
            return $this->unsupportedDeflection('CALCULATION_METHOD_NOT_SUPPORTED');
        }

        try {
            $result = $this->simplifiedSpanDepth->calculate(
                $input->geometry->effectiveSpan, SlabGeometry::CALCULATION_STRIP_WIDTH_MM, $depth->effectiveDepth,
                $concrete->fck, $steel->fyk, $proposal->recalculatedFlexure->designReinforcementArea,
                $proposal->providedAreaPerMeter, $structuralFactor, $profile->beamDeflectionRequirements,
            );
        } catch (\InvalidArgumentException) {
            return $this->unsupportedDeflection('CALCULATION_METHOD_NOT_SUPPORTED');
        }

        return new SlabDeflectionVerificationResult(
            $result->actualSpanDepthRatio <= $result->allowableSpanDepthRatio ? SlabDeflectionVerificationStatus::COMPLIANT : SlabDeflectionVerificationStatus::NOT_COMPLIANT,
            SlabDeflectionVerificationResult::METHOD, $input->geometry->effectiveSpan, $depth->effectiveDepth,
            $result->actualSpanDepthRatio, $result->reinforcementRatio, $result->referenceReinforcementRatio,
            $structuralFactor, $result->steelStressCorrectionFactor, $result->allowableSpanDepthRatio,
            $result->utilization,
            ['SIMPLIFIED_METHOD_ONLY', 'NO_EXPLICIT_DEFLECTION_CALCULATED', 'LONG_TERM_EFFECTS_NOT_EXPLICITLY_MODELLED'],
        );
    }

    private function unsupportedCrack(string $warning): SlabCrackVerificationResult
    {
        return new SlabCrackVerificationResult(SlabCrackVerificationStatus::CALCULATION_METHOD_NOT_SUPPORTED, SlabCrackVerificationResult::METHOD, null, null, null, null, null, null, null, null, null, null, null, null, null, [$warning]);
    }

    private function unsupportedDeflection(string $warning): SlabDeflectionVerificationResult
    {
        return new SlabDeflectionVerificationResult(SlabDeflectionVerificationStatus::CALCULATION_METHOD_NOT_SUPPORTED, SlabDeflectionVerificationResult::METHOD, null, null, null, null, null, null, null, null, null, [$warning]);
    }

    private function notCheckedCrack(): SlabCrackVerificationResult
    {
        return new SlabCrackVerificationResult(SlabCrackVerificationStatus::NOT_CHECKED, SlabCrackVerificationResult::METHOD, null, null, null, null, null, null, null, null, null, null, null, null, null, ['MISSING_MAIN_REINFORCEMENT_PROPOSAL']);
    }

    private function notCheckedDeflection(): SlabDeflectionVerificationResult
    {
        return new SlabDeflectionVerificationResult(SlabDeflectionVerificationStatus::NOT_CHECKED, SlabDeflectionVerificationResult::METHOD, null, null, null, null, null, null, null, null, null, ['MISSING_MAIN_REINFORCEMENT_PROPOSAL']);
    }
}
