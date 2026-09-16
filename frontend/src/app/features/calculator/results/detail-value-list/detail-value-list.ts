import { ChangeDetectionStrategy, Component, computed, input } from '@angular/core';

interface DetailRow {
  label: string;
  value: string;
  unit: string | null;
  children?: DetailRow[];
}

const labels: Readonly<Record<string, string>> = {
  elementType: 'Type d’élément', material: 'Matériau', crossSectionType: 'Type de section', structuralSystem: 'Système statique',
  calculationMode: 'Mode de calcul', designSituation: 'Situation de projet', designCodeProfile: 'Profil normatif',
  concreteClass: 'Classe de béton', steelGrade: 'Nuance d’acier', exposureClass: 'Classe d’exposition', exposureClasses: 'Classes d’exposition',
  effectiveSpan: 'Portée effective', width: 'Largeur', height: 'Hauteur', nominalCover: 'Enrobage nominal', selfWeightIncluded: 'Poids propre pris en compte',
  cNom: 'Enrobage nominal calculé', cMin: 'Enrobage minimal', cMinDurability: 'Enrobage minimal de durabilité',
  ultimate: 'ELU', characteristic: 'ELS caractéristique', frequent: 'ELS fréquente', quasiPermanent: 'ELS quasi-permanente',
  GkTotal: 'Charge permanente totale', Qk: 'Charge variable caractéristique', gammaG: 'Coefficient permanent', gammaQ: 'Coefficient variable',
  wEd: 'Charge de calcul ELU', wCharacteristic: 'Charge ELS caractéristique', wFrequent: 'Charge ELS fréquente', wQuasiPermanent: 'Charge ELS quasi-permanente',
  designBendingMoment: 'Moment de calcul ELU', MEd: 'Moment de calcul ELU', VEd: 'Effort tranchant de calcul',
  MCharacteristic: 'Moment ELS caractéristique', VCharacteristic: 'Effort tranchant ELS caractéristique', MFrequent: 'Moment ELS fréquent', VFrequent: 'Effort tranchant ELS fréquent', MQuasiPermanent: 'Moment ELS quasi-permanent', VQuasiPermanent: 'Effort tranchant ELS quasi-permanent',
  effectiveDepth: 'Hauteur utile', requiredArea: 'Armatures requises', minimumArea: 'Armatures minimales', targetArea: 'Armatures cibles', providedArea: 'Armatures fournies',
  longitudinalReinforcement: 'Ferraillage longitudinal', barCount: 'Nombre de barres', barDiameter: 'Diamètre des barres', source: 'Origine du ferraillage',
  concreteResistance: 'Résistance béton', maximumResistance: 'Résistance maximale', stirrupProposal: 'Proposition d’étriers',
  stress: 'Contraintes', crack: 'Fissuration', deflection: 'Déformation', method: 'Méthode', warnings: 'Informations et limites',
  utilization: 'Taux d’utilisation', status: 'Statut', crackWidth: 'Ouverture de fissure', wmax: 'Ouverture de fissure limite', actualSpanDepthRatio: 'Rapport portée / hauteur utile', allowableSpanDepthRatio: 'Rapport admissible portée / hauteur utile',
  coverMode: 'Mode d’enrobage', initialStructuralClass: 'Classe structurale initiale', structuralClassModifiers: 'Modificateurs de classe structurale', finalStructuralClass: 'Classe structurale finale', exposureResults: 'Résultats par classe d’exposition', governingExposureClass: 'Classe d’exposition gouvernante', cMinBond: 'Enrobage minimal d’adhérence', deltaCDurGamma: 'Correction γdur', deltaCDurSt: 'Correction acier inoxydable', deltaCDurAdd: 'Correction additionnelle', correctedCMinDurability: 'Enrobage de durabilité corrigé', minimumAbsoluteCover: 'Enrobage minimal absolu', governingCriterion: 'Critère gouvernant', deltaCDev: 'Marge d’exécution', unit: 'Unité', rule: 'Règle appliquée', value: 'Valeur', modifiers: 'Modificateurs', structuralClass: 'Classe structurale', minimumDurabilityCover: 'Enrobage minimal de durabilité',
  recommendedCandidate: 'Étrier recommandé', acceptedCandidates: 'Candidats acceptés', rejectedCandidates: 'Candidats rejetés', maximumShearResistanceValid: 'Résistance maximale au cisaillement vérifiée', barArea: 'Aire d’une branche', legCount: 'Nombre de branches', spacing: 'Espacement des étriers', providedAreaPerLength: 'Aire fournie par mètre', targetAreaPerLength: 'Aire requise par mètre', reinforcementExcess: 'Marge d’armature', targetUtilization: 'Taux d’utilisation cible', requiredMaximumSpacing: 'Espacement maximal requis', maximumLongitudinalSpacing: 'Espacement longitudinal maximal', transverseLegSpacing: 'Espacement entre branches', maximumTransverseLegSpacing: 'Espacement maximal entre branches', providedShearResistance: 'Résistance au cisaillement fournie', accepted: 'Accepté', rejectionReasons: 'Motifs de rejet',
  cover: 'Enrobage', stirrups: 'Étriers', configuration: 'Configuration', geometry: 'Géométrie', materials: 'Matériaux', permanentLoads: 'Charges permanentes', variableLoad: 'Charge d’exploitation', selfWeight: 'Poids propre', sectionArea: 'Aire de section', unitWeight: 'Poids volumique', characteristicLineLoad: 'Charge linéaire caractéristique', included: 'Pris en compte',
  characteristicActions: 'Actions caractéristiques', permanent: 'Actions permanentes', variable: 'Action variable', totalPermanentLoad: 'Charge permanente totale', additionalPermanentLoad: 'Charge permanente additionnelle', characteristicLoad: 'Charge caractéristique', designLineLoad: 'Charge de calcul', designMoment: 'Moment de calcul', maximumMoment: 'Moment maximal', maximumAbsoluteShear: 'Effort tranchant maximal',
  designStrengths: 'Résistances de calcul', concrete: 'Béton', steel: 'Acier', fck: 'Résistance caractéristique du béton', fcm: 'Résistance moyenne du béton', fctm: 'Résistance moyenne en traction', Ecm: 'Module d’élasticité du béton', fyk: 'Limite d’élasticité caractéristique', Es: 'Module d’élasticité de l’acier', fcd: 'Résistance de calcul du béton', fyd: 'Résistance de calcul de l’acier',
  reducedMoment: 'Moment réduit', neutralAxis: 'Axe neutre', leverArm: 'Bras de levier', flexuralDomain: 'Domaine de flexion', initialEffectiveDepth: 'Hauteur utile initiale', maximumShearResistance: 'Résistance maximale au cisaillement', reinforcementDesign: 'Dimensionnement des étriers',
};

const units: Readonly<Record<string, string>> = {
  designBendingMoment: 'kN·m', MEd: 'kN·m', MCharacteristic: 'kN·m', MFrequent: 'kN·m', MQuasiPermanent: 'kN·m',
  VEd: 'kN', VCharacteristic: 'kN', VFrequent: 'kN', VQuasiPermanent: 'kN',
  GkTotal: 'kN/m', Qk: 'kN/m', wEd: 'kN/m', wCharacteristic: 'kN/m', wFrequent: 'kN/m', wQuasiPermanent: 'kN/m',
  effectiveSpan: 'mm', width: 'mm', height: 'mm', nominalCover: 'mm', cNom: 'mm', cMin: 'mm', cMinDurability: 'mm', effectiveDepth: 'mm', barDiameter: 'mm',
  requiredArea: 'mm²', minimumArea: 'mm²', targetArea: 'mm²', providedArea: 'mm²', crackWidth: 'mm', wmax: 'mm',
  fcd: 'MPa', fyd: 'MPa', fywd: 'MPa', sigmaCp: 'MPa',
  VRdc: 'kN', VRds: 'kN', VRdmax: 'kN', concreteResistance: 'kN', maximumResistance: 'kN',
  cMinBond: 'mm', correctedCMinDurability: 'mm', minimumAbsoluteCover: 'mm', deltaCDev: 'mm', minimumDurabilityCover: 'mm',
  barArea: 'mm²', providedAreaPerLength: 'mm²/m', targetAreaPerLength: 'mm²/m', reinforcementExcess: 'mm²/m', spacing: 'mm', requiredMaximumSpacing: 'mm', maximumLongitudinalSpacing: 'mm', transverseLegSpacing: 'mm', maximumTransverseLegSpacing: 'mm', providedShearResistance: 'kN',
};

/** Liste de données backend avec libellés et unités de présentation uniquement. */
@Component({
  selector: 'app-detail-value-list',
  templateUrl: './detail-value-list.html',
  styleUrl: './detail-value-list.css',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class DetailValueList {
  readonly values = input.required<Record<string, unknown>>();

  readonly rows = computed(() => this.flatten(this.values()));

  private flatten(values: Record<string, unknown>, prefix = ''): DetailRow[] {
    const reinforcementLayout = typeof values['barCount'] === 'number' && typeof values['barDiameter'] === 'number'
      ? [{ label: `${prefix}Disposition`, value: `${values['barCount']} HA${values['barDiameter']}`, unit: null }]
      : [];

    return [...reinforcementLayout, ...Object.entries(values).flatMap(([key, value]) => {
      if (key === 'deflectionMm') {
        return [];
      }

      const label = `${prefix}${this.labelFor(key)}`;

      if (this.isRecord(value)) {
        return this.flatten(value, `${label} · `);
      }

      if (Array.isArray(value)) {
        const records = value.filter((item): item is Record<string, unknown> => this.isRecord(item));

        if (records.length > 0) {
          return [{
            label,
            value: '',
            unit: null,
            children: records.flatMap((item, index) => this.flatten(item, `${label} ${index + 1} · `)),
          }];
        }
      }

      return [{ label, value: this.formatValue(value, key), unit: value === null ? null : units[key] ?? null }];
    })];
  }

  private formatValue(value: unknown, key: string): string {
    if (value === null || value === undefined) return '—';
    if (typeof value === 'boolean') return value ? 'Oui' : 'Non';
    if (typeof value === 'number') {
      if (key.toLowerCase().includes('utilization')) return `${Math.round(value * 100)} %`;
      return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 2 }).format(value);
    }
    if (Array.isArray(value)) return value.length === 0 ? 'Aucun' : value.map((item) => this.formatValue(item, key)).join(', ');
    if (typeof value === 'string') return this.formatStatus(value);
    return String(value);
  }

  private formatStatus(value: string): string {
    return ({ COMPLIANT: 'Conforme', NOT_COMPLIANT: 'Non conforme', NOT_CHECKED: 'Non vérifié', NOT_APPLICABLE: 'Non applicable', CALCULATION_METHOD_NOT_SUPPORTED: 'Méthode non prise en charge', PROPOSED: 'Proposé', PROVIDED: 'Fourni', SIMPLIFIED_SPAN_DEPTH: 'Contrôle simplifié portée / hauteur utile', CANDIDATES_AVAILABLE: 'Candidats disponibles', MINIMUM_10_MM: 'Enrobage minimal de 10 mm', INSUFFICIENT_HORIZONTAL_SPACE: 'Largeur disponible insuffisante', LONGITUDINAL_SPACING_EXCEEDED: 'Espacement longitudinal insuffisant', TRANSVERSE_LEG_SPACING_EXCEEDED: 'Espacement entre branches insuffisant' } as Record<string, string>)[value] ?? value;
  }

  private labelFor(key: string): string {
    if (labels[key] !== undefined) {
      return labels[key];
    }

    const translatedWords: Record<string, string> = {
      initial: 'initial', final: 'final', maximum: 'maximal', minimum: 'minimal', required: 'requis', provided: 'fourni', design: 'de calcul', characteristic: 'caractéristique', serviceability: 'service', effective: 'utile', depth: 'hauteur', area: 'aire', resistance: 'résistance', reinforcement: 'armature', candidate: 'candidat', candidates: 'candidats', calculation: 'calcul', result: 'résultat', load: 'charge', force: 'effort', moment: 'moment', shear: 'cisaillement', concrete: 'béton', steel: 'acier', exposure: 'exposition', class: 'classe', strength: 'résistance', ratio: 'rapport', factor: 'coefficient', limit: 'limite', width: 'largeur', height: 'hauteur', spacing: 'espacement', diameter: 'diamètre', count: 'nombre', valid: 'valide', governing: 'gouvernant', value: 'valeur', status: 'statut', reason: 'motif', warning: 'avertissement', warnings: 'avertissements', detail: 'détail', details: 'détails', unit: 'unité', mode: 'mode', type: 'type', support: 'appui', system: 'système', profile: 'profil', code: 'normatif', situation: 'situation', structural: 'structural', modifier: 'modificateur', modifiers: 'modificateurs', durability: 'durabilité', bond: 'adhérence', corrected: 'corrigé', absolute: 'absolu', delta: 'correction', recommended: 'recommandé', transverse: 'transversal', longitudinal: 'longitudinal', leg: 'branche', excess: 'excédent', accepted: 'accepté', rejected: 'rejeté', available: 'disponible', remaining: 'restant', target: 'cible', utilization: 'utilisation', crack: 'fissuration', deflection: 'déformation', stress: 'contrainte', axial: 'axial', normal: 'normal', compression: 'compression', tension: 'traction', nominal: 'nominal', aggregate: 'granulat', mean: 'moyenne', partial: 'partiel', coefficient: 'coefficient', formula: 'formule', step: 'étape', method: 'méthode', verification: 'vérification', overall: 'global', uls: 'ELU', sls: 'ELS',
    };
    const words = key.replace(/([a-z0-9])([A-Z])/g, '$1 $2').replace(/([A-Z])([A-Z][a-z])/g, '$1 $2').split(' ');
    const label = words.map((word) => translatedWords[word.toLowerCase()] ?? 'donnée').join(' ');

    return `Paramètre : ${label}`;
  }

  private isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value);
  }
}
