import { ChangeDetectionStrategy, Component, computed, input } from '@angular/core';

interface DetailRow {
  label: string;
  value: string;
  unit: string | null;
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
};

const units: Readonly<Record<string, string>> = {
  designBendingMoment: 'kN·m', MEd: 'kN·m', MCharacteristic: 'kN·m', MFrequent: 'kN·m', MQuasiPermanent: 'kN·m',
  VEd: 'kN', VCharacteristic: 'kN', VFrequent: 'kN', VQuasiPermanent: 'kN',
  GkTotal: 'kN/m', Qk: 'kN/m', wEd: 'kN/m', wCharacteristic: 'kN/m', wFrequent: 'kN/m', wQuasiPermanent: 'kN/m',
  effectiveSpan: 'mm', width: 'mm', height: 'mm', nominalCover: 'mm', cNom: 'mm', cMin: 'mm', cMinDurability: 'mm', effectiveDepth: 'mm', barDiameter: 'mm',
  requiredArea: 'mm²', minimumArea: 'mm²', targetArea: 'mm²', providedArea: 'mm²', crackWidth: 'mm', wmax: 'mm',
  fcd: 'MPa', fyd: 'MPa', fywd: 'MPa', sigmaCp: 'MPa',
  VRdc: 'kN', VRds: 'kN', VRdmax: 'kN', concreteResistance: 'kN', maximumResistance: 'kN',
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

      const label = `${prefix}${labels[key] ?? key}`;

      if (this.isRecord(value)) {
        return this.flatten(value, `${label} · `);
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
    if (Array.isArray(value)) return value.join(', ');
    if (typeof value === 'string') return this.formatStatus(value);
    return String(value);
  }

  private formatStatus(value: string): string {
    return ({ COMPLIANT: 'Conforme', NOT_COMPLIANT: 'Non conforme', NOT_CHECKED: 'Non vérifié', NOT_APPLICABLE: 'Non applicable', CALCULATION_METHOD_NOT_SUPPORTED: 'Méthode non prise en charge', PROPOSED: 'Proposé', PROVIDED: 'Fourni', SIMPLIFIED_SPAN_DEPTH: 'Contrôle simplifié portée / hauteur utile' } as Record<string, string>)[value] ?? value;
  }

  private isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value);
  }
}
