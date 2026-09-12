<?php

namespace App\StructuralCalculation\Eurocode\Concrete;

/** Paramètres εcu3 d'EN 1992-1-1 §3.1.7, limités aux bétons jusqu'à C90/105. */
final class ConcreteUltimateStrainParametersCalculator
{
    public const STRAIN_PER_MILLE = 0.001;

    public const NORMAL_STRENGTH_MAXIMUM_FCK = 50.0;

    public const MAXIMUM_SUPPORTED_FCK = 90.0;

    public const NORMAL_STRENGTH_ECU3_PER_MILLE = 3.5;

    public const HIGH_STRENGTH_ECU3_BASE_PER_MILLE = 2.6;

    public const HIGH_STRENGTH_ECU3_COEFFICIENT_PER_MILLE = 35.0;

    public const HIGH_STRENGTH_ECU3_REFERENCE_FCK = 90.0;

    public const HIGH_STRENGTH_ECU3_DIVISOR = 100.0;

    public function calculate(float $characteristicConcreteStrength): ConcreteUltimateStrainParameters
    {
        if (! is_finite($characteristicConcreteStrength) || $characteristicConcreteStrength <= 0) {
            throw new ConcreteUltimateStrainException(ConcreteUltimateStrainRejectionReason::INVALID_CHARACTERISTIC_CONCRETE_STRENGTH);
        }
        if ($characteristicConcreteStrength > self::MAXIMUM_SUPPORTED_FCK) {
            throw new ConcreteUltimateStrainException(ConcreteUltimateStrainRejectionReason::UNSUPPORTED_CHARACTERISTIC_CONCRETE_STRENGTH);
        }

        $ultimateStrainPerMille = $characteristicConcreteStrength <= self::NORMAL_STRENGTH_MAXIMUM_FCK
            ? self::NORMAL_STRENGTH_ECU3_PER_MILLE
            : self::HIGH_STRENGTH_ECU3_BASE_PER_MILLE + self::HIGH_STRENGTH_ECU3_COEFFICIENT_PER_MILLE
                * ((self::HIGH_STRENGTH_ECU3_REFERENCE_FCK - $characteristicConcreteStrength) / self::HIGH_STRENGTH_ECU3_DIVISOR) ** 4;

        return new ConcreteUltimateStrainParameters(
            characteristicConcreteStrength: $characteristicConcreteStrength,
            ultimateStrain: $ultimateStrainPerMille * self::STRAIN_PER_MILLE,
        );
    }
}
