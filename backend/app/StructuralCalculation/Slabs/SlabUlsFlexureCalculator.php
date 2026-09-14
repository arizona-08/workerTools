<?php

namespace App\StructuralCalculation\Slabs;

use App\StructuralCalculation\Beams\BeamBendingMoment;
use App\StructuralCalculation\Beams\BeamCalculationMode;
use App\StructuralCalculation\Beams\BeamEffectiveDepthResult;
use App\StructuralCalculation\Beams\BeamFlexuralConcreteDesignStrength;
use App\StructuralCalculation\Beams\BeamFlexuralDomainCheckCalculator;
use App\StructuralCalculation\Beams\BeamFlexuralDomainCheckException;
use App\StructuralCalculation\Beams\BeamFlexuralSteelDesignStrength;
use App\StructuralCalculation\Beams\BeamGeometry;
use App\StructuralCalculation\Beams\BeamLeverArmCalculator;
use App\StructuralCalculation\Beams\BeamMinimumTensionReinforcementCalculator;
use App\StructuralCalculation\Beams\BeamNeutralAxisCalculator;
use App\StructuralCalculation\Beams\BeamNeutralAxisException;
use App\StructuralCalculation\Beams\BeamReducedMomentCalculator;
use App\StructuralCalculation\Beams\BeamRequiredTensionReinforcementCalculator;
use App\StructuralCalculation\Beams\LongitudinalBarDiameterSource;
use App\StructuralCalculation\Eurocode\Concrete\ConcreteDesignStrengthCalculator;
use App\StructuralCalculation\Eurocode\Cover\CoverCalculationInput;
use App\StructuralCalculation\Eurocode\Cover\CoverMode;
use App\StructuralCalculation\Eurocode\Cover\NominalCoverCalculator;
use App\StructuralCalculation\Eurocode\Profiles\FrenchEurocodeProfileRepository;
use App\StructuralCalculation\Eurocode\ReinforcementSteel\ReinforcementSteelDesignStrengthCalculator;
use App\StructuralCalculation\Materials\Concrete\ConcreteClassRepository;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGradeRepository;

/** Orchestrateur SLAB-07 : flexion ELU d'une bande, sans proposition de ferraillage. */
final readonly class SlabUlsFlexureCalculator
{
    public function __construct(
        private FrenchEurocodeProfileRepository $profiles,
        private NominalCoverCalculator $coverCalculator,
        private SlabEffectiveDepthCalculator $effectiveDepthCalculator,
        private ConcreteClassRepository $concreteClasses,
        private ReinforcementSteelGradeRepository $steelGrades,
        private ConcreteDesignStrengthCalculator $concreteStrengthCalculator,
        private ReinforcementSteelDesignStrengthCalculator $steelStrengthCalculator,
        private BeamReducedMomentCalculator $reducedMomentCalculator,
        private BeamNeutralAxisCalculator $neutralAxisCalculator,
        private BeamLeverArmCalculator $leverArmCalculator,
        private BeamRequiredTensionReinforcementCalculator $requiredReinforcementCalculator,
        private BeamMinimumTensionReinforcementCalculator $minimumReinforcementCalculator,
        private BeamFlexuralDomainCheckCalculator $domainCheckCalculator,
    ) {}

    public function calculate(SlabCalculationInput $input, SlabStripAnalysis $analysis): SlabUlsFlexureResult
    {
        $profile = $this->profiles->find($input->configuration->designCodeProfile->value);
        if ($profile === null) {
            throw new SlabUlsFlexureException(SlabUlsFlexureRejectionReason::INVALID_INPUT);
        }

        $detailing = SlabFlexuralDetailingAssumptions::mvp();
        $cover = $this->coverCalculator->calculate(new CoverCalculationInput(
            coverMode: CoverMode::AUTO,
            exposureClasses: [$input->materials->exposureClass],
            concreteClass: $input->materials->concreteClass,
            designWorkingLifeYears: 50,
            reinforcementDiameter: $detailing->preliminaryMainBarDiameter,
        ), $profile);
        $effectiveDepth = $this->effectiveDepthCalculator->calculate($input->geometry, $cover, $detailing);
        $concrete = $this->concreteClasses->get($input->materials->concreteClass);
        $steel = $this->steelGrades->get($input->materials->steelGrade);
        $concreteStrength = new BeamFlexuralConcreteDesignStrength(
            $concrete->strengthClass,
            $concrete->fck,
            $profile->materialSafetyFactors->alphaCc,
            $profile->materialSafetyFactors->gammaC,
            $this->concreteStrengthCalculator->calculate($concrete, $profile),
        );
        $steelStrength = new BeamFlexuralSteelDesignStrength(
            $steel->grade,
            $steel->fyk,
            $profile->materialSafetyFactors->gammaS,
            $this->steelStrengthCalculator->calculate($steel, $profile),
        );
        $geometry = new BeamGeometry($input->geometry->effectiveSpan, $input->geometry->calculationStripWidth, $input->geometry->thickness);
        $depthAdapter = new BeamEffectiveDepthResult(
            BeamCalculationMode::DESIGN,
            $effectiveDepth->overallDepth,
            $effectiveDepth->nominalCover,
            0.0,
            $effectiveDepth->preliminaryMainBarDiameter,
            LongitudinalBarDiameterSource::CONFIG,
            $effectiveDepth->tensionSteelCentroidOffset,
            $effectiveDepth->effectiveDepth,
        );
        $designMoment = new BeamBendingMoment(
            $analysis->linearLoads->uls->lineLoad,
            $analysis->internalForces->uls->maximumMoment,
            $analysis->linearLoads->uls->surfaceLoadCombination->expressionReference,
            'provided by SLAB-06',
        );

        try {
            $reduced = $this->reducedMomentCalculator->calculate($designMoment, $geometry, $depthAdapter, $concreteStrength);
            $neutral = $this->neutralAxisCalculator->calculate($reduced, $depthAdapter, $concreteStrength);
            $lever = $this->leverArmCalculator->calculate($depthAdapter, $neutral);
            $required = $this->requiredReinforcementCalculator->calculate($designMoment, $steelStrength, $lever);
            $minimum = $this->minimumReinforcementCalculator->calculate($concrete, $steel, $geometry, $depthAdapter, $profile->beamLongitudinalReinforcementRequirements);
            $domain = $this->domainCheckCalculator->calculate($depthAdapter, $neutral, $concreteStrength, $steelStrength, $steel);
        } catch (BeamNeutralAxisException|BeamFlexuralDomainCheckException) {
            throw new SlabUlsFlexureException(SlabUlsFlexureRejectionReason::CALCULATION_METHOD_NOT_SUPPORTED);
        }

        if (! $domain->singlyReinforcedModelValid) {
            throw new SlabUlsFlexureException(SlabUlsFlexureRejectionReason::CALCULATION_METHOD_NOT_SUPPORTED);
        }

        $governing = match (true) {
            $required->requiredReinforcementArea > $minimum->requiredMinimum => SlabReinforcementTargetGoverningRequirement::FLEXURAL_DEMAND,
            $required->requiredReinforcementArea < $minimum->requiredMinimum => SlabReinforcementTargetGoverningRequirement::MINIMUM_REINFORCEMENT,
            default => SlabReinforcementTargetGoverningRequirement::EQUAL_REQUIREMENTS,
        };

        return new SlabUlsFlexureResult(
            designMoment: $designMoment->maximumMoment,
            designMomentInNewtonMillimetres: $reduced->designMomentInNewtonMillimetres,
            sectionWidth: $geometry->width,
            effectiveDepth: $effectiveDepth,
            cover: $cover,
            concreteDesignStrength: $concreteStrength->fcd,
            steelDesignStrength: $steelStrength->fyd,
            meanTensileConcreteStrength: $concrete->fctm,
            characteristicSteelStrength: $steel->fyk,
            reducedMoment: $reduced->reducedDesignMoment,
            neutralAxisRatio: $neutral->neutralAxisRatio,
            neutralAxisDepth: $neutral->neutralAxisDepth,
            leverArm: $lever->leverArm,
            requiredReinforcementArea: $required->requiredReinforcementArea,
            minimumStrengthBasedReinforcementArea: $minimum->strengthBasedMinimum,
            minimumAbsoluteReinforcementArea: $minimum->absoluteMinimum,
            minimumReinforcementArea: $minimum->requiredMinimum,
            designReinforcementArea: max($required->requiredReinforcementArea, $minimum->requiredMinimum),
            governingRequirement: $governing,
        );
    }
}
