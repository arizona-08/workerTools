<?php

namespace App\Http\Controllers;

use App\StructuralCalculation\Beams\BeamCalculationCapabilities;
use App\StructuralCalculation\Beams\BeamSubmoduleCatalog;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementBarDiameterCatalog;
use Illuminate\Http\JsonResponse;

final class BeamMaterialCatalogController extends Controller
{
    public function __invoke(
        BeamCalculationCapabilities $capabilities,
        ReinforcementBarDiameterCatalog $reinforcementBarDiameters,
        BeamSubmoduleCatalog $submodules,
    ): JsonResponse {
        return response()->json([
            'concreteClasses' => $capabilities->supportedConcreteClasses(),
            'steelGrades' => $capabilities->supportedSteelGrades(),
            'reinforcementBarDiameters' => $reinforcementBarDiameters->all(),
            'exposureClasses' => $capabilities->supportedExposureClasses(),
            'beamSubmodules' => array_map(
                fn ($submodule) => $submodule->toPublicArray(),
                $submodules->all(),
            ),
        ]);
    }
}
