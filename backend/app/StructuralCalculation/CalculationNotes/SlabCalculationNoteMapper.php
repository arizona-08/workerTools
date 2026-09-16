<?php

namespace App\StructuralCalculation\CalculationNotes;

use App\StructuralCalculation\Beams\BeamVerificationComponent;
use App\StructuralCalculation\Eurocode\Cover\CoverCalculationResult;
use App\StructuralCalculation\Slabs\SlabCalculationInput;
use App\StructuralCalculation\Slabs\SlabCalculationResult;
use App\StructuralCalculation\Slabs\SlabCharacteristicActions;
use App\StructuralCalculation\Slabs\SlabCrackVerificationResult;
use App\StructuralCalculation\Slabs\SlabDeflectionVerificationResult;
use App\StructuralCalculation\Slabs\SlabEffectiveDepthResult;
use App\StructuralCalculation\Slabs\SlabMainReinforcementProposal;
use App\StructuralCalculation\Slabs\SlabSecondaryReinforcementProposal;
use App\StructuralCalculation\Slabs\SlabSecondaryReinforcementResult;
use App\StructuralCalculation\Slabs\SlabStripInternalForces;
use App\StructuralCalculation\Slabs\SlabStripLinearLoads;
use App\StructuralCalculation\Slabs\SlabUlsFlexureResult;
use DateTimeImmutable;

/** Projection sans recalcul du pipeline Dalle vers le contrat PDF commun. */
final class SlabCalculationNoteMapper
{
    public function map(SlabCalculationInput $input, SlabCalculationResult $result, DateTimeImmutable $generatedAt): CalculationNoteDocument
    {
        $details = $result->details;
        $finalFlexure = $details->flexure['final'];
        $main = $details->mainReinforcement['proposal'];
        $secondary = $details->secondaryReinforcement['proposal'];
        $linearLoads = $details->internalForces['linearLoads'];
        $forces = $details->internalForces['internalForces'];

        return new CalculationNoteDocument(
            new CalculationNoteMetadata(
                'Note de calcul — Dalle unidirectionnelle en béton armé',
                $input->configuration->elementType,
                $generatedAt,
                $input->configuration->designCodeProfile->value,
            ),
            $this->assumptions($input, $linearLoads),
            $this->geometry($input, $finalFlexure->cover, $finalFlexure->effectiveDepth, $linearLoads),
            $this->materials($input, $finalFlexure),
            $this->loads($details->characteristicActions),
            $this->combinations($details->combinations, $linearLoads),
            $this->internalForces($forces),
            $this->verifications($result, $finalFlexure, $main->proposal, $secondary, $details->serviceability),
            $this->reinforcement($main->proposal, $secondary->proposal, $finalFlexure, $secondary),
            new CalculationNoteFinalStatus(
                $result->status,
                $result->summary->governingVerificationType,
                $result->summary->utilization,
                $this->summary($result),
            ),
            $details->warnings,
            ['Cette note présente les vérifications réalisées dans le périmètre actuellement supporté par WorkerTools.'],
        );
    }

    private function assumptions(SlabCalculationInput $input, SlabStripLinearLoads $linearLoads): CalculationNoteSection
    {
        return new CalculationNoteSection('assumptions', 'Hypothèses et paramètres', [
            $this->value('slabType', 'Type de dalle', $input->configuration->slabType->value),
            $this->value('spanningSystem', 'Système porteur', $input->configuration->spanningSystem->value),
            $this->value('structuralSystem', 'Système statique', $input->configuration->structuralSystem->value),
            $this->value('loadModel', 'Modèle de charge', $input->configuration->loadModel->value),
            $this->value('materialType', 'Matériau', $input->configuration->materialType->value),
            $this->value('designSituation', 'Situation de calcul', $input->configuration->designSituation->value),
            $this->value('calculationStrip', 'Bande de calcul', $linearLoads->uls->stripWidthMetres, 'm'),
        ]);
    }

    private function geometry(SlabCalculationInput $input, CoverCalculationResult $cover, SlabEffectiveDepthResult $depth, SlabStripLinearLoads $linearLoads): CalculationNoteSection
    {
        return new CalculationNoteSection('geometry', 'Géométrie', [
            $this->value('effectiveSpan', 'Portée effective L', $input->geometry->effectiveSpan, 'mm'),
            $this->value('thickness', 'Épaisseur h', $input->geometry->thickness, 'mm'),
            $this->value('calculationStripWidth', 'Largeur de bande b', $linearLoads->uls->stripWidthMetres, 'm'),
            $this->value('nominalCover', 'Enrobage nominal c_nom', $cover->cNom, $cover->unit),
            $this->value('effectiveDepth', 'Hauteur utile finale d', $depth->effectiveDepth, 'mm'),
        ], [
            new CalculationNoteStep('Hauteur utile', $depth::FORMULA, $depth->substitution, $depth->effectiveDepth),
        ]);
    }

    private function materials(SlabCalculationInput $input, SlabUlsFlexureResult $flexure): CalculationNoteSection
    {
        return new CalculationNoteSection('materials', 'Matériaux', [
            $this->value('concreteClass', 'Classe de béton', $input->materials->concreteClass->value),
            $this->value('steelGrade', 'Nuance d’acier', $input->materials->steelGrade->value),
            $this->value('exposureClass', 'Classe d’exposition', $input->materials->exposureClass->value),
            $this->value('fctm', 'Résistance moyenne en traction fctm', $flexure->meanTensileConcreteStrength, 'MPa'),
            $this->value('fcd', 'Résistance de calcul béton fcd', $flexure->concreteDesignStrength, 'MPa'),
            $this->value('fyk', 'Limite caractéristique acier fyk', $flexure->characteristicSteelStrength, 'MPa'),
            $this->value('fyd', 'Résistance de calcul acier fyd', $flexure->steelDesignStrength, 'MPa'),
        ]);
    }

    private function loads(SlabCharacteristicActions $actions): CalculationNoteSection
    {
        return new CalculationNoteSection('loads', 'Charges surfaciques caractéristiques', [
            $this->value('selfWeight', 'Poids propre gk,self', $actions->selfWeight, 'kN/m²'),
            $this->value('finishes', 'Finitions', $actions->finishes, 'kN/m²'),
            $this->value('partitions', 'Cloisons', $actions->partitions, 'kN/m²'),
            $this->value('otherPermanent', 'Autres charges permanentes', $actions->otherPermanent, 'kN/m²'),
            $this->value('gkTotal', 'Charge permanente totale Gk,total', $actions->permanentTotal, 'kN/m²'),
            $this->value('qk', 'Charge variable Qk', $actions->imposedLoad, 'kN/m²'),
        ], [
            new CalculationNoteStep('Poids propre', SlabCharacteristicActions::SELF_WEIGHT_FORMULA, result: $actions->selfWeight),
            new CalculationNoteStep('Charges permanentes', SlabCharacteristicActions::PERMANENT_TOTAL_FORMULA, result: $actions->permanentTotal),
        ]);
    }

    /** @param array<string, mixed> $combinations */
    private function combinations(array $combinations, SlabStripLinearLoads $linearLoads): CalculationNoteSection
    {
        return new CalculationNoteSection('combinations', 'Combinaisons', [
            $this->value('ulsSurface', 'Charge surfacique ELU qEd', $combinations['uls']->value, 'kN/m²'),
            $this->value('ulsStrip', 'Charge de bande ELU wEd', $linearLoads->uls->lineLoad, 'kN/m'),
            $this->value('gammaG', 'Coefficient permanent ELU γG', $combinations['uls']->permanentFactor),
            $this->value('gammaQ', 'Coefficient variable ELU γQ', $combinations['uls']->variableFactor),
            $this->value('slsCharacteristic', 'Charge ELS caractéristique', $combinations['slsCharacteristic']->value, 'kN/m²'),
            $this->value('slsCharacteristicStrip', 'Charge de bande ELS caractéristique', $linearLoads->slsCharacteristic->lineLoad, 'kN/m'),
            $this->value('slsFrequent', 'Charge ELS fréquente', $combinations['slsFrequent']->value, 'kN/m²'),
            $this->value('slsFrequentStrip', 'Charge de bande ELS fréquente', $linearLoads->slsFrequent->lineLoad, 'kN/m'),
            $this->value('psiFrequent', 'Facteur ψ ELS fréquente', $combinations['slsFrequent']->variableFactor),
            $this->value('slsQuasiPermanent', 'Charge ELS quasi-permanente', $combinations['slsQuasiPermanent']->value, 'kN/m²'),
            $this->value('slsQuasiPermanentStrip', 'Charge de bande ELS quasi-permanente', $linearLoads->slsQuasiPermanent->lineLoad, 'kN/m'),
            $this->value('psiQuasiPermanent', 'Facteur ψ ELS quasi-permanente', $combinations['slsQuasiPermanent']->variableFactor),
        ], [
            new CalculationNoteStep('Combinaison ELU', $combinations['uls']->formula, result: $combinations['uls']->value),
            new CalculationNoteStep('Conversion en charge de bande', $linearLoads->uls::FORMULA, $linearLoads->uls->substitution, $linearLoads->uls->lineLoad),
            new CalculationNoteStep('Combinaison ELS caractéristique', $combinations['slsCharacteristic']->formula, result: $combinations['slsCharacteristic']->value),
            new CalculationNoteStep('Combinaison ELS fréquente', $combinations['slsFrequent']->formula, result: $combinations['slsFrequent']->value),
            new CalculationNoteStep('Combinaison ELS quasi-permanente', $combinations['slsQuasiPermanent']->formula, result: $combinations['slsQuasiPermanent']->value),
        ]);
    }

    private function internalForces(SlabStripInternalForces $forces): CalculationNoteSection
    {
        return new CalculationNoteSection('internalForces', 'Sollicitations de la bande de 1 m', [
            $this->value('med', 'Moment ELU MEd', $forces->uls->maximumMoment, 'kN·m'),
            $this->value('ved', 'Effort tranchant ELU VEd', $forces->uls->maximumShear, 'kN'),
            $this->value('mCharacteristic', 'Moment ELS caractéristique', $forces->slsCharacteristic->maximumMoment, 'kN·m'),
            $this->value('mFrequent', 'Moment ELS fréquent', $forces->slsFrequent->maximumMoment, 'kN·m'),
            $this->value('mQuasiPermanent', 'Moment ELS quasi-permanent', $forces->slsQuasiPermanent->maximumMoment, 'kN·m'),
            $this->value('vCharacteristic', 'Effort tranchant ELS caractéristique', $forces->slsCharacteristic->maximumShear, 'kN'),
            $this->value('vFrequent', 'Effort tranchant ELS fréquent', $forces->slsFrequent->maximumShear, 'kN'),
            $this->value('vQuasiPermanent', 'Effort tranchant ELS quasi-permanent', $forces->slsQuasiPermanent->maximumShear, 'kN'),
        ], [
            new CalculationNoteStep('Moment fléchissant', $forces->uls::MOMENT_FORMULA, $forces->uls->momentSubstitution, $forces->uls->maximumMoment),
            new CalculationNoteStep('Effort tranchant', $forces->uls::SHEAR_FORMULA, $forces->uls->shearSubstitution, $forces->uls->maximumShear),
        ]);
    }

    /** @param array<string, mixed> $serviceability @return list<CalculationNoteVerification> */
    private function verifications(SlabCalculationResult $result, SlabUlsFlexureResult $flexure, ?SlabMainReinforcementProposal $main, SlabSecondaryReinforcementResult $secondary, array $serviceability): array
    {
        return array_map(function (BeamVerificationComponent $component) use ($flexure, $main, $secondary, $serviceability): CalculationNoteVerification {
            return $this->verification($component, match ($component->identifier) {
                'FLEXURE' => 'Flexion',
                'MAIN_REINFORCEMENT' => 'Armatures principales',
                'SECONDARY_REINFORCEMENT' => 'Armatures secondaires',
                'CRACK' => 'ELS — fissuration',
                'DEFLECTION' => 'ELS — flèche simplifiée',
                default => $component->identifier,
            }, match ($component->identifier) {
                'FLEXURE' => [
                    $this->value('d', 'Hauteur utile finale d', $flexure->effectiveDepth->effectiveDepth, 'mm'),
                    $this->value('mu', 'Moment réduit μEd', $flexure->reducedMoment),
                    $this->value('xi', 'Rapport d’axe neutre ξ', $flexure->neutralAxisRatio),
                    $this->value('x', 'Axe neutre x', $flexure->neutralAxisDepth, 'mm'),
                    $this->value('z', 'Bras de levier z', $flexure->leverArm, 'mm'),
                    $this->value('asRequired', 'Armature requise As,req', $flexure->requiredReinforcementArea, 'mm²/m'),
                    $this->value('asMinimum', 'Armature minimale As,min', $flexure->minimumReinforcementArea, 'mm²/m'),
                    $this->value('asDesign', 'Armature de dimensionnement As,design', $flexure->designReinforcementArea, 'mm²/m'),
                ],
                'MAIN_REINFORCEMENT' => [
                    $this->value('diameter', 'Diamètre', $main?->barDiameter, 'mm'),
                    $this->value('spacing', 'Espacement', $main?->spacing, 'mm'),
                    $this->value('asProvided', 'Armature fournie As,prov', $main?->providedAreaPerMeter, 'mm²/m'),
                ],
                'SECONDARY_REINFORCEMENT' => [
                    $this->value('ratio', 'Ratio d’armatures secondaires', $secondary->secondaryReinforcementRatio),
                    $this->value('asMinimum', 'Armature secondaire minimale', $secondary->minimumRequiredAreaPerMeter, 'mm²/m'),
                    $this->value('spacingMaximum', 'Espacement maximal', $secondary->maximumAllowedSpacing, 'mm'),
                    $this->value('diameter', 'Diamètre', $secondary->proposal?->barDiameter, 'mm'),
                    $this->value('spacing', 'Espacement', $secondary->proposal?->spacing, 'mm'),
                    $this->value('asProvided', 'Armature secondaire fournie', $secondary->proposal?->providedAreaPerMeter, 'mm²/m'),
                ],
                'CRACK' => $this->crackDetails($serviceability['crack']),
                'DEFLECTION' => $this->deflectionDetails($serviceability['deflection']),
                default => [],
            });
        }, $result->verifications);
    }

    /** @return list<CalculationNoteReinforcement> */
    private function reinforcement(?SlabMainReinforcementProposal $main, ?SlabSecondaryReinforcementProposal $secondary, SlabUlsFlexureResult $flexure, SlabSecondaryReinforcementResult $secondaryResult): array
    {
        $reinforcement = [];
        if ($main !== null) {
            $reinforcement[] = new CalculationNoteReinforcement('MAIN', 'Armatures principales', $main->label(), $main->barDiameter, spacing: $main->spacing, providedArea: $main->providedAreaPerMeter, requiredArea: $flexure->designReinforcementArea, unit: 'mm²/m');
        }
        if ($secondary !== null) {
            $reinforcement[] = new CalculationNoteReinforcement('SECONDARY', 'Armatures secondaires', $secondary->label(), $secondary->barDiameter, spacing: $secondary->spacing, providedArea: $secondary->providedAreaPerMeter, requiredArea: $secondaryResult->minimumRequiredAreaPerMeter, unit: 'mm²/m');
        }

        return $reinforcement;
    }

    /** @return list<CalculationNoteValue> */
    private function crackDetails(SlabCrackVerificationResult $crack): array
    {
        return [
            $this->value('wk', 'Ouverture de fissure wk', $crack->crackWidth, 'mm'),
            $this->value('wkMax', 'Limite wk,max', $crack->crackWidthLimit, 'mm'),
            $this->value('loadCombination', 'Combinaison', $crack->loadCombination),
            $this->value('method', 'Méthode', $crack->method),
            $this->value('xsls', 'Axe neutre ELS x', $crack->crackedNeutralAxisDepth, 'mm'),
            $this->value('steelStress', 'Contrainte acier σs', $crack->steelStress, 'MPa'),
            $this->value('effectiveTensionArea', 'Aire efficace Ac,eff', $crack->effectiveTensionArea, 'mm²'),
            $this->value('effectiveReinforcementRatio', 'Taux efficace ρp,eff', $crack->effectiveReinforcementRatio),
            $this->value('maximumCrackSpacing', 'Espacement maximal sr,max', $crack->maximumCrackSpacing, 'mm'),
        ];
    }

    /** @return list<CalculationNoteValue> */
    private function deflectionDetails(SlabDeflectionVerificationResult $deflection): array
    {
        return [
            $this->value('method', 'Méthode', $deflection->method),
            $this->value('actualSpanDepthRatio', 'Rapport L/d réel', $deflection->actualSpanDepthRatio),
            $this->value('allowableSpanDepthRatio', 'Rapport L/d admissible', $deflection->allowableSpanDepthRatio),
        ];
    }

    /** @param list<CalculationNoteValue> $details */
    private function verification(BeamVerificationComponent $component, string $label, array $details): CalculationNoteVerification
    {
        return new CalculationNoteVerification($component->identifier, $label, $component->status, $component->utilization, $component->governingValue, $component->limitValue, null, $component->method, $details);
    }

    /** @return list<CalculationNoteValue> */
    private function summary(SlabCalculationResult $result): array
    {
        return [
            $this->value('utilization', 'Taux d’utilisation gouvernant', $result->summary->utilization),
            $this->value('governingVerification', 'Vérification gouvernante', $result->summary->governingVerificationType),
            $this->value('med', 'Moment ELU MEd', $result->summary->designBendingMoment, 'kN·m'),
            $this->value('effectiveDepth', 'Hauteur utile finale d', $result->summary->effectiveDepth, 'mm'),
            $this->value('asRequired', 'Armature principale requise As,req', $result->summary->requiredMainReinforcementArea, 'mm²/m'),
            $this->value('asMinimum', 'Armature principale minimale As,min', $result->summary->minimumMainReinforcementArea, 'mm²/m'),
            $this->value('asMainProvided', 'Armature principale fournie', $result->summary->mainReinforcement?->providedAreaPerMeter, 'mm²/m'),
            $this->value('asSecondaryProvided', 'Armature secondaire fournie', $result->summary->secondaryReinforcement?->providedAreaPerMeter, 'mm²/m'),
        ];
    }

    private function value(string $key, string $label, string|int|float|bool|null $value, ?string $unit = null): CalculationNoteValue
    {
        return new CalculationNoteValue($key, $label, $value, $unit, CalculationNoteDisplayValue::french($value));
    }
}
