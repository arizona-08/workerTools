<?php

namespace App\StructuralCalculation\Beams;

/**
 * Assemble le contrat d'entrée Poutre MVP sans déclencher de calcul.
 * Les propriétés dérivées ou mécaniques envoyées par le client sont refusées.
 */
final readonly class BeamCalculationInputFactory
{
    public function __construct(
        private BeamCalculationConfigurationFactory $configurationFactory,
        private BeamCalculationConfigurationValidator $configurationValidator,
        private BeamGeometryFactory $geometryFactory,
        private BeamMaterialsFactory $materialsFactory,
        private BeamPermanentLoadsFactory $permanentLoadsFactory,
        private BeamVariableLoadFactory $variableLoadFactory,
        private BeamLongitudinalReinforcementFactory $reinforcementFactory,
    ) {}

    /** @param array<string, mixed> $payload */
    public function fromPayload(array $payload): BeamCalculationSetup
    {
        $this->assertOnlyKeys($payload, ['configuration', 'geometry', 'materials', 'loads', 'reinforcement']);
        $configurationValues = $this->section($payload, 'configuration', BeamCalculationInputRejectionReason::MISSING_CONFIGURATION, BeamCalculationInputRejectionReason::INVALID_CONFIGURATION_STRUCTURE);
        $this->assertOnlyKeys($configurationValues, ['calculationMode', 'elementType', 'materialType', 'sectionType', 'supportSystem', 'loadModel', 'designCodeProfile', 'designSituation']);
        $configuration = $this->configurationFactory->fromValues(
            $this->string($configurationValues, 'calculationMode', BeamCalculationInputRejectionReason::INVALID_CONFIGURATION_STRUCTURE),
            $this->string($configurationValues, 'elementType', BeamCalculationInputRejectionReason::INVALID_CONFIGURATION_STRUCTURE),
            $this->string($configurationValues, 'materialType', BeamCalculationInputRejectionReason::INVALID_CONFIGURATION_STRUCTURE),
            $this->string($configurationValues, 'sectionType', BeamCalculationInputRejectionReason::INVALID_CONFIGURATION_STRUCTURE),
            $this->string($configurationValues, 'supportSystem', BeamCalculationInputRejectionReason::INVALID_CONFIGURATION_STRUCTURE),
            $this->string($configurationValues, 'loadModel', BeamCalculationInputRejectionReason::INVALID_CONFIGURATION_STRUCTURE),
            $this->string($configurationValues, 'designCodeProfile', BeamCalculationInputRejectionReason::INVALID_CONFIGURATION_STRUCTURE),
            $this->string($configurationValues, 'designSituation', BeamCalculationInputRejectionReason::INVALID_CONFIGURATION_STRUCTURE),
        );
        $this->configurationValidator->validate($configuration);

        $geometryValues = $this->section($payload, 'geometry', BeamCalculationInputRejectionReason::MISSING_GEOMETRY, BeamCalculationInputRejectionReason::INVALID_GEOMETRY_STRUCTURE);
        $this->assertOnlyKeys($geometryValues, ['effectiveSpan', 'width', 'height', 'unit']);
        $this->assertUnit($geometryValues, 'unit', 'mm', BeamCalculationInputRejectionReason::INVALID_GEOMETRY_UNIT);
        $geometry = $this->geometryFactory->fromInternalValues($geometryValues['effectiveSpan'] ?? null, $geometryValues['width'] ?? null, $geometryValues['height'] ?? null);

        $materialValues = $this->section($payload, 'materials', BeamCalculationInputRejectionReason::MISSING_MATERIALS, BeamCalculationInputRejectionReason::INVALID_MATERIALS_STRUCTURE);
        $this->assertOnlyKeys($materialValues, ['concreteClass', 'steelGrade', 'exposureClasses']);
        $materials = $this->materialsFactory->fromValues($materialValues['concreteClass'] ?? null, $materialValues['steelGrade'] ?? null, $materialValues['exposureClasses'] ?? null);

        $loadValues = $this->section($payload, 'loads', BeamCalculationInputRejectionReason::MISSING_LOADS, BeamCalculationInputRejectionReason::INVALID_LOADS_STRUCTURE);
        $this->assertOnlyKeys($loadValues, ['permanent', 'variable']);
        $permanentValues = $this->section($loadValues, 'permanent', BeamCalculationInputRejectionReason::MISSING_LOADS, BeamCalculationInputRejectionReason::INVALID_LOADS_STRUCTURE);
        $this->assertOnlyKeys($permanentValues, ['includeSelfWeight', 'additionalPermanentLoad', 'unit']);
        $this->assertUnit($permanentValues, 'unit', 'kN/m', BeamCalculationInputRejectionReason::INVALID_PERMANENT_LOAD_UNIT);
        $permanentLoads = $this->permanentLoadsFactory->fromValues($permanentValues['includeSelfWeight'] ?? null, $permanentValues['additionalPermanentLoad'] ?? null);
        $variableValues = $this->section($loadValues, 'variable', BeamCalculationInputRejectionReason::MISSING_LOADS, BeamCalculationInputRejectionReason::INVALID_LOADS_STRUCTURE);
        $this->assertOnlyKeys($variableValues, ['category', 'characteristicLoad', 'unit']);
        $this->assertUnit($variableValues, 'unit', 'kN/m', BeamCalculationInputRejectionReason::INVALID_VARIABLE_LOAD_UNIT);
        $variableLoad = $this->variableLoadFactory->fromValues($variableValues['category'] ?? null, $variableValues['characteristicLoad'] ?? null);

        $reinforcement = $this->reinforcement($payload, $configuration->calculationMode);

        return new BeamCalculationSetup($configuration, $geometry, $materials, $permanentLoads, $variableLoad, $reinforcement);
    }

    /** @param array<string, mixed> $payload */
    private function reinforcement(array $payload, BeamCalculationMode $mode): ?BeamLongitudinalReinforcement
    {
        if (! array_key_exists('reinforcement', $payload)) {
            if ($mode === BeamCalculationMode::VERIFICATION) {
                throw new BeamCalculationInputException(BeamCalculationInputRejectionReason::MISSING_REINFORCEMENT);
            }

            return $this->reinforcementFactory->fromValues($mode);
        }
        if ($mode === BeamCalculationMode::DESIGN) {
            return $this->reinforcementFactory->fromValues($mode, 1, 8);
        }

        $reinforcementValues = $this->section($payload, 'reinforcement', BeamCalculationInputRejectionReason::MISSING_REINFORCEMENT, BeamCalculationInputRejectionReason::INVALID_REINFORCEMENT_STRUCTURE);
        $this->assertOnlyKeys($reinforcementValues, ['longitudinal']);
        $longitudinalValues = $this->section($reinforcementValues, 'longitudinal', BeamCalculationInputRejectionReason::MISSING_REINFORCEMENT, BeamCalculationInputRejectionReason::INVALID_REINFORCEMENT_STRUCTURE);
        $this->assertOnlyKeys($longitudinalValues, ['tension']);
        $tensionValues = $this->section($longitudinalValues, 'tension', BeamCalculationInputRejectionReason::MISSING_REINFORCEMENT, BeamCalculationInputRejectionReason::INVALID_REINFORCEMENT_STRUCTURE);
        $this->assertOnlyKeys($tensionValues, ['barCount', 'barDiameter', 'diameterUnit']);
        $this->assertUnit($tensionValues, 'diameterUnit', 'mm', BeamCalculationInputRejectionReason::INVALID_REINFORCEMENT_DIAMETER_UNIT);

        return $this->reinforcementFactory->fromValues($mode, $tensionValues['barCount'] ?? null, $tensionValues['barDiameter'] ?? null);
    }

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    private function section(array $payload, string $name, BeamCalculationInputRejectionReason $missingReason, BeamCalculationInputRejectionReason $invalidReason): array
    {
        if (! array_key_exists($name, $payload)) {
            throw new BeamCalculationInputException($missingReason);
        }
        if (! is_array($payload[$name])) {
            throw new BeamCalculationInputException($invalidReason);
        }

        return $payload[$name];
    }

    /** @param array<string, mixed> $values @param list<string> $allowed */
    private function assertOnlyKeys(array $values, array $allowed): void
    {
        if (array_diff(array_keys($values), $allowed) !== []) {
            throw new BeamCalculationInputException(BeamCalculationInputRejectionReason::UNEXPECTED_PAYLOAD_PROPERTY);
        }
    }

    /** @param array<string, mixed> $values */
    private function string(array $values, string $key, BeamCalculationInputRejectionReason $reason): string
    {
        if (! isset($values[$key]) || ! is_string($values[$key])) {
            throw new BeamCalculationInputException($reason);
        }

        return $values[$key];
    }

    /** @param array<string, mixed> $values */
    private function assertUnit(array $values, string $key, string $expected, BeamCalculationInputRejectionReason $reason): void
    {
        if (($values[$key] ?? null) !== $expected) {
            throw new BeamCalculationInputException($reason);
        }
    }
}
