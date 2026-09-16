<?php

namespace App\StructuralCalculation\CalculationNotes;

use App\StructuralCalculation\Beams\BeamCalculationResponse;
use App\StructuralCalculation\Beams\BeamCalculationSetup;
use App\StructuralCalculation\Beams\BeamCharacteristicActionsResult;
use App\StructuralCalculation\Beams\BeamEffectiveDepthResult;
use App\StructuralCalculation\Beams\BeamLongitudinalReinforcementSource;
use App\StructuralCalculation\Beams\BeamReinforcementCandidateRecalculationResult;
use App\StructuralCalculation\Beams\BeamReinforcementPosition;
use App\StructuralCalculation\Beams\BeamStirrupProposalCandidate;
use App\StructuralCalculation\Beams\BeamSubmodule;
use App\StructuralCalculation\Beams\BeamVerificationComponent;
use App\StructuralCalculation\Eurocode\Cover\CoverCalculationResult;
use DateTimeImmutable;

/**
 * Projection sans recalcul du pipeline Poutre vers le contrat PDF commun.
 * Les résultats restent issus du BeamCalculationOrchestrator.
 */
final class BeamCalculationNoteMapper
{
    public function map(BeamCalculationSetup $input, BeamCalculationResponse $result, DateTimeImmutable $generatedAt): CalculationNoteDocument
    {
        $details = $result->details;
        $assumptions = $details->assumptions;
        $cover = $assumptions['cover'];
        $flexure = $details->flexure;
        $selected = $details->reinforcement['selectedCandidate'];
        $shear = $details->shear;
        $serviceability = $details->serviceability;

        return new CalculationNoteDocument(
            new CalculationNoteMetadata(
                'Note de calcul — '.$this->submoduleLabel($input->configuration->submodule),
                $input->configuration->elementType,
                $generatedAt,
                $input->configuration->designCodeProfile->value,
            ),
            $this->assumptions($input, $cover),
            $this->geometry($input, $cover, $selected->effectiveDepth),
            $this->materials($input, $flexure, $selected),
            $this->loads($input, $details->combinations['characteristicActions']),
            $this->combinations($details->combinations),
            $this->internalForces($details->internalForces, $input->configuration->submodule),
            $this->verifications($result, $flexure, $shear, $serviceability),
            $this->reinforcement($result, $selected, $shear['recommendedStirrup'] ?? null),
            new CalculationNoteFinalStatus(
                $result->summary->status,
                $result->summary->governingVerificationType,
                $result->summary->utilization,
                $this->summary($result),
            ),
            $details->warnings,
            ['Cette note présente les vérifications réalisées dans le périmètre actuellement supporté par WorkerTools.'],
        );
    }

    private function assumptions(BeamCalculationSetup $input, CoverCalculationResult $cover): CalculationNoteSection
    {
        return new CalculationNoteSection('assumptions', 'Hypothèses et paramètres', [
            $this->value('calculationMode', 'Mode de calcul', $input->configuration->calculationMode->value),
            $this->value('submodule', 'Type de poutre', $input->configuration->submodule->value),
            $this->value('materialType', 'Matériau', $input->configuration->materialType->value),
            $this->value('sectionType', 'Section', $input->configuration->sectionType->value),
            $this->value('supportSystem', 'Système statique', $input->configuration->supportSystem->value),
            $this->value('loadModel', 'Modèle de charge', $input->configuration->loadModel->value),
            $this->value('structuralHypotheses', 'Hypothèses structurales', $this->structuralHypotheses($input->configuration->submodule)),
            $this->value('designSituation', 'Situation de calcul', $input->configuration->designSituation->value),
            $this->value('coverMode', 'Mode d’enrobage', $cover->coverMode->value),
        ]);
    }

    private function geometry(BeamCalculationSetup $input, CoverCalculationResult $cover, BeamEffectiveDepthResult $depth): CalculationNoteSection
    {
        return new CalculationNoteSection('geometry', 'Géométrie', [
            $this->value('effectiveSpan', 'Portée effective l_eff', $input->geometry->effectiveSpan, 'mm'),
            $this->value('width', 'Largeur b', $input->geometry->width, 'mm'),
            $this->value('height', 'Hauteur h', $input->geometry->height, 'mm'),
            $this->value('nominalCover', 'Enrobage nominal c_nom', $cover->cNom, $cover->unit),
            $this->value('effectiveDepth', 'Hauteur utile finale d', $depth->effectiveDepth, 'mm'),
            $this->value('tensionSteelCentroidOffset', 'Position du centre des aciers tendus', $depth->tensionSteelCentroidOffset, 'mm'),
        ], [
            new CalculationNoteStep('Hauteur utile', $depth::FORMULA, result: $depth->effectiveDepth),
        ]);
    }

    /** @param array<string, mixed> $flexure */
    private function materials(BeamCalculationSetup $input, array $flexure, BeamReinforcementCandidateRecalculationResult $selected): CalculationNoteSection
    {
        $strengths = $flexure['designStrengths'];
        $minimum = $selected->minimumArea;
        $domain = $selected->flexuralDomain;

        return new CalculationNoteSection('materials', 'Matériaux', [
            $this->value('concreteClass', 'Classe de béton', $input->materials?->concreteClass->value),
            $this->value('steelGrade', 'Nuance d’acier', $input->materials?->steelGrade->value),
            $this->value('exposureClass', 'Classe d’exposition', $input->materials?->exposureClasses[0]->value),
            $this->value('fck', 'Résistance caractéristique béton fck', $strengths->concrete->fck, 'MPa'),
            $this->value('fctm', 'Résistance moyenne en traction fctm', $minimum->meanTensileConcreteStrength, 'MPa'),
            $this->value('fcd', 'Résistance de calcul béton fcd', $strengths->concrete->fcd, 'MPa'),
            $this->value('fyk', 'Limite caractéristique acier fyk', $strengths->steel->fyk, 'MPa'),
            $this->value('fyd', 'Résistance de calcul acier fyd', $strengths->steel->fyd, 'MPa'),
            $this->value('es', 'Module d’Young acier Es', $domain->steelElasticModulus, 'MPa'),
        ]);
    }

    private function loads(BeamCalculationSetup $input, BeamCharacteristicActionsResult $actions): CalculationNoteSection
    {
        return new CalculationNoteSection('loads', 'Charges caractéristiques', [
            $this->value('includeSelfWeight', 'Poids propre inclus', $input->permanentLoads?->includeSelfWeight),
            $this->value('gkSelf', 'Poids propre Gk,self', $actions->permanent->selfWeight->characteristicLineLoad, 'kN/m'),
            $this->value('gkAdditional', 'Charge permanente ajoutée Gk,additional', $actions->permanent->additionalPermanentLoad, 'kN/m'),
            $this->value('gkTotal', 'Charge permanente totale Gk,total', $actions->permanent->totalPermanentLoad, 'kN/m'),
            $this->value('qk', 'Charge variable Qk', $actions->variable->characteristicLoad, 'kN/m'),
            $this->value('variableActionCategory', 'Catégorie d’action variable', $actions->variable->category->value),
        ], [
            new CalculationNoteStep('Poids propre', $actions->permanent->selfWeight::FORMULA, result: $actions->permanent->selfWeight->characteristicLineLoad),
            new CalculationNoteStep('Actions permanentes', $actions->permanent::TOTAL_FORMULA, result: $actions->permanent->totalPermanentLoad),
        ]);
    }

    /** @param array<string, mixed> $combinations */
    private function combinations(array $combinations): CalculationNoteSection
    {
        $ultimate = $combinations['ultimate'];
        $serviceability = $combinations['serviceability'];

        return new CalculationNoteSection('combinations', 'Combinaisons', [
            $this->value('uls', 'Charge linéaire ELU wEd', $ultimate->designLineLoad, 'kN/m'),
            $this->value('slsCharacteristic', 'Charge ELS caractéristique', $serviceability->characteristic->resultingLineLoad, 'kN/m'),
            $this->value('psiCharacteristic', 'Facteur ψ ELS caractéristique', $serviceability->characteristic->variableFactor),
            $this->value('slsFrequent', 'Charge ELS fréquente', $serviceability->frequent->resultingLineLoad, 'kN/m'),
            $this->value('psiFrequent', 'Facteur ψ ELS fréquente', $serviceability->frequent->variableFactor),
            $this->value('slsQuasiPermanent', 'Charge ELS quasi-permanente', $serviceability->quasiPermanent->resultingLineLoad, 'kN/m'),
            $this->value('psiQuasiPermanent', 'Facteur ψ ELS quasi-permanente', $serviceability->quasiPermanent->variableFactor),
        ], [
            new CalculationNoteStep('Combinaison fondamentale ELU', $ultimate::FORMULA, result: $ultimate->designLineLoad),
            new CalculationNoteStep('Combinaison ELS caractéristique', $serviceability->characteristic->formula, result: $serviceability->characteristic->resultingLineLoad),
            new CalculationNoteStep('Combinaison ELS fréquente', $serviceability->frequent->formula, result: $serviceability->frequent->resultingLineLoad),
            new CalculationNoteStep('Combinaison ELS quasi-permanente', $serviceability->quasiPermanent->formula, result: $serviceability->quasiPermanent->resultingLineLoad),
        ]);
    }

    /** @param array<string, mixed> $internalForces */
    private function internalForces(array $internalForces, BeamSubmodule $submodule): CalculationNoteSection
    {
        $moments = $internalForces['bendingMoments'];
        $shears = $internalForces['shearForces'];
        $atFixedEnd = $submodule === BeamSubmodule::BEAM_CANTILEVER_RECTANGULAR;
        $momentLabel = $atFixedEnd ? 'Moment ELU MEd à l’encastrement' : 'Moment ELU MEd';
        $shearLabel = $atFixedEnd ? 'Effort tranchant ELU VEd à l’encastrement' : 'Effort tranchant ELU VEd';

        return new CalculationNoteSection('internalForces', 'Sollicitations', [
            $this->value('med', $momentLabel, $moments->ultimate->maximumMoment, 'kN·m'),
            $this->value('ved', $shearLabel, $shears->ultimate->maximumAbsoluteShear, 'kN'),
            $this->value('mCharacteristic', 'Moment ELS caractéristique', $moments->characteristic->maximumMoment, 'kN·m'),
            $this->value('mFrequent', 'Moment ELS fréquent', $moments->frequent->maximumMoment, 'kN·m'),
            $this->value('mQuasiPermanent', 'Moment ELS quasi-permanent', $moments->quasiPermanent->maximumMoment, 'kN·m'),
            $this->value('vCharacteristic', 'Effort tranchant ELS caractéristique', $shears->characteristic->maximumAbsoluteShear, 'kN'),
            $this->value('vFrequent', 'Effort tranchant ELS fréquent', $shears->frequent->maximumAbsoluteShear, 'kN'),
            $this->value('vQuasiPermanent', 'Effort tranchant ELS quasi-permanent', $shears->quasiPermanent->maximumAbsoluteShear, 'kN'),
        ], [
            new CalculationNoteStep($atFixedEnd ? 'Moment fléchissant à l’encastrement' : 'Moment fléchissant', $moments->ultimate->formula, result: $moments->ultimate->maximumMoment),
            new CalculationNoteStep($atFixedEnd ? 'Effort tranchant à l’encastrement' : 'Effort tranchant', $shears->ultimate->formula, result: $shears->ultimate->maximumAbsoluteShear),
        ]);
    }

    /** @param array<string, mixed> $flexure @param array<string, mixed> $shear @param array<string, mixed> $serviceability @return list<CalculationNoteVerification> */
    private function verifications(BeamCalculationResponse $result, array $flexure, array $shear, array $serviceability): array
    {
        $selected = $result->details->reinforcement['selectedCandidate'];
        $stress = $serviceability['stress'];
        $crack = $serviceability['crack'];
        $deflection = $serviceability['deflection'];

        return [
            $this->verification($result->verifications->flexureVerification, 'Flexion', 'mm²', [
                $this->value('med', 'Moment de calcul MEd', $selected->requiredArea->designMoment, 'kN·m'),
                $this->value('d', 'Hauteur utile finale d', $selected->effectiveDepth->effectiveDepth, 'mm'),
                $this->value('mu', 'Moment réduit μEd', $selected->reducedMoment->reducedDesignMoment),
                $this->value('xi', 'Rapport d’axe neutre ξ', $selected->neutralAxis->neutralAxisRatio),
                $this->value('x', 'Axe neutre x', $selected->neutralAxis->neutralAxisDepth, 'mm'),
                $this->value('z', 'Bras de levier z', $selected->leverArm->leverArm, 'mm'),
                $this->value('asRequired', 'Armature requise As,req', $selected->requiredArea->requiredReinforcementArea, 'mm²'),
                $this->value('asMinimum', 'Armature minimale As,min', $selected->minimumArea->requiredMinimum, 'mm²'),
                $this->value('asProvided', 'Armature fournie As,prov', $selected->providedArea, 'mm²'),
            ]),
            $this->verification($result->verifications->shearVerification, 'Cisaillement', 'kN', $this->shearDetails($shear)),
            $this->verification($result->verifications->stressVerification, 'ELS — contraintes', 'MPa', [
                $this->value('sectionModel', 'Modèle de section', $stress->sectionModel),
                $this->value('governingCheck', 'Contrôle gouvernant', $stress->governingStressCheck),
                $this->value('concreteCharacteristic', 'Contrainte béton ELS caractéristique', $stress->concreteCharacteristic->stress, 'MPa'),
                $this->value('steelCharacteristic', 'Contrainte acier ELS caractéristique', $stress->steelCharacteristic->stress, 'MPa'),
            ]),
            $this->verification($result->verifications->crackVerification, 'ELS — fissuration', 'mm', $this->crackDetails($crack)),
            $this->verification($result->verifications->deflectionVerification, 'ELS — flèche simplifiée', null, [
                $this->value('method', 'Méthode', $deflection->method->value),
                $this->value('actualSpanDepthRatio', 'Rapport l/d réel', $deflection->actualSpanDepthRatio),
                $this->value('allowableSpanDepthRatio', 'Rapport l/d admissible', $deflection->allowableSpanDepthRatio),
            ]),
        ];
    }

    /** @return list<CalculationNoteReinforcement> */
    private function reinforcement(BeamCalculationResponse $result, BeamReinforcementCandidateRecalculationResult $selected, ?BeamStirrupProposalCandidate $stirrup): array
    {
        $summary = $result->summary->longitudinalReinforcement;
        $longitudinalLabel = $summary->source === BeamLongitudinalReinforcementSource::PROVIDED
            ? 'Ferraillage longitudinal fourni'
            : 'Ferraillage longitudinal proposé';
        $position = $summary->position;
        $positionLabel = $position === BeamReinforcementPosition::TOP ? 'Partie supérieure' : 'Partie inférieure';
        $reinforcement = [new CalculationNoteReinforcement(
            'LONGITUDINAL',
            $longitudinalLabel.' — '.$positionLabel,
            null,
            $summary->barDiameter,
            $summary->barCount,
            providedArea: $summary->providedArea,
            requiredArea: $selected->requiredArea->requiredReinforcementArea,
            unit: 'mm²',
            details: [$this->value('position', 'Position des armatures principales', $position->value)],
        )];

        if ($stirrup !== null) {
            $reinforcement[] = new CalculationNoteReinforcement(
                'STIRRUPS',
                'Étriers proposés',
                null,
                $stirrup->barDiameter,
                $stirrup->legCount,
                $stirrup->spacing,
                $stirrup->providedAreaPerLength,
                $stirrup->targetAreaPerLength,
                'mm²/mm',
            );
        }

        return $reinforcement;
    }

    /** @param list<CalculationNoteValue> $details */
    private function verification(BeamVerificationComponent $component, string $label, ?string $unit, array $details): CalculationNoteVerification
    {
        return new CalculationNoteVerification(
            $component->identifier,
            $label,
            $component->status,
            $component->utilization,
            $component->governingValue,
            $component->limitValue,
            $unit,
            $component->method,
            $details,
        );
    }

    /** @return list<CalculationNoteValue> */
    private function summary(BeamCalculationResponse $result): array
    {
        $atFixedEnd = $result->summary->submodule === BeamSubmodule::BEAM_CANTILEVER_RECTANGULAR;

        return [
            $this->value('submodule', 'Type de poutre', $result->summary->submodule->value),
            $this->value('supportSystem', 'Système statique', $result->summary->supportSystem->value),
            $this->value('utilization', 'Taux d’utilisation gouvernant', $result->summary->utilization),
            $this->value('governingVerification', 'Vérification gouvernante', $result->summary->governingVerificationType),
            $this->value('med', $atFixedEnd ? 'Moment ELU MEd à l’encastrement' : 'Moment ELU MEd', $result->summary->designBendingMoment, 'kN·m'),
            $this->value('ved', $atFixedEnd ? 'Effort tranchant ELU VEd à l’encastrement' : 'Effort tranchant ELU VEd', $result->summary->designShearForce, 'kN'),
            $this->value('effectiveDepth', 'Hauteur utile finale d', $result->summary->effectiveDepth, 'mm'),
            $this->value('asRequired', 'Armature requise As,req', $result->summary->requiredLongitudinalReinforcementArea, 'mm²'),
            $this->value('asProvided', 'Armature fournie As,prov', $result->summary->longitudinalReinforcement->providedArea, 'mm²'),
        ];
    }

    private function value(string $key, string $label, string|int|float|bool|null $value, ?string $unit = null): CalculationNoteValue
    {
        return new CalculationNoteValue($key, $label, $value, $unit, CalculationNoteDisplayValue::french($value));
    }

    /** @param array<string, mixed> $shear @return list<CalculationNoteValue> */
    private function shearDetails(array $shear): array
    {
        if (isset($shear['scope'])) {
            $scope = $shear['scope'];

            return [
                $this->value('criticalSectionLocation', 'Section critique', $scope->criticalSectionLocation->value),
                $this->value('criticalSectionPosition', 'Distance à l’encastrement', $scope->criticalSectionPosition, 'mm'),
                $this->value('fixedEndVed', 'Effort tranchant VEd à l’encastrement', $scope->fixedEndDesignShearForce->maximumAbsoluteShear, 'kN'),
                $this->value('controlVed', 'Effort tranchant VEd à la section critique', $scope->criticalSectionDesignShearForce->maximumAbsoluteShear, 'kN'),
                $this->value('controlFormula', 'Formule de VEd à la section critique', $scope->criticalSectionFormula),
                $this->value('vrdc', 'Résistance béton VRd,c', $shear['concreteResistance']->concreteShearResistance, 'kN'),
                $this->value('vrdmax', 'Résistance maximale VRd,max à l’encastrement', $shear['maximumResistance']->maximumShearResistance, 'kN'),
                $this->value('stirrup', 'Étrier recommandé', $shear['recommendedStirrup']?->providedShearResistance, 'kN'),
            ];
        }

        return [
            $this->value('ved', 'Effort tranchant VEd', $shear['concreteResistance']->designShearForce, 'kN'),
            $this->value('vrdc', 'Résistance béton VRd,c', $shear['concreteResistance']->concreteShearResistance, 'kN'),
            $this->value('aswPerLengthRequired', 'Asw/s requis', $shear['reinforcementDesign']->requiredShearReinforcementPerLength, 'mm²/mm'),
            $this->value('aswPerLengthMinimum', 'Asw/s minimal', $shear['reinforcementDesign']->minimumShearReinforcementPerLength, 'mm²/mm'),
            $this->value('vrds', 'Résistance des étriers VRd,s', $shear['recommendedStirrup']?->providedShearResistance, 'kN'),
            $this->value('vrdmax', 'Résistance maximale VRd,max', $shear['maximumResistance']->maximumShearResistance, 'kN'),
        ];
    }

    /** @return list<CalculationNoteValue> */
    private function crackDetails(object $crack): array
    {
        return [
            $this->value('serviceMoment', 'Moment ELS quasi-permanent utilisé', $crack->serviceMoment, 'kN·m'),
            $this->value('tensionFace', 'Face tendue', $crack->tensionFace->value),
            $this->value('position', 'Position des armatures principales', $crack->longitudinalReinforcementPosition->value),
            $this->value('asProvided', 'Armature fournie As,prov', $crack->providedLongitudinalReinforcementArea, 'mm²'),
            $this->value('barDiameter', 'Diamètre des armatures principales', $crack->barDiameter, 'mm'),
            $this->value('transverseBarDiameter', 'Diamètre transversal utilisé pour la géométrie', $crack->transverseBarDiameter, 'mm'),
            $this->value('coverToLongitudinalBar', 'Enrobage jusqu’aux armatures longitudinales', $crack->coverToLongitudinalBar, 'mm'),
            $this->value('wk', 'Ouverture de fissure wk', $crack->crackWidth, 'mm'),
            $this->value('wkMax', 'Limite wk,max', $crack->crackWidthLimit, 'mm'),
            $this->value('loadCombination', 'Combinaison', $crack->loadCombination),
            $this->value('method', 'Méthode', $crack->sectionModel),
            $this->value('srMax', 'Espacement maximal des fissures sr,max', $crack->maximumCrackSpacing, 'mm'),
        ];
    }

    private function submoduleLabel(BeamSubmodule $submodule): string
    {
        return $submodule === BeamSubmodule::BEAM_CANTILEVER_RECTANGULAR
            ? 'Poutre rectangulaire en console'
            : 'Poutre rectangulaire simplement appuyée';
    }

    private function structuralHypotheses(BeamSubmodule $submodule): string
    {
        return $submodule === BeamSubmodule::BEAM_CANTILEVER_RECTANGULAR
            ? 'Section rectangulaire constante ; encastrement à une extrémité ; extrémité opposée libre ; charge uniformément répartie.'
            : 'Section rectangulaire constante ; poutre simplement appuyée ; charge uniformément répartie.';
    }
}
