<?php

namespace App\Http\Controllers;

use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementBarDiameterCatalog;
use App\StructuralCalculation\Beams\BeamCalculationCapabilities;
use Illuminate\Http\JsonResponse;

final class BeamMaterialCatalogController extends Controller
{
    public function __invoke(
        BeamCalculationCapabilities $capabilities,
        ReinforcementBarDiameterCatalog $reinforcementBarDiameters,
    ): JsonResponse {
        return response()->json([
            'concreteClasses' => $capabilities->supportedConcreteClasses(),
            'steelGrades' => $capabilities->supportedSteelGrades(),
            'reinforcementBarDiameters' => $reinforcementBarDiameters->all(),
            'exposureClasses' => $capabilities->supportedExposureClasses(),
        ]);
    }
}
