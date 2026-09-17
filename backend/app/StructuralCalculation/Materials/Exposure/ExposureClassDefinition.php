<?php

namespace App\StructuralCalculation\Materials\Exposure;

/**
 * Description intrinsèque d'une classe d'exposition.
 *
 * Cette définition ne porte aucune exigence d'enrobage : celle-ci dépendra
 * notamment du profil normatif, de la classe structurale et de l'exécution.
 */
final readonly class ExposureClassDefinition
{
    public function __construct(
        public ExposureClassCode $code,
        public ExposureFamily $family,
        public string $label,
        public string $description,
        public ?DegradationMechanism $degradationMechanism,
    ) {}
}
