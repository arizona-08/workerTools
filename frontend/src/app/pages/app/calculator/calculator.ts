import { Component, ViewChild, inject, signal } from '@angular/core';
import { ModuleSelector } from '../../../components/module-selector/module-selector';
import { BeamForm } from '../../../features/calculator/forms/beam-form/beam-form';
import { SlabForm } from '../../../features/calculator/forms/slab-form/slab-form';
import { CalculatorModule, ModuleSelectorFieldType } from '../../../../types';
import { BeamCalculationResponse, BeamCalculationService } from '../../../features/calculator/beam-calculation.service';
import { ResultComplianceIndicator } from '../../../features/calculator/results/result-compliance-indicator/result-compliance-indicator';
import { ResultSummaryCards } from '../../../features/calculator/results/result-summary-cards/result-summary-cards';
import { ResultSummaryMessage } from '../../../features/calculator/results/result-summary-message/result-summary-message';
import { CalculationDetailsAccordion } from '../../../features/calculator/results/calculation-details/calculation-details-accordion';

@Component({
  selector: 'app-calculator',
  imports: [ModuleSelector, BeamForm, SlabForm, ResultComplianceIndicator, ResultSummaryCards, ResultSummaryMessage, CalculationDetailsAccordion],
  templateUrl: './calculator.html',
  styleUrl: './calculator.css',
})
export class Calculator {
  private readonly calculations = inject(BeamCalculationService);
  @ViewChild(BeamForm) private beamForm?: BeamForm;
  readonly modules: readonly CalculatorModule[] = [
    { id: 'Poutre', label: 'Poutres', description: 'Élément linéaire en béton armé' },
    { id: 'Dalle', label: 'Dalles', description: 'Élément surfacique en béton armé' },
  ];

  selectedModule = signal<ModuleSelectorFieldType>('Poutre');
  readonly calculationResult = signal<BeamCalculationResponse | null>(null);
  readonly calculationError = signal<string | null>(null);
  readonly isCalculating = signal(false);

  handleModuleChange(moduleType: ModuleSelectorFieldType): void {
    this.selectedModule.set(moduleType);
  }

  selectedModuleDescription(): string {
    return this.modules.find((module) => module.id === this.selectedModule())?.description ?? '';
  }

  calculateBeam(): void {
    const payload = this.beamForm?.requestPayload();
    if (payload === null || payload === undefined) {
      this.calculationError.set('Complétez les champs obligatoires avant de lancer le calcul.');
      return;
    }

    this.isCalculating.set(true);
    this.calculationError.set(null);
    this.calculations.calculate(payload).subscribe({
      next: (result) => {
        this.calculationResult.set(result);
        this.isCalculating.set(false);
      },
      error: (error: { error?: { message?: string } }) => {
        this.calculationError.set(error.error?.message ?? 'Le calcul n’a pas pu être exécuté. Réessayez après avoir vérifié les données.');
        this.isCalculating.set(false);
      },
    });
  }
}
