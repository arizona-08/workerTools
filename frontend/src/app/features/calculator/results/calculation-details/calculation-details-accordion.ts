import { ChangeDetectionStrategy, Component, computed, input } from '@angular/core';
import { LucideInfo } from '@lucide/angular';

import { DetailValueList } from '../detail-value-list/detail-value-list';
import { CalculationFormulaListComponent } from '../calculation-formula-list/calculation-formula-list';
import { ResultAccordionItem } from '../result-accordion-item/result-accordion-item';
import { BeamCalculationDetails } from './beam-calculation-details';
import { ReinforcementDetails } from './reinforcement-details';

/** Présente les sections déjà structurées par BEAM-RESULT-04, sans les recalculer. */
@Component({
  selector: 'app-calculation-details-accordion',
  imports: [CalculationFormulaListComponent, DetailValueList, LucideInfo, ReinforcementDetails, ResultAccordionItem],
  templateUrl: './calculation-details-accordion.html',
  styleUrl: './calculation-details-accordion.css',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class CalculationDetailsAccordion {
  readonly details = input.required<BeamCalculationDetails>();

  readonly stress = computed(() => this.record(this.details().serviceability['stress']));
  readonly crack = computed(() => this.record(this.details().serviceability['crack']));
  readonly deflection = computed(() => this.record(this.details().serviceability['deflection']));

  readonly usesSimplifiedSpanDepthMethod = computed(() => this.deflection()['method'] === 'SIMPLIFIED_SPAN_DEPTH');
  readonly actionsFormulaSteps = computed(() => this.details().calculationSteps?.actions ?? []);
  readonly flexureFormulaSteps = computed(() => this.details().calculationSteps?.flexure ?? []);
  readonly reinforcementFormulaSteps = computed(() => this.details().calculationSteps?.reinforcement ?? []);
  readonly shearFormulaSteps = computed(() => this.details().calculationSteps?.shear ?? []);
  readonly serviceabilityFormulaSteps = computed(() => this.details().calculationSteps?.serviceability ?? []);

  private record(value: unknown): Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value) ? value as Record<string, unknown> : {};
  }
}
