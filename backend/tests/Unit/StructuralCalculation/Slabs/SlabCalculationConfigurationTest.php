<?php

use App\StructuralCalculation\DesignSituation;
use App\StructuralCalculation\ElementType;
use App\StructuralCalculation\Eurocode\Profiles\DesignCodeProfileIdentifier;
use App\StructuralCalculation\MaterialType;
use App\StructuralCalculation\Slabs\SlabCalculationConfiguration;
use App\StructuralCalculation\Slabs\SlabCalculationConfigurationFactory;
use App\StructuralCalculation\Slabs\SlabCalculationConfigurationValidator;
use App\StructuralCalculation\Slabs\SlabConfigurationException;
use App\StructuralCalculation\Slabs\SlabConfigurationRejectionReason;
use App\StructuralCalculation\Slabs\SlabLoadModel;
use App\StructuralCalculation\Slabs\SlabSpanningSystem;
use App\StructuralCalculation\Slabs\SlabStructuralSystem;
use App\StructuralCalculation\Slabs\SlabType;

it('validates the explicit fixed MVP slab configuration without starting a calculation', function () {
    $configuration = SlabCalculationConfiguration::mvp();

    app(SlabCalculationConfigurationValidator::class)->validate($configuration);

    expect($configuration->elementType)->toBe(ElementType::SLAB)
        ->and($configuration->slabType)->toBe(SlabType::SOLID)
        ->and($configuration->spanningSystem)->toBe(SlabSpanningSystem::ONE_WAY)
        ->and($configuration->structuralSystem)->toBe(SlabStructuralSystem::SINGLE_SPAN_SIMPLY_SUPPORTED_ON_OPPOSITE_SIDES)
        ->and($configuration->loadModel)->toBe(SlabLoadModel::VERTICAL_UNIFORMLY_DISTRIBUTED)
        ->and($configuration->materialType)->toBe(MaterialType::REINFORCED_CONCRETE)
        ->and($configuration->designCodeProfile)->toBe(DesignCodeProfileIdentifier::NF_EN_1992_1_1_2005_FR)
        ->and($configuration->designSituation)->toBe(DesignSituation::PERSISTENT_TRANSIENT)
        ->and(method_exists($configuration, 'calculate'))->toBeFalse();
});

it('rejects an external configuration identifier outside the representable slab MVP', function () {
    app(SlabCalculationConfigurationFactory::class)->fromValues(
        'SLAB',
        'RIBBED',
        'ONE_WAY',
        'SINGLE_SPAN_SIMPLY_SUPPORTED_ON_OPPOSITE_SIDES',
        'VERTICAL_UNIFORMLY_DISTRIBUTED',
        'REINFORCED_CONCRETE',
        'NF_EN_1992_1_1_2005_FR',
        'PERSISTENT_TRANSIENT',
    );
})->throws(SlabConfigurationException::class, SlabConfigurationRejectionReason::INVALID_CONFIGURATION_VALUE->value);

it('refuses a configuration for another existing structural element', function () {
    $mvp = SlabCalculationConfiguration::mvp();
    $configuration = new SlabCalculationConfiguration(
        ElementType::BEAM,
        $mvp->slabType,
        $mvp->spanningSystem,
        $mvp->structuralSystem,
        $mvp->loadModel,
        $mvp->materialType,
        $mvp->designCodeProfile,
        $mvp->designSituation,
    );

    app(SlabCalculationConfigurationValidator::class)->validate($configuration);
})->throws(SlabConfigurationException::class, SlabConfigurationRejectionReason::UNSUPPORTED_ELEMENT_TYPE->value);
