export const UNITS = {
  length: ['mm', 'cm', 'm'],
  force: ['kN'],
  lineLoad: ['kN/m'],
  areaLoad: ['kN/m²'],
  moment: ['kN·m'],
  stress: ['MPa'],
  reinforcementArea: ['mm²', 'cm²', 'mm²/m'],
} as const;

export type UnitCategory = keyof typeof UNITS;
export type Unit = (typeof UNITS)[UnitCategory][number];

/** Unités prévues pour les échanges internes entre le moteur et ses use cases. */
export const INTERNAL_UNITS: Record<UnitCategory, Unit> = {
  length: 'mm',
  force: 'kN',
  lineLoad: 'kN/m',
  areaLoad: 'kN/m²',
  moment: 'kN·m',
  stress: 'MPa',
  reinforcementArea: 'mm²',
};
