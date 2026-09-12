<?php

namespace App\StructuralCalculation\Beams;

/** Stratégie de premier dimensionnement des étriers, distincte des bornes du profil. */
final readonly class BeamShearDesignAssumptions
{
    public function __construct(public float $designCotTheta) {}

    public static function mvp(): self
    {
        return new self(designCotTheta: 2.5);
    }
}
