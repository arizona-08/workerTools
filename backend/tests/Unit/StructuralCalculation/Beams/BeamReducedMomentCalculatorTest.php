<?php

use App\StructuralCalculation\Beams\BeamBendingMoment;
use App\StructuralCalculation\Beams\BeamCalculationInputFactory;
use App\StructuralCalculation\Beams\BeamCalculationMode;
use App\StructuralCalculation\Beams\BeamEffectiveDepthCalculator;
use App\StructuralCalculation\Beams\BeamEffectiveDepthResult;
use App\StructuralCalculation\Beams\BeamFlexuralConcreteDesignStrength;
use App\StructuralCalculation\Beams\BeamFlexuralDesignStrengthsCalculator;
use App\StructuralCalculation\Beams\BeamFlexuralDetailingAssumptions;
use App\StructuralCalculation\Beams\BeamGeometry;
use App\StructuralCalculation\Beams\BeamReducedMomentCalculator;
use App\StructuralCalculation\Beams\BeamReducedMomentException;
use App\StructuralCalculation\Beams\BeamReducedMomentRejectionReason;
use App\StructuralCalculation\Beams\BeamServiceabilityCombinationCalculator;
use App\StructuralCalculation\Beams\BeamUltimateCombinationCalculator;
use App\StructuralCalculation\Beams\CharacteristicActionsCalculator;
use App\StructuralCalculation\Beams\LongitudinalBarDiameterSource;
use App\StructuralCalculation\Beams\SelfWeightCalculator;
use App\StructuralCalculation\Beams\SimplySupportedBeamBendingMomentCalculator;
use App\StructuralCalculation\Eurocode\Actions\ReinforcedConcreteUnitWeightRepository;
use App\StructuralCalculation\Eurocode\Cover\CoverCalculationInput;
use App\StructuralCalculation\Eurocode\Cover\CoverMode;
use App\StructuralCalculation\Eurocode\Cover\NominalCoverCalculator;
use App\StructuralCalculation\Eurocode\Profiles\FrenchEurocodeProfileRepository;
use App\StructuralCalculation\Eurocode\Profiles\FundamentalUltimateCombinationExpression;
use App\StructuralCalculation\Materials\Concrete\ConcreteStrengthClass;
use App\StructuralCalculation\Units\MomentConverter;

function beamReducedMomentCalculator(): BeamReducedMomentCalculator
{
    return app(BeamReducedMomentCalculator::class);
}

function reducedMomentInput(float $designMoment = 95.45859375): BeamBendingMoment
{
    return new BeamBendingMoment(18.075, $designMoment, FundamentalUltimateCombinationExpression::EN1990_6_10, 'Mmax = w × l_eff² / 8');
}

function reducedMomentDepth(float $depth = 554): BeamEffectiveDepthResult
{
    return new BeamEffectiveDepthResult(
        BeamCalculationMode::DESIGN,
        600,
        30,
        8,
        16,
        LongitudinalBarDiameterSource::CONFIG,
        46,
        $depth,
    );
}

function reducedMomentConcrete(float $fcd = 20): BeamFlexuralConcreteDesignStrength
{
    return new BeamFlexuralConcreteDesignStrength(ConcreteStrengthClass::C30_37, 30, 1, 1.5, $fcd);
}

it('calculates the reduced design moment with coherent N and mm units', function () {
    $result = beamReducedMomentCalculator()->calculate(
        reducedMomentInput(),
        new BeamGeometry(6500, 300, 600),
        reducedMomentDepth(),
        reducedMomentConcrete(),
    );
    $expectedNormalizationTerm = 300 * 554 ** 2 * 20;
    $expectedMuEd = 95.45859375 * 1_000_000 / $expectedNormalizationTerm;

    expect($result->designMoment)->toBe(95.45859375)
        ->and($result->designMomentInNewtonMillimetres)->toBe(95458593.75)
        ->and($result->sectionWidth)->toBe(300.0)
        ->and($result->effectiveDepth)->toBe(554.0)
        ->and($result->concreteDesignStrength)->toBe(20.0)
        ->and($result->normalizationTerm)->toBe((float) $expectedNormalizationTerm)
        ->and(abs($result->reducedDesignMoment - $expectedMuEd))->toBeLessThan(0.000000001)
        ->and($result::DIMENSIONLESS_UNIT)->toBe('dimensionless')
        ->and($result::FORMULA)->toBe('μEd = MEd_Nmm / (b × d² × fcd)');
});

it('uses d rather than h when normalizing the design moment', function () {
    $result = beamReducedMomentCalculator()->calculate(
        reducedMomentInput(100),
        new BeamGeometry(6500, 300, 600),
        reducedMomentDepth(500),
        reducedMomentConcrete(20),
    );

    expect(abs($result->reducedDesignMoment - (100_000_000 / (300 * 500 ** 2 * 20))))->toBeLessThan(0.000000001)
        ->and($result->reducedDesignMoment)->not->toBe(100_000_000 / (300 * 600 ** 2 * 20));
});

it('keeps a zero design moment valid', function () {
    $result = beamReducedMomentCalculator()->calculate(
        reducedMomentInput(0),
        new BeamGeometry(6500, 300, 600),
        reducedMomentDepth(),
        reducedMomentConcrete(),
    );

    expect($result->designMomentInNewtonMillimetres)->toBe(0.0)
        ->and($result->reducedDesignMoment)->toBe(0.0);
});

it('converts kilonewton metres to newton millimetres through the units infrastructure', function () {
    expect(app(MomentConverter::class)->kilonewtonMetresToNewtonMillimetres(1))->toBe(1_000_000.0);
});

it('rejects impossible reduced-moment inputs', function (
    BeamBendingMoment $moment,
    BeamGeometry $geometry,
    BeamEffectiveDepthResult $depth,
    BeamFlexuralConcreteDesignStrength $concrete,
    BeamReducedMomentRejectionReason $reason,
) {
    try {
        beamReducedMomentCalculator()->calculate($moment, $geometry, $depth, $concrete);
    } catch (BeamReducedMomentException $exception) {
        expect($exception->reason)->toBe($reason);

        return;
    }

    throw new RuntimeException('Expected invalid reduced-moment input to be rejected.');
})->with([
    'negative design moment' => [reducedMomentInput(-1), new BeamGeometry(6500, 300, 600), reducedMomentDepth(), reducedMomentConcrete(), BeamReducedMomentRejectionReason::INVALID_DESIGN_MOMENT],
    'non-positive width' => [reducedMomentInput(), new BeamGeometry(6500, 0, 600), reducedMomentDepth(), reducedMomentConcrete(), BeamReducedMomentRejectionReason::INVALID_SECTION_WIDTH],
    'non-positive effective depth' => [reducedMomentInput(), new BeamGeometry(6500, 300, 600), reducedMomentDepth(0), reducedMomentConcrete(), BeamReducedMomentRejectionReason::INVALID_EFFECTIVE_DEPTH],
    'non-positive fcd' => [reducedMomentInput(), new BeamGeometry(6500, 300, 600), reducedMomentDepth(), reducedMomentConcrete(0), BeamReducedMomentRejectionReason::INVALID_CONCRETE_DESIGN_STRENGTH],
]);

it('chains input, structural analysis, cover, effective depth and material strengths into muEd', function () {
    $setup = app(BeamCalculationInputFactory::class)->fromPayload([
        'configuration' => [
            'calculationMode' => 'DESIGN',
            'elementType' => 'BEAM',
            'materialType' => 'REINFORCED_CONCRETE',
            'sectionType' => 'RECTANGULAR',
            'supportSystem' => 'SIMPLY_SUPPORTED',
            'loadModel' => 'UNIFORMLY_DISTRIBUTED',
            'designCodeProfile' => 'NF_EN_1992_1_1_2005_FR',
            'designSituation' => 'PERSISTENT_TRANSIENT',
        ],
        'geometry' => ['effectiveSpan' => 6500, 'width' => 300, 'height' => 600, 'unit' => 'mm'],
        'materials' => ['concreteClass' => 'C30/37', 'steelGrade' => 'B500B', 'exposureClasses' => ['XC4']],
        'loads' => [
            'permanent' => ['includeSelfWeight' => true, 'additionalPermanentLoad' => 5, 'unit' => 'kN/m'],
            'variable' => ['category' => 'A', 'characteristicLoad' => 3.5, 'unit' => 'kN/m'],
        ],
    ]);
    $profile = app(FrenchEurocodeProfileRepository::class)->get();
    $selfWeight = app(SelfWeightCalculator::class)->calculate(
        $setup->geometry,
        $setup->permanentLoads->includeSelfWeight,
        app(ReinforcedConcreteUnitWeightRepository::class)->normalWeightReinforcedConcrete(),
    );
    $actions = app(CharacteristicActionsCalculator::class)->calculate($selfWeight, $setup->permanentLoads, $setup->variableLoad);
    $ultimate = app(BeamUltimateCombinationCalculator::class)->calculate($actions, $profile);
    $serviceability = app(BeamServiceabilityCombinationCalculator::class)->calculate($actions, $profile);
    $bending = app(SimplySupportedBeamBendingMomentCalculator::class)->calculate($setup->configuration, $setup->geometry, $ultimate, $serviceability);
    $cover = app(NominalCoverCalculator::class)->calculate(
        new CoverCalculationInput(
            coverMode: CoverMode::AUTO,
            exposureClasses: $setup->materials->exposureClasses,
            concreteClass: $setup->materials->concreteClass,
            designWorkingLifeYears: 50,
            reinforcementDiameter: BeamFlexuralDetailingAssumptions::mvp()->transverseReinforcementDiameter,
        ),
        $profile,
    );
    $depth = app(BeamEffectiveDepthCalculator::class)->calculate(
        $setup->configuration->calculationMode,
        $setup->geometry,
        $cover,
        BeamFlexuralDetailingAssumptions::mvp(),
    );
    $strengths = app(BeamFlexuralDesignStrengthsCalculator::class)->calculate($setup->materials, $profile);
    $result = beamReducedMomentCalculator()->calculate($bending->ultimate, $setup->geometry, $depth, $strengths->concrete);
    $expected = $bending->ultimate->maximumMoment * 1_000_000
        / ($setup->geometry->width * $depth->effectiveDepth ** 2 * $strengths->concrete->fcd);

    expect(abs($bending->ultimate->maximumMoment - 95.45859375))->toBeLessThan(0.000000001)
        ->and(abs($result->designMomentInNewtonMillimetres - 95458593.75))->toBeLessThan(0.0000001)
        ->and($result->effectiveDepth)->toBe($depth->effectiveDepth)
        ->and($result->concreteDesignStrength)->toBe(20.0)
        ->and(abs($result->reducedDesignMoment - $expected))->toBeLessThan(0.000000001);
});
