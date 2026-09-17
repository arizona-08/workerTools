<?php

namespace App\StructuralCalculation\Materials\Exposure;

/**
 * Ensemble des classes d'exposition applicables à un même élément.
 *
 * La détermination ultérieure de l'exigence gouvernante reste du ressort des
 * règles de durabilité ; aucune priorité n'est définie ici.
 */
final readonly class ExposureConditions
{
    /** @var list<ExposureClassCode> */
    public array $exposureClasses;

    public function __construct(ExposureClassCode ...$exposureClasses)
    {
        $this->exposureClasses = $exposureClasses;
    }
}
