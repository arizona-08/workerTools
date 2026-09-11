<?php

namespace App\Http\Controllers;

use App\StructuralCalculation\Materials\Concrete\ConcreteClassRepository;
use App\StructuralCalculation\Materials\Exposure\ExposureClassRepository;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementBarDiameterCatalog;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGradeRepository;
use Illuminate\Http\JsonResponse;

final class BeamMaterialCatalogController extends Controller
{
    public function __invoke(
        ConcreteClassRepository $concreteClasses,
        ReinforcementSteelGradeRepository $steelGrades,
        ReinforcementBarDiameterCatalog $reinforcementBarDiameters,
        ExposureClassRepository $exposureClasses,
    ): JsonResponse {
        return response()->json([
            'concreteClasses' => array_map(fn ($material) => $material->strengthClass->value, $concreteClasses->all()),
            'steelGrades' => array_map(fn ($material) => $material->grade->value, $steelGrades->all()),
            'reinforcementBarDiameters' => $reinforcementBarDiameters->all(),
            'exposureClasses' => array_map(fn ($exposure) => ['code' => $exposure->code->value, 'label' => $exposure->label], $exposureClasses->all()),
        ]);
    }
}
