import { ChangeDetectionStrategy, Component, input } from '@angular/core';

import { CalculationFormulaStep, CalculationFormulaStepComponent } from '../calculation-formula-step/calculation-formula-step';

/** Liste réutilisable d'étapes de formules déjà préparées côté backend. */
@Component({
  selector: 'app-calculation-formula-list',
  imports: [CalculationFormulaStepComponent],
  templateUrl: './calculation-formula-list.html',
  styleUrl: './calculation-formula-list.css',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class CalculationFormulaListComponent {
  readonly steps = input.required<readonly CalculationFormulaStep[]>();
}
