<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Eurocode\Profiles\DesignCodeProfile;
use App\StructuralCalculation\Eurocode\Serviceability\CrackedElasticSectionCalculator;
use App\StructuralCalculation\Eurocode\Serviceability\CrackedElasticSectionResult;
use App\StructuralCalculation\Materials\Concrete\ConcreteProperties;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelProperties;
use App\StructuralCalculation\Units\MomentConverter;

/** Vérifie les contraintes ELS sur section fissurée élastique instantanée. */
final readonly class BeamServiceStressVerificationCalculator
{
    public function __construct(
        private MomentConverter $momentConverter,
        private CrackedElasticSectionCalculator $crackedSection,
    ) {}

    public function calculate(
        BeamBendingMomentResult $moments,
        BeamGeometry $geometry,
        BeamEffectiveDepthResult $depth,
        ConcreteProperties $concrete,
        ReinforcementSteelProperties $steel,
        float $tensionArea,
        DesignCodeProfile $profile,
        float $compressedArea = 0.0,
    ): BeamServiceStressVerificationResult {
        if ($compressedArea !== 0.0) {
            throw new BeamServiceStressVerificationException(BeamServiceStressVerificationRejectionReason::UNSUPPORTED_COMPRESSED_REINFORCEMENT_SLS);
        }

        $this->ensurePositiveFinite($geometry->width, BeamServiceStressVerificationRejectionReason::INVALID_SECTION_WIDTH);
        $this->ensurePositiveFinite($geometry->height, BeamServiceStressVerificationRejectionReason::INVALID_SECTION_HEIGHT);
        $this->ensurePositiveFinite($depth->effectiveDepth, BeamServiceStressVerificationRejectionReason::INVALID_EFFECTIVE_DEPTH);
        if ($depth->effectiveDepth >= $geometry->height) {
            throw new BeamServiceStressVerificationException(BeamServiceStressVerificationRejectionReason::INVALID_EFFECTIVE_DEPTH);
        }
        $this->ensurePositiveFinite($tensionArea, BeamServiceStressVerificationRejectionReason::INVALID_TENSION_REINFORCEMENT_AREA);
        $this->ensurePositiveFinite($concrete->ecm, BeamServiceStressVerificationRejectionReason::INVALID_CONCRETE_MODULUS);
        $this->ensurePositiveFinite($steel->es, BeamServiceStressVerificationRejectionReason::INVALID_STEEL_MODULUS);
        $this->ensurePositiveFinite($concrete->fck, BeamServiceStressVerificationRejectionReason::INVALID_CONCRETE_STRENGTH);
        $this->ensurePositiveFinite($steel->fyk, BeamServiceStressVerificationRejectionReason::INVALID_STEEL_STRENGTH);
        $this->ensureNonNegativeFinite($moments->characteristic->magnitude());
        $this->ensureNonNegativeFinite($moments->frequent->magnitude());
        $this->ensureNonNegativeFinite($moments->quasiPermanent->magnitude());

        try {
            $section = $this->crackedSection->calculate($geometry->width, $depth->effectiveDepth, $tensionArea, $concrete->ecm, $steel->es);
        } catch (\InvalidArgumentException) {
            throw new BeamServiceStressVerificationException(BeamServiceStressVerificationRejectionReason::INVALID_CRACKED_NEUTRAL_AXIS);
        }
        $alpha = $section->modularRatio;
        $x = $section->neutralAxisDepth;
        $inertia = $section->secondMomentOfArea;

        $requirements = $profile->beamServiceStressRequirements;
        $this->ensurePositiveFinite($requirements->concreteCharacteristicStressLimitFactor, BeamServiceStressVerificationRejectionReason::INVALID_STRESS_LIMIT_FACTOR);
        $this->ensurePositiveFinite($requirements->concreteQuasiPermanentStressLimitFactor, BeamServiceStressVerificationRejectionReason::INVALID_STRESS_LIMIT_FACTOR);
        $this->ensurePositiveFinite($requirements->reinforcementCharacteristicStressLimitFactor, BeamServiceStressVerificationRejectionReason::INVALID_STRESS_LIMIT_FACTOR);
        $concreteChar = $this->check('CONCRETE_CHARACTERISTIC_STRESS', $moments->characteristic, $section, $depth->effectiveDepth, $requirements->concreteCharacteristicStressLimitFactor * $concrete->fck, false, 'CHARACTERISTIC');
        $steelChar = $this->check('STEEL_CHARACTERISTIC_STRESS', $moments->characteristic, $section, $depth->effectiveDepth, $requirements->reinforcementCharacteristicStressLimitFactor * $steel->fyk, true, 'CHARACTERISTIC');
        $concreteQp = $this->check('CONCRETE_QUASI_PERMANENT_STRESS', $moments->quasiPermanent, $section, $depth->effectiveDepth, $requirements->concreteQuasiPermanentStressLimitFactor * $concrete->fck, false, 'QUASI_PERMANENT');
        $steelQp = $this->check('STEEL_QUASI_PERMANENT_STRESS', $moments->quasiPermanent, $section, $depth->effectiveDepth, null, true, 'QUASI_PERMANENT');
        $frequent = new BeamServiceStressCheck('FREQUENT_STRESS', null, null, null, BeamServiceStressCheckStatus::NOT_APPLICABLE, 'FREQUENT', null, 'NOT_APPLICABLE_IN_BEAM_SLS_01');
        $checks = [$concreteChar, $steelChar, $concreteQp];
        $failed = array_filter($checks, fn ($check) => $check->status === BeamServiceStressCheckStatus::NOT_COMPLIANT);
        $governing = collect($checks)->sortByDesc('utilization')->first();

        return new BeamServiceStressVerificationResult(
            $failed === [] ? BeamServiceStressCheckStatus::COMPLIANT : BeamServiceStressCheckStatus::NOT_COMPLIANT,
            BeamServiceStressVerificationResult::SECTION_MODEL,
            $alpha,
            $x,
            $inertia,
            $concreteChar,
            $steelChar,
            $concreteQp,
            $steelQp,
            $frequent,
            $governing->name,
            $depth->tensionFace,
        );
    }

    public function notChecked(): BeamServiceStressVerificationResult
    {
        $check = new BeamServiceStressCheck('NOT_CHECKED', null, null, null, BeamServiceStressCheckStatus::NOT_CHECKED, 'NONE', null, 'NOT_CHECKED');

        return new BeamServiceStressVerificationResult(
            BeamServiceStressCheckStatus::NOT_CHECKED,
            BeamServiceStressVerificationResult::SECTION_MODEL,
            null,
            null,
            null,
            $check,
            $check,
            $check,
            $check,
            $check,
            null,
        );
    }

    private function check(string $name, BeamBendingMoment $moment, CrackedElasticSectionResult $section, float $d, ?float $limit, bool $steel, string $combination): BeamServiceStressCheck
    {
        $magnitude = $moment->magnitude();
        $stress = $steel ? $this->crackedSection->steelStress($magnitude, $d, $section) : $this->momentConverter->kilonewtonMetresToNewtonMillimetres($magnitude) * $section->neutralAxisDepth / $section->secondMomentOfArea;

        return new BeamServiceStressCheck(
            $name,
            $stress,
            $limit,
            $limit === null ? null : $stress / $limit,
            $limit === null ? BeamServiceStressCheckStatus::NOT_APPLICABLE : ($stress <= $limit ? BeamServiceStressCheckStatus::COMPLIANT : BeamServiceStressCheckStatus::NOT_COMPLIANT),
            $combination,
            $moment->maximumMoment,
            BeamServiceStressVerificationResult::SECTION_MODEL,
        );
    }

    private function ensureNonNegativeFinite(float $value): void
    {
        if (! is_finite($value) || $value < 0) {
            throw new BeamServiceStressVerificationException(BeamServiceStressVerificationRejectionReason::INVALID_SERVICE_MOMENT);
        }
    }

    private function ensurePositiveFinite(float $value, BeamServiceStressVerificationRejectionReason $reason): void
    {
        if (! is_finite($value) || $value <= 0) {
            throw new BeamServiceStressVerificationException($reason);
        }
    }
}
