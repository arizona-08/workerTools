/**
 * Contrat de présentation commun aux futurs moteurs de calcul.
 * Les valeurs normatives restent calculées et fournies par le backend.
 */
export type CalculationStatus =
  | 'compliant'
  | 'non_compliant'
  | 'warning'
  | 'not_verified'
  | 'not_applicable'
  | 'not_calculated';

export interface CalculationValue {
  label: string;
  value: number | string;
  unit?: string;
}

export interface CalculationVerification {
  id: string;
  label: string;
  status: Exclude<CalculationStatus, 'not_calculated'>;
  utilization?: number;
  message?: string;
}

export interface CalculationDetail {
  id: string;
  title: string;
  entries: CalculationValue[];
}

export interface CalculationResult {
  status: CalculationStatus;
  summary: CalculationValue[];
  verifications: CalculationVerification[];
  details: CalculationDetail[];
  warnings: string[];
}
