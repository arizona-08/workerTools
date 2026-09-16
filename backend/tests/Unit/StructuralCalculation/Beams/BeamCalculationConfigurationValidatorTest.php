<?php

use App\StructuralCalculation\Beams\BeamCalculationConfiguration;
use App\StructuralCalculation\Beams\BeamCalculationConfigurationFactory;
use App\StructuralCalculation\Beams\BeamCalculationConfigurationValidator;
use App\StructuralCalculation\Beams\BeamCalculationMode;
use App\StructuralCalculation\Beams\BeamConfigurationException;
use App\StructuralCalculation\Beams\BeamConfigurationRejectionReason;
use App\StructuralCalculation\Beams\BeamLoadModel;
use App\StructuralCalculation\Beams\BeamSectionType;
use App\StructuralCalculation\Beams\BeamSubmodule;
use App\StructuralCalculation\Beams\BeamSupportSystem;
use App\StructuralCalculation\DesignSituation;
use App\StructuralCalculation\Eurocode\Profiles\DesignCodeProfileIdentifier;
use App\StructuralCalculation\MaterialType;

function beamConfigurationValidator(): BeamCalculationConfigurationValidator
{
    return app(BeamCalculationConfigurationValidator::class);
}

it('validates the explicit V1 beam configuration and reuses the French profile', function () {
    $configuration = BeamCalculationConfiguration::supported();

    beamConfigurationValidator()->validate($configuration);

    expect($configuration->sectionType)->toBe(BeamSectionType::RECTANGULAR)
        ->and($configuration->calculationMode)->toBe(BeamCalculationMode::DESIGN)
        ->and($configuration->materialType)->toBe(MaterialType::REINFORCED_CONCRETE)
        ->and($configuration->submodule)->toBe(BeamSubmodule::BEAM_SIMPLE_RECTANGULAR)
        ->and($configuration->supportSystem)->toBe(BeamSupportSystem::SIMPLY_SUPPORTED)
        ->and($configuration->loadModel)->toBe(BeamLoadModel::UNIFORMLY_DISTRIBUTED)
        ->and($configuration->designSituation)->toBe(DesignSituation::PERSISTENT_TRANSIENT)
        ->and($configuration->designCodeProfile)->toBe(DesignCodeProfileIdentifier::NF_EN_1992_1_1_2005_FR);
});

it('maps each recognized Beam submodule to its explicit support system', function (BeamSubmodule $submodule, BeamSupportSystem $supportSystem) {
    expect($submodule->supportSystem())->toBe($supportSystem);
})->with([
    'simply supported rectangular Beam' => [BeamSubmodule::BEAM_SIMPLE_RECTANGULAR, BeamSupportSystem::SIMPLY_SUPPORTED],
    'rectangular cantilever Beam' => [BeamSubmodule::BEAM_CANTILEVER_RECTANGULAR, BeamSupportSystem::CANTILEVER],
]);

it('accepts both supported calculation modes in the beam configuration', function (BeamCalculationMode $mode) {
    $values = configurationValues(calculationMode: $mode);
    $configuration = new BeamCalculationConfiguration(...$values);

    beamConfigurationValidator()->validate($configuration);

    expect($configuration->calculationMode)->toBe($mode);
})->with([BeamCalculationMode::DESIGN, BeamCalculationMode::VERIFICATION]);

it('rejects valid conceptual configurations that are not supported by the V1', function (BeamCalculationConfiguration $configuration, BeamConfigurationRejectionReason $reason) {
    try {
        beamConfigurationValidator()->validate($configuration);
    } catch (BeamConfigurationException $exception) {
        expect($exception->reason)->toBe($reason);

        return;
    }

    throw new RuntimeException('Expected the unsupported configuration to be rejected.');
})->with([
    'T section' => [new BeamCalculationConfiguration(...[...configurationValues(sectionType: BeamSectionType::T_SECTION)]), BeamConfigurationRejectionReason::UNSUPPORTED_SECTION_TYPE],
    'continuous support' => [new BeamCalculationConfiguration(...[...configurationValues(supportSystem: BeamSupportSystem::CONTINUOUS)]), BeamConfigurationRejectionReason::UNSUPPORTED_SUPPORT_SYSTEM],
    'point load' => [new BeamCalculationConfiguration(...[...configurationValues(loadModel: BeamLoadModel::POINT_LOAD)]), BeamConfigurationRejectionReason::UNSUPPORTED_LOAD_MODEL],
]);

it('distinguishes an invalid external value from a supported-value request', function () {
    app(BeamCalculationConfigurationFactory::class)->fromValues(
        'UNKNOWN_MODE',
        'BEAM',
        'REINFORCED_CONCRETE',
        'UNKNOWN_SECTION',
        'SIMPLY_SUPPORTED',
        'UNIFORMLY_DISTRIBUTED',
        'NF_EN_1992_1_1_2005_FR',
        'PERSISTENT_TRANSIENT',
    );
})->throws(BeamConfigurationException::class, BeamConfigurationRejectionReason::INVALID_CONFIGURATION_VALUE->value);

it('rejects an unknown Beam submodule instead of letting a free string enter the domain', function () {
    app(BeamCalculationConfigurationFactory::class)->fromValues(
        'DESIGN',
        'BEAM',
        'REINFORCED_CONCRETE',
        'RECTANGULAR',
        'SIMPLY_SUPPORTED',
        'UNIFORMLY_DISTRIBUTED',
        'NF_EN_1992_1_1_2005_FR',
        'PERSISTENT_TRANSIENT',
        'BEAM_UNKNOWN',
    );
})->throws(BeamConfigurationException::class, BeamConfigurationRejectionReason::INVALID_CONFIGURATION_VALUE->value);

it('accepts the recognized cantilever configuration until the analysis router resolves it', function () {
    $configuration = app(BeamCalculationConfigurationFactory::class)->fromValues(
        'DESIGN',
        'BEAM',
        'REINFORCED_CONCRETE',
        'RECTANGULAR',
        'CANTILEVER',
        'UNIFORMLY_DISTRIBUTED',
        'NF_EN_1992_1_1_2005_FR',
        'PERSISTENT_TRANSIENT',
        'BEAM_CANTILEVER_RECTANGULAR',
    );

    expect($configuration->submodule)->toBe(BeamSubmodule::BEAM_CANTILEVER_RECTANGULAR)
        ->and($configuration->supportSystem)->toBe(BeamSupportSystem::CANTILEVER)
        ->and(fn () => beamConfigurationValidator()->validate($configuration))->not->toThrow(BeamConfigurationException::class);
});

/** @return array<string, mixed> */
function configurationValues(
    ?BeamCalculationMode $calculationMode = null,
    ?BeamSectionType $sectionType = null,
    ?BeamSupportSystem $supportSystem = null,
    ?BeamLoadModel $loadModel = null,
): array {
    $configuration = BeamCalculationConfiguration::supported();

    return [
        'calculationMode' => $calculationMode ?? $configuration->calculationMode,
        'elementType' => $configuration->elementType,
        'materialType' => $configuration->materialType,
        'sectionType' => $sectionType ?? $configuration->sectionType,
        'supportSystem' => $supportSystem ?? $configuration->supportSystem,
        'loadModel' => $loadModel ?? $configuration->loadModel,
        'designCodeProfile' => $configuration->designCodeProfile,
        'designSituation' => $configuration->designSituation,
    ];
}
