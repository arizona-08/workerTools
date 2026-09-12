<?php

namespace App\StructuralCalculation\Eurocode\Concrete;

/** Résout λ et η du bloc rectangulaire simplifié EC2, sans extrapolation au-delà de fck 90 MPa. */
final class ConcreteRectangularStressBlockParametersCalculator
{
    private const NORMAL_STRENGTH_MAX_FCK = 50.0;

    private const MAX_SUPPORTED_FCK = 90.0;

    private const BASE_LAMBDA = 0.8;

    private const BASE_ETA = 1.0;

    private const LAMBDA_REDUCTION_DIVISOR = 400.0;

    private const ETA_REDUCTION_DIVISOR = 200.0;

    public function calculate(float $fck): ConcreteRectangularStressBlockParameters
    {
        if (! is_finite($fck) || $fck <= 0) {
            throw new ConcreteRectangularStressBlockException(ConcreteRectangularStressBlockRejectionReason::INVALID_CHARACTERISTIC_CONCRETE_STRENGTH);
        }
        if ($fck > self::MAX_SUPPORTED_FCK) {
            throw new ConcreteRectangularStressBlockException(ConcreteRectangularStressBlockRejectionReason::UNSUPPORTED_CHARACTERISTIC_CONCRETE_STRENGTH);
        }
        if ($fck <= self::NORMAL_STRENGTH_MAX_FCK) {
            return new ConcreteRectangularStressBlockParameters($fck, self::BASE_LAMBDA, self::BASE_ETA);
        }

        $strengthExcess = $fck - self::NORMAL_STRENGTH_MAX_FCK;

        return new ConcreteRectangularStressBlockParameters(
            fck: $fck,
            lambda: self::BASE_LAMBDA - $strengthExcess / self::LAMBDA_REDUCTION_DIVISOR,
            eta: self::BASE_ETA - $strengthExcess / self::ETA_REDUCTION_DIVISOR,
        );
    }
}
