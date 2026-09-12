<?php

use App\StructuralCalculation\Beams\BeamCalculationInputFactory;
use App\StructuralCalculation\Beams\BeamEffectiveDepthCalculator;
use App\StructuralCalculation\Beams\BeamFlexuralDesignStrengthsCalculator;
use App\StructuralCalculation\Beams\BeamFlexuralDetailingAssumptions;
use App\StructuralCalculation\Beams\BeamFlexuralDomainCheckCalculator;
use App\StructuralCalculation\Beams\BeamFlexuralDomainCheckResult;
use App\StructuralCalculation\Beams\BeamLeverArmCalculator;
use App\StructuralCalculation\Beams\BeamMinimumTensionReinforcementCalculator;
use App\StructuralCalculation\Beams\BeamMinimumTensionReinforcementResult;
use App\StructuralCalculation\Beams\BeamNeutralAxisCalculator;
use App\StructuralCalculation\Beams\BeamReducedMomentCalculator;
use App\StructuralCalculation\Beams\BeamReinforcementTargetCalculator;
use App\StructuralCalculation\Beams\BeamReinforcementTargetException;
use App\StructuralCalculation\Beams\BeamReinforcementTargetGoverningRequirement;
use App\StructuralCalculation\Beams\BeamReinforcementTargetRejectionReason;
use App\StructuralCalculation\Beams\BeamRequiredTensionReinforcementCalculator;
use App\StructuralCalculation\Beams\BeamRequiredTensionReinforcementResult;
use App\StructuralCalculation\Beams\BeamServiceabilityCombinationCalculator;
use App\StructuralCalculation\Beams\BeamUltimateCombinationCalculator;
use App\StructuralCalculation\Beams\CharacteristicActionsCalculator;
use App\StructuralCalculation\Beams\MinimumTensionReinforcementGoverningCriterion;
use App\StructuralCalculation\Beams\SelfWeightCalculator;
use App\StructuralCalculation\Beams\SimplySupportedBeamBendingMomentCalculator;
use App\StructuralCalculation\Eurocode\Actions\ReinforcedConcreteUnitWeightRepository;
use App\StructuralCalculation\Eurocode\Cover\CoverCalculationInput;
use App\StructuralCalculation\Eurocode\Cover\CoverMode;
use App\StructuralCalculation\Eurocode\Cover\NominalCoverCalculator;
use App\StructuralCalculation\Eurocode\Profiles\FrenchEurocodeProfileRepository;
use App\StructuralCalculation\Materials\Concrete\ConcreteClassRepository;
use App\StructuralCalculation\Materials\Concrete\ConcreteStrengthClass;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGrade;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGradeRepository;

function beamReinforcementTargetCalculator(): BeamReinforcementTargetCalculator
{
    return app(BeamReinforcementTargetCalculator::class);
}

function targetFlexuralRequirement(float $area): BeamRequiredTensionReinforcementResult
{
    return new BeamRequiredTensionReinforcementResult(95.45859375, 95458593.75, 500 / 1.15, 528.96131457381, (500 / 1.15) * 528.96131457381, $area);
}

function targetMinimumRequirement(float $area): BeamMinimumTensionReinforcementResult
{
    return new BeamMinimumTensionReinforcementResult(
        ConcreteStrengthClass::C30_37, 2.9, ReinforcementSteelGrade::B500B, 500, 300, 544, 246.1056, 212.16, $area, MinimumTensionReinforcementGoverningCriterion::FCTM_FYK,
    );
}

function targetDomainCheck(bool $valid = true): BeamFlexuralDomainCheckResult
{
    return new BeamFlexuralDomainCheckResult(30, 0.0035, 500 / 1.15, 200000, (500 / 1.15) / 200000, 544, 37.596713565478, 37.596713565478 / 544, 0.047142724308443, 0.616858237547893, $valid, $valid);
}

it('selects the flexural requirement as the target area for the current case', function () {
    $result = beamReinforcementTargetCalculator()->calculate(
        targetFlexuralRequirement(415.06771776287212), targetMinimumRequirement(246.1056), targetDomainCheck(),
    );

    expect($result->flexuralRequiredArea)->toBe(415.06771776287212)
        ->and($result->minimumRequiredArea)->toBe(246.1056)
        ->and($result->targetArea)->toBe(415.06771776287212)
        ->and($result->governingRequirement)->toBe(BeamReinforcementTargetGoverningRequirement::FLEXURAL_DEMAND)
        ->and($result::AREA_UNIT)->toBe('mm²')
        ->and($result::FORMULA)->toBe('As_target = max(As_req, As_min)');
});

it('selects the minimum reinforcement when it governs, including a zero flexural demand', function () {
    $result = beamReinforcementTargetCalculator()->calculate(targetFlexuralRequirement(0), targetMinimumRequirement(250), targetDomainCheck());

    expect($result->targetArea)->toBe(250.0)
        ->and($result->governingRequirement)->toBe(BeamReinforcementTargetGoverningRequirement::MINIMUM_REINFORCEMENT);
});

it('uses an explicit deterministic criterion for equal requirements', function () {
    $result = beamReinforcementTargetCalculator()->calculate(targetFlexuralRequirement(250), targetMinimumRequirement(250), targetDomainCheck());

    expect($result->targetArea)->toBe(250.0)
        ->and($result->governingRequirement)->toBe(BeamReinforcementTargetGoverningRequirement::EQUAL_REQUIREMENTS);
});

it('keeps a zero minimum requirement generic without inventing a new minimum', function () {
    $result = beamReinforcementTargetCalculator()->calculate(targetFlexuralRequirement(150), targetMinimumRequirement(0), targetDomainCheck());

    expect($result->targetArea)->toBe(150.0)
        ->and($result->governingRequirement)->toBe(BeamReinforcementTargetGoverningRequirement::FLEXURAL_DEMAND);
});

it('rejects negative areas and an invalid singly-reinforced domain', function (BeamRequiredTensionReinforcementResult $flexural, BeamMinimumTensionReinforcementResult $minimum, BeamFlexuralDomainCheckResult $domain, BeamReinforcementTargetRejectionReason $reason) {
    try {
        beamReinforcementTargetCalculator()->calculate($flexural, $minimum, $domain);
    } catch (BeamReinforcementTargetException $exception) {
        expect($exception->reason)->toBe($reason);

        return;
    }

    throw new RuntimeException('Expected invalid reinforcement target input to be rejected.');
})->with([
    'negative flexural area' => [targetFlexuralRequirement(-1), targetMinimumRequirement(250), targetDomainCheck(), BeamReinforcementTargetRejectionReason::INVALID_FLEXURAL_REQUIRED_AREA],
    'negative minimum area' => [targetFlexuralRequirement(150), targetMinimumRequirement(-1), targetDomainCheck(), BeamReinforcementTargetRejectionReason::INVALID_MINIMUM_REQUIRED_AREA],
    'invalid flexural domain' => [targetFlexuralRequirement(150), targetMinimumRequirement(250), targetDomainCheck(false), BeamReinforcementTargetRejectionReason::INVALID_SINGLY_REINFORCED_DOMAIN],
]);

it('chains current beam results into As_target without selecting bars', function () {
    $setup = app(BeamCalculationInputFactory::class)->fromPayload([
        'configuration' => ['calculationMode' => 'DESIGN', 'elementType' => 'BEAM', 'materialType' => 'REINFORCED_CONCRETE', 'sectionType' => 'RECTANGULAR', 'supportSystem' => 'SIMPLY_SUPPORTED', 'loadModel' => 'UNIFORMLY_DISTRIBUTED', 'designCodeProfile' => 'NF_EN_1992_1_1_2005_FR', 'designSituation' => 'PERSISTENT_TRANSIENT'],
        'geometry' => ['effectiveSpan' => 6500, 'width' => 300, 'height' => 600, 'unit' => 'mm'],
        'materials' => ['concreteClass' => 'C30/37', 'steelGrade' => 'B500B', 'exposureClasses' => ['XC4']],
        'loads' => ['permanent' => ['includeSelfWeight' => true, 'additionalPermanentLoad' => 5, 'unit' => 'kN/m'], 'variable' => ['category' => 'A', 'characteristicLoad' => 3.5, 'unit' => 'kN/m']],
    ]);
    $profile = app(FrenchEurocodeProfileRepository::class)->get();
    $selfWeight = app(SelfWeightCalculator::class)->calculate($setup->geometry, true, app(ReinforcedConcreteUnitWeightRepository::class)->normalWeightReinforcedConcrete());
    $actions = app(CharacteristicActionsCalculator::class)->calculate($selfWeight, $setup->permanentLoads, $setup->variableLoad);
    $ultimate = app(BeamUltimateCombinationCalculator::class)->calculate($actions, $profile);
    $serviceability = app(BeamServiceabilityCombinationCalculator::class)->calculate($actions, $profile);
    $bending = app(SimplySupportedBeamBendingMomentCalculator::class)->calculate($setup->configuration, $setup->geometry, $ultimate, $serviceability);
    $cover = app(NominalCoverCalculator::class)->calculate(new CoverCalculationInput(CoverMode::AUTO, $setup->materials->exposureClasses, $setup->materials->concreteClass, 50, BeamFlexuralDetailingAssumptions::mvp()->transverseReinforcementDiameter), $profile);
    $depth = app(BeamEffectiveDepthCalculator::class)->calculate($setup->configuration->calculationMode, $setup->geometry, $cover, BeamFlexuralDetailingAssumptions::mvp());
    $strengths = app(BeamFlexuralDesignStrengthsCalculator::class)->calculate($setup->materials, $profile);
    $reducedMoment = app(BeamReducedMomentCalculator::class)->calculate($bending->ultimate, $setup->geometry, $depth, $strengths->concrete);
    $neutralAxis = app(BeamNeutralAxisCalculator::class)->calculate($reducedMoment, $depth, $strengths->concrete);
    $leverArm = app(BeamLeverArmCalculator::class)->calculate($depth, $neutralAxis);
    $flexural = app(BeamRequiredTensionReinforcementCalculator::class)->calculate($bending->ultimate, $strengths->steel, $leverArm);
    $minimum = app(BeamMinimumTensionReinforcementCalculator::class)->calculate(app(ConcreteClassRepository::class)->get($setup->materials->concreteClass), app(ReinforcementSteelGradeRepository::class)->get($setup->materials->steelGrade), $setup->geometry, $depth, $profile->beamLongitudinalReinforcementRequirements);
    $domain = app(BeamFlexuralDomainCheckCalculator::class)->calculate($depth, $neutralAxis, $strengths->concrete, $strengths->steel, app(ReinforcementSteelGradeRepository::class)->get($setup->materials->steelGrade));
    $result = beamReinforcementTargetCalculator()->calculate($flexural, $minimum, $domain);

    expect(abs($flexural->requiredReinforcementArea - 415.06771776287212))->toBeLessThan(0.000001)
        ->and($minimum->requiredMinimum)->toBe(246.1056)
        ->and($domain->singlyReinforcedModelValid)->toBeTrue()
        ->and(abs($result->targetArea - 415.06771776287212))->toBeLessThan(0.000001)
        ->and($result->governingRequirement)->toBe(BeamReinforcementTargetGoverningRequirement::FLEXURAL_DEMAND)
        ->and(property_exists($result, 'barCount'))->toBeFalse()
        ->and(property_exists($result, 'providedArea'))->toBeFalse();
});
