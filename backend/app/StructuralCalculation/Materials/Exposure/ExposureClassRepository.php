<?php

namespace App\StructuralCalculation\Materials\Exposure;

/**
 * Référentiel des classes d'exposition de NF EN 1992-1-1:2005, tableau 4.1.
 *
 * Les libellés décrivent l'environnement ; les adaptations françaises de
 * durabilité restent rattachées au profil normatif et ne figurent pas ici.
 */
final class ExposureClassRepository
{
    /**
     * @var array<string, array{family: ExposureFamily, label: string, description: string, degradationMechanism: ?DegradationMechanism}>
     */
    private const DEFINITIONS_BY_CODE = [
        'X0' => [
            'family' => ExposureFamily::NO_RISK,
            'label' => 'Aucun risque de corrosion ou d’attaque',
            'description' => 'Béton sans armature ou béton armé en environnement très sec, sans risque de corrosion ni d’attaque.',
            'degradationMechanism' => null,
        ],
        'XC1' => [
            'family' => ExposureFamily::CARBONATION,
            'label' => 'Sec ou humide en permanence',
            'description' => 'Corrosion induite par carbonatation : environnement sec ou humide en permanence.',
            'degradationMechanism' => DegradationMechanism::REINFORCEMENT_CORROSION_BY_CARBONATION,
        ],
        'XC2' => [
            'family' => ExposureFamily::CARBONATION,
            'label' => 'Humide, rarement sec',
            'description' => 'Corrosion induite par carbonatation : environnement humide, rarement sec.',
            'degradationMechanism' => DegradationMechanism::REINFORCEMENT_CORROSION_BY_CARBONATION,
        ],
        'XC3' => [
            'family' => ExposureFamily::CARBONATION,
            'label' => 'Humidité modérée',
            'description' => 'Corrosion induite par carbonatation : humidité modérée.',
            'degradationMechanism' => DegradationMechanism::REINFORCEMENT_CORROSION_BY_CARBONATION,
        ],
        'XC4' => [
            'family' => ExposureFamily::CARBONATION,
            'label' => 'Alternance humidité/séchage',
            'description' => 'Corrosion induite par carbonatation : alternance d’humidité et de séchage.',
            'degradationMechanism' => DegradationMechanism::REINFORCEMENT_CORROSION_BY_CARBONATION,
        ],
        'XD1' => [
            'family' => ExposureFamily::CHLORIDES_NOT_SEAWATER,
            'label' => 'Chlorures, humidité modérée',
            'description' => 'Corrosion induite par des chlorures non marins, en humidité modérée.',
            'degradationMechanism' => DegradationMechanism::REINFORCEMENT_CORROSION_BY_CHLORIDES,
        ],
        'XD2' => [
            'family' => ExposureFamily::CHLORIDES_NOT_SEAWATER,
            'label' => 'Chlorures, humide rarement sec',
            'description' => 'Corrosion induite par des chlorures non marins, humide et rarement sec.',
            'degradationMechanism' => DegradationMechanism::REINFORCEMENT_CORROSION_BY_CHLORIDES,
        ],
        'XD3' => [
            'family' => ExposureFamily::CHLORIDES_NOT_SEAWATER,
            'label' => 'Chlorures, alternance humidité/séchage',
            'description' => 'Corrosion induite par des chlorures non marins, avec alternance humidité/séchage.',
            'degradationMechanism' => DegradationMechanism::REINFORCEMENT_CORROSION_BY_CHLORIDES,
        ],
        'XS1' => [
            'family' => ExposureFamily::CHLORIDES_SEAWATER,
            'label' => 'Air véhiculant du sel marin',
            'description' => 'Corrosion induite par des chlorures d’eau de mer, sans contact direct avec l’eau de mer.',
            'degradationMechanism' => DegradationMechanism::REINFORCEMENT_CORROSION_BY_CHLORIDES,
        ],
        'XS2' => [
            'family' => ExposureFamily::CHLORIDES_SEAWATER,
            'label' => 'Immergé en permanence',
            'description' => 'Corrosion induite par des chlorures d’eau de mer, en immersion permanente.',
            'degradationMechanism' => DegradationMechanism::REINFORCEMENT_CORROSION_BY_CHLORIDES,
        ],
        'XS3' => [
            'family' => ExposureFamily::CHLORIDES_SEAWATER,
            'label' => 'Marnage, projections ou embruns',
            'description' => 'Corrosion induite par des chlorures d’eau de mer, en zone de marnage, projections ou embruns.',
            'degradationMechanism' => DegradationMechanism::REINFORCEMENT_CORROSION_BY_CHLORIDES,
        ],
        'XF1' => [
            'family' => ExposureFamily::FREEZE_THAW,
            'label' => 'Gel/dégel, saturation modérée sans salage',
            'description' => 'Attaque gel/dégel avec saturation modérée en eau, sans agent de déverglaçage.',
            'degradationMechanism' => DegradationMechanism::FREEZE_THAW_DAMAGE,
        ],
        'XF2' => [
            'family' => ExposureFamily::FREEZE_THAW,
            'label' => 'Gel/dégel, saturation modérée avec salage',
            'description' => 'Attaque gel/dégel avec saturation modérée en eau et agents de déverglaçage.',
            'degradationMechanism' => DegradationMechanism::FREEZE_THAW_DAMAGE,
        ],
        'XF3' => [
            'family' => ExposureFamily::FREEZE_THAW,
            'label' => 'Gel/dégel, forte saturation sans salage',
            'description' => 'Attaque gel/dégel avec forte saturation en eau, sans agent de déverglaçage.',
            'degradationMechanism' => DegradationMechanism::FREEZE_THAW_DAMAGE,
        ],
        'XF4' => [
            'family' => ExposureFamily::FREEZE_THAW,
            'label' => 'Gel/dégel, forte saturation avec salage',
            'description' => 'Attaque gel/dégel avec forte saturation en eau et agents de déverglaçage ou eau de mer.',
            'degradationMechanism' => DegradationMechanism::FREEZE_THAW_DAMAGE,
        ],
        'XA1' => [
            'family' => ExposureFamily::CHEMICAL_ATTACK,
            'label' => 'Attaque chimique faible',
            'description' => 'Béton en contact avec un sol ou liquide faiblement agressif chimiquement.',
            'degradationMechanism' => DegradationMechanism::CHEMICAL_CONCRETE_ATTACK,
        ],
        'XA2' => [
            'family' => ExposureFamily::CHEMICAL_ATTACK,
            'label' => 'Attaque chimique modérée',
            'description' => 'Béton en contact avec un sol ou liquide modérément agressif chimiquement.',
            'degradationMechanism' => DegradationMechanism::CHEMICAL_CONCRETE_ATTACK,
        ],
        'XA3' => [
            'family' => ExposureFamily::CHEMICAL_ATTACK,
            'label' => 'Attaque chimique forte',
            'description' => 'Béton en contact avec un sol ou liquide fortement agressif chimiquement.',
            'degradationMechanism' => DegradationMechanism::CHEMICAL_CONCRETE_ATTACK,
        ],
    ];

    /** @return list<ExposureClassDefinition> */
    public function all(): array
    {
        return array_map(
            fn (ExposureClassCode $code): ExposureClassDefinition => $this->get($code),
            ExposureClassCode::cases(),
        );
    }

    public function find(string $code): ?ExposureClassDefinition
    {
        $exposureClassCode = ExposureClassCode::tryFrom($code);

        return $exposureClassCode === null ? null : $this->get($exposureClassCode);
    }

    public function get(ExposureClassCode $code): ExposureClassDefinition
    {
        $definition = self::DEFINITIONS_BY_CODE[$code->value];

        return new ExposureClassDefinition(
            code: $code,
            family: $definition['family'],
            label: $definition['label'],
            description: $definition['description'],
            degradationMechanism: $definition['degradationMechanism'],
        );
    }
}
