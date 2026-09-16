import { ChangeDetectionStrategy, Component, computed, input } from '@angular/core';

import { ResultMetricCard } from '../result-metric-card/result-metric-card';
import { BeamLongitudinalReinforcementSummary, BeamResultSummary } from './beam-result-summary';

interface ResultMetricCardPresentation {
  label: string;
  value: string;
  unit?: string;
  symbol?: string;
  secondaryValue?: string;
}

/**
 * Présente les quatre valeurs principales de BEAM-RESULT-03. Les nombres sont
 * uniquement formatés pour l'interface, sans calcul ni modification du résumé.
 */
@Component({
  selector: 'app-result-summary-cards',
  imports: [ResultMetricCard],
  templateUrl: './result-summary-cards.html',
  styleUrl: './result-summary-cards.css',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ResultSummaryCards {
  readonly summary = input.required<BeamResultSummary>();

  readonly cards = computed<readonly ResultMetricCardPresentation[]>(() => {
    const summary = this.summary();
    const reinforcement = summary.longitudinalReinforcement;
    const isCantilever = summary.supportSystem === 'CANTILEVER';

    return [
      {
        label: isCantilever ? 'Moment à l’encastrement' : 'Moment de calcul',
        value: this.formatNumber(summary.designBendingMoment, 2),
        unit: summary.designBendingMoment === null ? undefined : 'kN·m',
        symbol: 'MEd',
      },
      ...(isCantilever ? [{
        label: 'Effort tranchant à l’encastrement',
        value: this.formatNumber(summary.designShearForce ?? null, 2),
        unit: summary.designShearForce === null || summary.designShearForce === undefined ? undefined : 'kN',
        symbol: 'VEd',
      }] : []),
      {
        label: 'Hauteur utile',
        value: this.formatNumber(summary.effectiveDepth, 0),
        unit: summary.effectiveDepth === null ? undefined : 'mm',
        symbol: 'd',
      },
      {
        label: 'Armatures requises',
        value: this.formatNumber(summary.requiredLongitudinalReinforcementArea, 2),
        unit: summary.requiredLongitudinalReinforcementArea === null ? undefined : 'mm²',
        symbol: 'As_req',
      },
      this.reinforcementCard(reinforcement),
    ];
  });

  private reinforcementCard(reinforcement: BeamLongitudinalReinforcementSummary | null): ResultMetricCardPresentation {
    if (reinforcement === null) {
      return { label: 'Ferraillage', value: '—' };
    }

    return {
      label: `${reinforcement.source === 'PROPOSED' ? 'Ferraillage proposé' : 'Ferraillage fourni'} — ${reinforcement.position === 'TOP' ? 'partie supérieure' : 'partie inférieure'}`,
      value: `${reinforcement.barCount} HA${reinforcement.barDiameter}`,
      secondaryValue: `As = ${this.formatNumber(reinforcement.providedArea, 2)} mm²`,
    };
  }

  private formatNumber(value: number | null, maximumFractionDigits: number): string {
    if (value === null) {
      return '—';
    }

    return new Intl.NumberFormat('fr-FR', {
      minimumFractionDigits: maximumFractionDigits,
      maximumFractionDigits,
    }).format(value);
  }
}
