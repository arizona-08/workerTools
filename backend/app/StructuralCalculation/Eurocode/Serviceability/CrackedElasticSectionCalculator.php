<?php

namespace App\StructuralCalculation\Eurocode\Serviceability;

use App\StructuralCalculation\Units\MomentConverter;

/** Noyau commun de section fissurée élastique instantanée pour sections rectangulaires simplement armées. */
final readonly class CrackedElasticSectionCalculator
{
    public function __construct(private MomentConverter $moments) {}

    public function calculate(float $width, float $effectiveDepth, float $tensionArea, float $concreteModulus, float $steelModulus): CrackedElasticSectionResult
    {
        foreach ([$width, $effectiveDepth, $tensionArea, $concreteModulus, $steelModulus] as $value) {
            if (! is_finite($value) || $value <= 0) {
                throw new \InvalidArgumentException('The cracked elastic section input is invalid.');
            }
        }

        $modularRatio = $steelModulus / $concreteModulus;
        $a = $width / 2;
        $b = $modularRatio * $tensionArea;
        $neutralAxisDepth = (-$b + sqrt($b ** 2 + 4 * $a * $modularRatio * $tensionArea * $effectiveDepth)) / (2 * $a);
        $secondMomentOfArea = $width * $neutralAxisDepth ** 3 / 3 + $modularRatio * $tensionArea * ($effectiveDepth - $neutralAxisDepth) ** 2;

        if (! is_finite($neutralAxisDepth) || $neutralAxisDepth <= 0 || $neutralAxisDepth >= $effectiveDepth
            || ! is_finite($secondMomentOfArea) || $secondMomentOfArea <= 0) {
            throw new \InvalidArgumentException('The cracked elastic section result is invalid.');
        }

        return new CrackedElasticSectionResult($modularRatio, $neutralAxisDepth, $secondMomentOfArea);
    }

    public function steelStress(float $momentInKilonewtonMetres, float $effectiveDepth, CrackedElasticSectionResult $section): float
    {
        if (! is_finite($momentInKilonewtonMetres) || $momentInKilonewtonMetres < 0) {
            throw new \InvalidArgumentException('The service moment is invalid.');
        }

        return $section->modularRatio * $this->moments->kilonewtonMetresToNewtonMillimetres($momentInKilonewtonMetres)
            * ($effectiveDepth - $section->neutralAxisDepth) / $section->secondMomentOfArea;
    }
}
