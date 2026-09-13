<?php

use App\StructuralCalculation\Beams\BeamCalculationInputFactory;
use App\StructuralCalculation\Beams\BeamFlexuralDesignStrengthsCalculator;
use App\StructuralCalculation\Beams\BeamMaterials;
use App\StructuralCalculation\Eurocode\Profiles\DesignCodeProfile;
use App\StructuralCalculation\Eurocode\Profiles\FrenchEurocodeProfileRepository;
use App\StructuralCalculation\Eurocode\Profiles\MaterialSafetyFactors;
use App\StructuralCalculation\Materials\Concrete\ConcreteStrengthClass;
use App\StructuralCalculation\Materials\Exposure\ExposureClassCode;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGrade;

function beamFlexuralDesignStrengthsCalculator(): BeamFlexuralDesignStrengthsCalculator
{
    return app(BeamFlexuralDesignStrengthsCalculator::class);
}

function flexuralMaterials(ConcreteStrengthClass $concreteClass): BeamMaterials
{
    return new BeamMaterials($concreteClass, ReinforcementSteelGrade::B500B, [ExposureClassCode::XC1]);
}

it('resolves C30/37 and B500B through the repositories and existing EC2 calculators', function () {
    $profile = app(FrenchEurocodeProfileRepository::class)->get();
    $result = beamFlexuralDesignStrengthsCalculator()->calculate(flexuralMaterials(ConcreteStrengthClass::C30_37), $profile);

    expect($result->concrete->concreteClass)->toBe(ConcreteStrengthClass::C30_37)
        ->and($result->concrete->fck)->toBe(30.0)
        ->and($result->concrete->alphaCc)->toBe($profile->materialSafetyFactors->alphaCc)
        ->and($result->concrete->gammaC)->toBe($profile->materialSafetyFactors->gammaC)
        ->and($result->concrete->fcd)->toBe(20.0)
        ->and($result->concrete::UNIT)->toBe('MPa')
        ->and($result->steel->steelGrade)->toBe(ReinforcementSteelGrade::B500B)
        ->and($result->steel->fyk)->toBe(500.0)
        ->and($result->steel->gammaS)->toBe($profile->materialSafetyFactors->gammaS)
        ->and(abs($result->steel->fyd - 500 / 1.15))->toBeLessThan(0.000000001)
        ->and($result->steel::UNIT)->toBe('MPa');
});

it('uses the selected concrete class rather than a C30/37 constant', function () {
    $result = beamFlexuralDesignStrengthsCalculator()->calculate(
        flexuralMaterials(ConcreteStrengthClass::C20_25),
        app(FrenchEurocodeProfileRepository::class)->get(),
    );

    expect($result->concrete->concreteClass)->toBe(ConcreteStrengthClass::C20_25)
        ->and($result->concrete->fck)->toBe(20.0)
        ->and(abs($result->concrete->fcd - 20 / 1.5))->toBeLessThan(0.000000001);
});

it('uses alphaCc gammaC and gammaS from the supplied profile', function () {
    $reference = app(FrenchEurocodeProfileRepository::class)->get();
    $profile = new DesignCodeProfile(
        identifier: $reference->identifier,
        materialSafetyFactors: new MaterialSafetyFactors(gammaC: 2, gammaS: 1.25, alphaCc: 0.85),
        actionSafetyFactors: $reference->actionSafetyFactors,
        fundamentalUltimateCombinationExpression: $reference->fundamentalUltimateCombinationExpression,
        coverRequirements: $reference->coverRequirements,
        beamLongitudinalReinforcementRequirements: $reference->beamLongitudinalReinforcementRequirements,
        beamConcreteShearResistanceRequirements: $reference->beamConcreteShearResistanceRequirements,
        reinforcementSpacingRequirements: $reference->reinforcementSpacingRequirements,
        combinationFactorsByActionCategory: [],
    );
    $result = beamFlexuralDesignStrengthsCalculator()->calculate(flexuralMaterials(ConcreteStrengthClass::C30_37), $profile);

    expect($result->concrete->alphaCc)->toBe(0.85)
        ->and($result->concrete->gammaC)->toBe(2.0)
        ->and($result->concrete->fcd)->toBe(12.75)
        ->and($result->steel->gammaS)->toBe(1.25)
        ->and($result->steel->fyd)->toBe(400.0);
});

it('chains validated beam materials through the EC2 repositories into flexural design strengths', function () {
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
    $result = beamFlexuralDesignStrengthsCalculator()->calculate(
        $setup->materials,
        app(FrenchEurocodeProfileRepository::class)->get(),
    );

    expect($result->concrete->fck)->toBe(30.0)
        ->and($result->concrete->fcd)->toBe(20.0)
        ->and($result->steel->fyk)->toBe(500.0)
        ->and(abs($result->steel->fyd - 434.7826086956522))->toBeLessThan(0.000000001)
        ->and(property_exists($result, 'effectiveDepth'))->toBeFalse()
        ->and(property_exists($result, 'designLineLoad'))->toBeFalse();
});
