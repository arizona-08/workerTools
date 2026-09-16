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

  warningLabel(warning: string): string {
    return ({
      CANTILEVER_FIXED_END_CRITICAL_SECTION_NOT_MODELLED: 'La section critique de cisaillement à l’encastrement n’est pas encore modélisée.',
      CANTILEVER_CRACK_VERIFICATION_REQUIRES_FIXED_END_STIRRUP_LAYOUT: 'La vérification de fissuration nécessite un ferraillage transversal défini à l’encastrement.',
      CANTILEVER_STRUCTURAL_FACTOR_NOT_DEFINED_IN_PROFILE: 'La méthode simplifiée de déformation ne définit pas de facteur de système pour une console.',
    } as Readonly<Record<string, string>>)[warning] ?? warning;
  }

  private record(value: unknown): Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value) ? value as Record<string, unknown> : {};
  }
}
