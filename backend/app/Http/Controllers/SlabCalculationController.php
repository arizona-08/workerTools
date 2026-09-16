<?php

namespace App\Http\Controllers;

use App\StructuralCalculation\CalculationFailureMessage;
use App\StructuralCalculation\Slabs\SlabCalculationInputFactory;
use App\StructuralCalculation\Slabs\SlabCalculationOrchestrator;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LogicException;

final class SlabCalculationController extends Controller
{
    public function __invoke(Request $request, SlabCalculationInputFactory $inputs, SlabCalculationOrchestrator $calculations, CalculationFailureMessage $failureMessage): JsonResponse
    {
        try {
            return response()->json($calculations->calculate($inputs->fromPayload($request->all())));
        } catch (DomainException|LogicException $exception) {
            return response()->json(['message' => $failureMessage->for($exception), 'reason' => $exception->getMessage()], 422);
        }
    }
}
