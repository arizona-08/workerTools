/** Projection frontend du résumé BEAM-RESULT-03 utilisée par les cartes. */
export interface BeamLongitudinalReinforcementSummary {
  source: 'PROPOSED' | 'PROVIDED';
  barCount: number;
  barDiameter: number;
  providedArea: number;
}

/**
 * Les champs de résultat peuvent être absents lors d'un calcul incomplet.
 * Une valeur manquante est rendue comme telle, jamais remplacée par zéro.
 */
export interface BeamResultSummary {
  utilization?: number | null;
  status?: string;
  governingVerificationType?: string | null;
  designBendingMoment: number | null;
  effectiveDepth: number | null;
  requiredLongitudinalReinforcementArea: number | null;
  longitudinalReinforcement: BeamLongitudinalReinforcementSummary | null;
}
