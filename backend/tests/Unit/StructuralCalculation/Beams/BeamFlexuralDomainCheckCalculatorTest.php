<?php

use App\StructuralCalculation\Beams\BeamCalculationInputFactory;
use App\StructuralCalculation\Beams\BeamCalculationMode;
use App\StructuralCalculation\Beams\BeamEffectiveDepthCalculator;
use App\StructuralCalculation\Beams\BeamEffectiveDepthResult;
use App\StructuralCalculation\Beams\BeamFlexuralConcreteDesignStrength;
use App\StructuralCalculation\Beams\BeamFlexuralDesignStrengthsCalculator;
use App\StructuralCalculation\Beams\BeamFlexuralDetailingAssumptions;
use App\StructuralCalculation\Beams\BeamFlexuralDomainCheckCalculator;
use App\StructuralCalculation\Beams\BeamFlexuralDomainCheckException;
use App\StructuralCalculation\Beams\BeamFlexuralDomainCheckRejectionReason;
use App\StructuralCalculation\Beams\BeamFlexuralSteelDesignStrength;
use App\StructuralCalculation\Beams\BeamNeutralAxisCalculator;
use App\StructuralCalculation\Beams\BeamNeutralAxisResult;
use App\StructuralCalculation\Beams\BeamReducedMomentCalculator;
use App\StructuralCalculation\Beams\BeamServiceabilityCombinationCalculator;
use App\StructuralCalculation\Beams\BeamUltimateCombinationCalculator;
use App\StructuralCalculation\Beams\CharacteristicActionsCalculator;
use App\StructuralCalculation\Beams\LongitudinalBarDiameterSource;
use App\StructuralCalculation\Beams\SelfWeightCalculator;
use App\StructuralCalculation\Beams\SimplySupportedBeamBendingMomentCalculator;
use App\StructuralCalculation\Eurocode\Actions\ReinforcedConcreteUnitWeightRepository;
use App\StructuralCalculation\Eurocode\Concrete\ConcreteUltimateStrainException;
use App\StructuralCalculation\Eurocode\Concrete\ConcreteUltimateStrainParametersCalculator;
use App\StructuralCalculation\Eurocode\Concrete\ConcreteUltimateStrainRejectionReason;
use App\StructuralCalculation\Eurocode\Cover\CoverCalculationInput;
use App\StructuralCalculation\Eurocode\Cover\CoverMode;
use App\StructuralCalculation\Eurocode\Cover\NominalCoverCalculator;
use App\StructuralCalculation\Eurocode\Profiles\FrenchEurocodeProfileRepository;
use App\StructuralCalculation\Materials\Concrete\ConcreteStrengthClass;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGrade;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGradeRepository;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelProperties;
use App\StructuralCalculation\Materials\ReinforcementSteel\SteelDuctilityClass;

function beamFlexuralDomainCheckCalculator(): BeamFlexuralDomainCheckCalculator
{
    return app(BeamFlexuralDomainCheckCalculator::class);
}

function domainCheckDepth(float $depth = 544): BeamEffectiveDepthResult
{
    return new BeamEffectiveDepthResult(BeamCalculationMode::DESIGN, 600, 40, 8, 16, LongitudinalBarDiameterSource::CONFIG, 56, $depth);
}

function domainCheckNeutralAxis(float $ratio = 37.596713565478 / 544, float $depth = 544): BeamNeutralAxisResult
{
    $axisDepth = $ratio * $depth;

    return new BeamNeutralAxisResult(0.053760832156277, 30, 0.8, 1, 0.892478335687446, $axisDepth / $depth, $depth, $axisDepth);
}

function domainCheckConcrete(float $fck = 30): BeamFlexuralConcreteDesignStrength
{
    return new BeamFlexuralConcreteDesignStrength(ConcreteStrengthClass::C30_37, $fck, 1, 1.5, 20);
}

function domainCheckSteelDesign(float $fyd = 500 / 1.15): BeamFlexuralSteelDesignStrength
{
    return new BeamFlexuralSteelDesignStrength(ReinforcementSteelGrade::B500B, 500, 1.15, $fyd);
}

function domainCheckSteel(float $elasticModulus = 200000): ReinforcementSteelProperties
{
    return new ReinforcementSteelProperties(ReinforcementSteelGrade::B500B, 500, $elasticModulus, SteelDuctilityClass::B);
}

it('validates the current C30/37 and B500B flexural case through strain compatibility', function () {
    $result = beamFlexuralDomainCheckCalculator()->calculate(
        domainCheckDepth(), domainCheckNeutralAxis(), domainCheckConcrete(), domainCheckSteelDesign(), domainCheckSteel(),
    );
    $expectedYieldStrain = (500 / 1.15) / 200000;
    $expectedSteelStrain = 0.0035 * (1 - $result->neutralAxisRatio) / $result->neutralAxisRatio;
    $expectedYieldLimit = 0.0035 / (0.0035 + $expectedYieldStrain);

    expect($result->concreteUltimateStrain)->toBe(0.0035)
        ->and($result->steelElasticModulus)->toBe(200000.0)
        ->and(abs($result->steelDesignYieldStrain - $expectedYieldStrain))->toBeLessThan(0.000000000001)
        ->and(abs($result->tensionSteelStrain - $expectedSteelStrain))->toBeLessThan(0.000000000001)
        ->and(abs($result->yieldingNeutralAxisLimit - $expectedYieldLimit))->toBeLessThan(0.000000000001)
        ->and($result->neutralAxisRatio)->toBeLessThan($result->yieldingNeutralAxisLimit)
        ->and($result->tensionSteelReachesDesignYield)->toBeTrue()
        ->and($result->singlyReinforcedModelValid)->toBeTrue();
});

it('reports the current model outside its domain when the steel does not reach fyd', function () {
    $result = beamFlexuralDomainCheckCalculator()->calculate(
        domainCheckDepth(), domainCheckNeutralAxis(0.7), domainCheckConcrete(), domainCheckSteelDesign(), domainCheckSteel(),
    );

    expect($result->neutralAxisRatio)->toBeGreaterThan($result->yieldingNeutralAxisLimit)
        ->and($result->tensionSteelStrain)->toBeLessThan($result->steelDesignYieldStrain)
        ->and($result->tensionSteelReachesDesignYield)->toBeFalse()
        ->and($result->singlyReinforcedModelValid)->toBeFalse();
});

it('accepts the yielding-neutral-axis boundary with the named numerical tolerance', function () {
    $yieldStrain = (500 / 1.15) / 200000;
    $yieldingNeutralAxisLimit = 0.0035 / (0.0035 + $yieldStrain);
    $result = beamFlexuralDomainCheckCalculator()->calculate(
        domainCheckDepth(), domainCheckNeutralAxis($yieldingNeutralAxisLimit), domainCheckConcrete(), domainCheckSteelDesign(), domainCheckSteel(),
    );

    expect(abs($result->neutralAxisRatio - $result->yieldingNeutralAxisLimit))->toBeLessThan(0.000000000001)
        ->and($result->tensionSteelReachesDesignYield)->toBeTrue()
        ->and($result->singlyReinforcedModelValid)->toBeTrue();
});

it('keeps the zero-neutral-axis case explicit without dividing by zero', function () {
    $result = beamFlexuralDomainCheckCalculator()->calculate(
        domainCheckDepth(), domainCheckNeutralAxis(0), domainCheckConcrete(), domainCheckSteelDesign(), domainCheckSteel(),
    );

    expect($result->tensionSteelStrain)->toBeNull()
        ->and($result->tensionSteelReachesDesignYield)->toBeNull()
        ->and($result->singlyReinforcedModelValid)->toBeTrue();
});

it('calculates the high-strength concrete ultimate strain and refuses fck beyond its EC2 domain', function () {
    $calculator = app(ConcreteUltimateStrainParametersCalculator::class);
    $highStrength = $calculator->calculate(60);

    expect(abs($highStrength->ultimateStrain - 0.0028835))->toBeLessThan(0.000000000001)
        ->and($highStrength->ultimateStrain)->not->toBe(0.0035);

    try {
        $calculator->calculate(90.1);
    } catch (ConcreteUltimateStrainException $exception) {
        expect($exception->reason)->toBe(ConcreteUltimateStrainRejectionReason::UNSUPPORTED_CHARACTERISTIC_CONCRETE_STRENGTH);

        return;
    }

    throw new RuntimeException('Expected concrete strength above 90 MPa to be rejected.');
});

it('rejects invalid steel inputs for the domain check', function (BeamFlexuralSteelDesignStrength $steelDesign, ReinforcementSteelProperties $steel, BeamFlexuralDomainCheckRejectionReason $reason) {
    try {
        beamFlexuralDomainCheckCalculator()->calculate(domainCheckDepth(), domainCheckNeutralAxis(), domainCheckConcrete(), $steelDesign, $steel);
    } catch (BeamFlexuralDomainCheckException $exception) {
        expect($exception->reason)->toBe($reason);

        return;
    }

    throw new RuntimeException('Expected invalid domain-check steel input to be rejected.');
})->with([
    'non-positive fyd' => [domainCheckSteelDesign(0), domainCheckSteel(), BeamFlexuralDomainCheckRejectionReason::INVALID_STEEL_DESIGN_STRENGTH],
    'non-positive Es' => [domainCheckSteelDesign(), domainCheckSteel(0), BeamFlexuralDomainCheckRejectionReason::INVALID_STEEL_ELASTIC_MODULUS],
]);

it('chains the current beam inputs through the existing flexural results into the domain check', function () {
    $setup = app(BeamCalculationInputFactory::class)->fromPayload([
        'configuration' => [
            'calculationMode' => 'DESIGN', 'elementType' => 'BEAM', 'materialType' => 'REINFORCED_CONCRETE', 'sectionType' => 'RECTANGULAR',
            'supportSystem' => 'SIMPLY_SUPPORTED', 'loadModel' => 'UNIFORMLY_DISTRIBUTED', 'designCodeProfile' => 'NF_EN_1992_1_1_2005_FR', 'designSituation' => 'PERSISTENT_TRANSIENT',
        ],
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
    $result = beamFlexuralDomainCheckCalculator()->calculate(
        $depth, $neutralAxis, $strengths->concrete, $strengths->steel, app(ReinforcementSteelGradeRepository::class)->get($setup->materials->steelGrade),
    );

    expect($depth->effectiveDepth)->toBe(544.0)
        ->and(abs($neutralAxis->neutralAxisDepth - 37.596713565478))->toBeLessThan(0.000000000001)
        ->and(abs($result->steelDesignYieldStrain - 0.00217391304347826))->toBeLessThan(0.000000000001)
        ->and($result->tensionSteelStrain)->toBeGreaterThan($result->steelDesignYieldStrain)
        ->and($result->neutralAxisRatio)->toBeLessThan($result->yieldingNeutralAxisLimit)
        ->and($result->singlyReinforcedModelValid)->toBeTrue();
});
