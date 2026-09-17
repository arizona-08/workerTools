import { ChangeDetectionStrategy, Component, input } from '@angular/core';

/** Étape pédagogique préparée par le backend ; le frontend ne l'évalue jamais. */
export interface CalculationFormulaStep {
  id?: string;
  name: string;
  symbol?: string | null;
  formula: string | null;
  substitution: string | null;
  result: string | number | null;
  unit?: string | null;
  reference?: string | null;
  warning?: string | null;
  status?: string | null;
}

@Component({
  selector: 'app-calculation-formula-step',
  templateUrl: './calculation-formula-step.html',
  styleUrl: './calculation-formula-step.css',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class CalculationFormulaStepComponent {
  readonly step = input.required<CalculationFormulaStep>();

  resultText(): string {
    const result = this.step().result;

    return typeof result === 'number'
      ? new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 5 }).format(result)
      : result ?? '—';
  }
}
