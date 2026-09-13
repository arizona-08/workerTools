import { ChangeDetectionStrategy, Component, computed, input } from '@angular/core';
import { LucideCircleCheck, LucideCircleHelp, LucideCircleMinus, LucideCircleX, LucideTriangleAlert } from '@lucide/angular';

import { VerificationStatus, VerificationType } from '../result-compliance-indicator/result-compliance-indicator';

interface ResultSummaryMessagePresentation {
  message: string;
  tone: VerificationStatus;
}

/**
 * Synthèse textuelle des résultats métier. Le statut backend choisit toujours
 * le message principal ; taux et vérification gouvernante restent informatifs.
 */
@Component({
  selector: 'app-result-summary-message',
  imports: [LucideCircleCheck, LucideCircleHelp, LucideCircleMinus, LucideCircleX, LucideTriangleAlert],
  templateUrl: './result-summary-message.html',
  styleUrl: './result-summary-message.css',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ResultSummaryMessage {
  readonly status = input.required<VerificationStatus>();
  readonly utilization = input<number | null>(null);
  readonly governingVerificationType = input<VerificationType | null>(null);

  readonly presentation = computed<ResultSummaryMessagePresentation>(() => {
    switch (this.status()) {
      case 'COMPLIANT':
        return {
          message: 'La section satisfait les vérifications réalisées dans le périmètre actuel selon l’Eurocode 2.',
          tone: 'COMPLIANT',
        };
      case 'NOT_COMPLIANT':
        return {
          message: 'La section ne satisfait pas toutes les vérifications réalisées dans le périmètre actuel.',
          tone: 'NOT_COMPLIANT',
        };
      case 'NOT_CHECKED':
        return {
          message: 'Toutes les vérifications nécessaires n’ont pas pu être menées à leur terme. Aucune conclusion complète de conformité ne peut être établie.',
          tone: 'NOT_CHECKED',
        };
      case 'NOT_APPLICABLE':
        return {
          message: 'Cette vérification n’est pas applicable à la configuration étudiée.',
          tone: 'NOT_APPLICABLE',
        };
      case 'CALCULATION_METHOD_NOT_SUPPORTED':
        return {
          message: 'Une vérification requise n’est pas prise en charge par la méthode actuellement implémentée. Le calcul ne permet pas de conclure à une conformité globale.',
          tone: 'CALCULATION_METHOD_NOT_SUPPORTED',
        };
    }
  });

  readonly governingVerificationLabel = computed(() => {
    switch (this.governingVerificationType()) {
      case 'FLEXURE':
        return 'Flexion';
      case 'SHEAR':
        return 'Cisaillement';
      case 'STRESS':
        return 'Contraintes ELS';
      case 'CRACK':
        return 'Fissuration';
      case 'DEFLECTION':
        return 'Déformation';
      case null:
        return null;
    }
  });

  readonly displayedPercentage = computed<number | null>(() => {
    const utilization = this.utilization();

    return utilization === null ? null : Math.round(utilization * 100);
  });

  readonly secondaryMessage = computed(() => {
    const governingVerification = this.governingVerificationLabel();

    if (governingVerification === null) {
      return null;
    }

    const percentage = this.displayedPercentage();

    return percentage === null
      ? `Vérification la plus sollicitée : ${governingVerification}.`
      : `Vérification la plus sollicitée : ${governingVerification} — ${percentage} %.`;
  });
}
