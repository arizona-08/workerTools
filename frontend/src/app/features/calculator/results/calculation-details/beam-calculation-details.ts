import { CalculationFormulaStep } from '../calculation-formula-step/calculation-formula-step';

/** Contrat frontend de BEAM-RESULT-04 ; chaque section est déjà assemblée côté backend. */
export interface BeamCalculationDetails {
  overallStatus: string;
  ulsStatus: string;
  slsStatus: string;
  governingVerification: Record<string, unknown> | null;
  assumptions: Record<string, unknown>;
  combinations: Record<string, unknown>;
  internalForces: Record<string, unknown>;
  flexure: Record<string, unknown>;
  reinforcement: Record<string, unknown>;
  shear: Record<string, unknown>;
  serviceability: Record<string, unknown>;
  warnings: readonly string[];
  /** Optionnel : non encore fourni par le BEAM-RESULT-04 backend actuel. */
  calculationSteps?: Partial<Record<'actions' | 'flexure' | 'reinforcement' | 'shear' | 'serviceability', readonly CalculationFormulaStep[]>>;
}
