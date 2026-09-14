import { ChangeDetectionStrategy, Component, input } from '@angular/core';

/** Carte de présentation réutilisable ; elle ne connaît aucun calcul métier. */
@Component({
  selector: 'app-result-metric-card',
  templateUrl: './result-metric-card.html',
  styleUrl: './result-metric-card.css',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ResultMetricCard {
  readonly label = input.required<string>();
  readonly value = input.required<string>();
  readonly unit = input<string>();
  readonly symbol = input<string>();
  readonly secondaryValue = input<string>();
}
