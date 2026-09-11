<?php

use App\StructuralCalculation\Beams\BeamCalculationInputFactory;
use App\StructuralCalculation\Beams\BeamCharacteristicActionsResult;
use App\StructuralCalculation\Beams\BeamUltimateCombinationCalculator;
use App\StructuralCalculation\Beams\CharacteristicActionsCalculator;
use App\StructuralCalculation\Beams\CharacteristicPermanentActions;
use App\StructuralCalculation\Beams\CharacteristicVariableAction;
use App\StructuralCalculation\Beams\SelfWeightCalculator;
use App\StructuralCalculation\Beams\SelfWeightResult;
use App\StructuralCalculation\Eurocode\Actions\ReinforcedConcreteUnitWeight;
use App\StructuralCalculation\Eurocode\Actions\ReinforcedConcreteUnitWeightRepository;
use App\StructuralCalculation\Eurocode\Profiles\FrenchEurocodeProfileRepository;
use App\StructuralCalculation\Eurocode\Profiles\FundamentalUltimateCombinationExpression;
use App\StructuralCalculation\Eurocode\Profiles\VariableActionCategory;

function beamUltimateCombinationCalculator(): BeamUltimateCombinationCalculator
{
    return app(BeamUltimateCombinationCalculator::class);
}

function characteristicActions(float $gkTotal, float $qk): BeamCharacteristicActionsResult
{
    $selfWeightLoad = min(4.5, $gkTotal);

    return new BeamCharacteristicActionsResult(
        new CharacteristicPermanentActions(
            new SelfWeightResult($selfWeightLoad > 0, 300, 600, 0.18, new ReinforcedConcreteUnitWeight(25), $selfWeightLoad),
            $gkTotal - $selfWeightLoad,
            $gkTotal,
        ),
        new CharacteristicVariableAction(VariableActionCategory::A_DOMESTIC_RESIDENTIAL_AREAS, $qk),
    );
}

function frenchProfile()
{
    return app(FrenchEurocodeProfileRepository::class)->get();
}

it('calculates the EN 1990 6.10 reference combination from profile factors', function () {
    $profile = frenchProfile();
    $result = beamUltimateCombinationCalculator()->calculate(characteristicActions(9.5, 3.5), $profile);

    expect($result->permanentCharacteristicLoad)->toBe(9.5)
        ->and($result->permanentPartialFactor)->toBe($profile->actionSafetyFactors->gammaGUnfavourable)
        ->and(abs($result->permanentDesignContribution - 12.825))->toBeLessThan(0.000000001)
        ->and($result->variableCharacteristicLoad)->toBe(3.5)
        ->and($result->variablePartialFactor)->toBe($profile->actionSafetyFactors->gammaQ)
        ->and(abs($result->variableDesignContribution - 5.25))->toBeLessThan(0.000000001)
        ->and(abs($result->designLineLoad - 18.075))->toBeLessThan(0.000000001)
        ->and($result->expressionReference)->toBe(FundamentalUltimateCombinationExpression::EN1990_6_10)
        ->and($result::UNIT)->toBe('kN/m')
        ->and($result::FORMULA)->toBe('wEd = γG,sup × Gk_total + γQ × Qk')
        ->and(property_exists($result, 'psi0'))->toBeFalse()
        ->and(property_exists($result, 'xi'))->toBeFalse();
});

it('keeps zero characteristic actions valid', function (float $gkTotal, float $qk, float $expected) {
    $result = beamUltimateCombinationCalculator()->calculate(characteristicActions($gkTotal, $qk), frenchProfile());

    expect(abs($result->designLineLoad - $expected))->toBeLessThan(0.000000001);
})->with([
    'zero Qk' => [9.5, 0.0, 12.825],
    'zero Gk total' => [0.0, 3.5, 5.25],
    'zero actions' => [0.0, 0.0, 0.0],
]);

it('chains validated input through characteristic actions into the 6.10 ELU line load', function () {
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
        'materials' => ['concreteClass' => 'C30/37', 'steelGrade' => 'B500B', 'exposureClasses' => ['XC1']],
        'loads' => [
            'permanent' => ['includeSelfWeight' => true, 'additionalPermanentLoad' => 5, 'unit' => 'kN/m'],
            'variable' => ['category' => 'A', 'characteristicLoad' => 3.5, 'unit' => 'kN/m'],
        ],
    ]);
    $selfWeight = app(SelfWeightCalculator::class)->calculate(
        $setup->geometry,
        $setup->permanentLoads->includeSelfWeight,
        app(ReinforcedConcreteUnitWeightRepository::class)->normalWeightReinforcedConcrete(),
    );
    $actions = app(CharacteristicActionsCalculator::class)->calculate($selfWeight, $setup->permanentLoads, $setup->variableLoad);
    $result = beamUltimateCombinationCalculator()->calculate($actions, frenchProfile());

    expect($actions->permanent->totalPermanentLoad)->toBe(9.5)
        ->and(abs($result->designLineLoad - 18.075))->toBeLessThan(0.000000001);
});
