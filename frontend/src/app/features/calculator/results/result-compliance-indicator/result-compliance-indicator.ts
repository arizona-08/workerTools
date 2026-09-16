import { ChangeDetectionStrategy, Component, computed, input } from '@angular/core';
import { LucideCircleCheck, LucideCircleHelp, LucideCircleMinus, LucideCircleX, LucideTriangleAlert } from '@lucide/angular';

/** Statuts fournis par BeamVerificationStatus côté backend. */
export type VerificationStatus =
  | 'COMPLIANT'
  | 'NOT_COMPLIANT'
  | 'NOT_CHECKED'
  | 'NOT_APPLICABLE'
  | 'CALCULATION_METHOD_NOT_SUPPORTED';

export type VerificationType = 'FLEXURE' | 'SHEAR' | 'STRESS' | 'CRACK' | 'DEFLECTION' | 'MAIN_REINFORCEMENT' | 'SECONDARY_REINFORCEMENT';

interface CompliancePresentation {
  label: string;
  description: string;
}

/**
 * Présente le statut global calculé côté serveur. La conversion du ratio en
 * pourcentage est strictement une décision d'affichage : aucun statut n'est
 * déduit ni recalculé dans ce composant.
 */
@Component({
  selector: 'app-result-compliance-indicator',
  imports: [LucideCircleCheck, LucideCircleHelp, LucideCircleMinus, LucideCircleX, LucideTriangleAlert],
  templateUrl: './result-compliance-indicator.html',
  styleUrl: './result-compliance-indicator.css',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ResultComplianceIndicator {
  readonly utilization = input.required<number | null>();
  readonly status = input.required<VerificationStatus>();
  readonly governingVerificationType = input.required<VerificationType | null>();

  readonly presentation = computed<CompliancePresentation>(() => {
    switch (this.status()) {
      case 'COMPLIANT':
        return { label: 'Conforme', description: 'Les vérifications réalisées sont conformes.' };
      case 'NOT_COMPLIANT':
        return { label: 'Non conforme', description: 'Au moins une vérification réalisée n’est pas conforme.' };
      case 'NOT_CHECKED':
        return { label: 'Vérification incomplète', description: 'Le calcul ne permet pas encore de conclure à la conformité.' };
      case 'NOT_APPLICABLE':
        return { label: 'Non applicable', description: 'Cette vérification ne s’applique pas à la configuration.' };
      case 'CALCULATION_METHOD_NOT_SUPPORTED':
        return { label: 'Méthode non prise en charge', description: 'La méthode nécessaire n’est pas encore prise en charge par WorkerTools.' };
    }
  });

  readonly displayedPercentage = computed<number | null>(() => {
    const utilization = this.utilization();

    return utilization === null || !Number.isFinite(utilization) ? null : Math.round(utilization * 100);
  });

  /** Bornage exclusif au tracé SVG ; le taux affiché reste la valeur backend. */
  readonly visualProgress = computed(() => {
    const utilization = this.utilization();

    return utilization === null || !Number.isFinite(utilization) ? 0 : Math.min(Math.max(utilization, 0), 1);
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
      case 'MAIN_REINFORCEMENT':
        return 'Armatures principales';
      case 'SECONDARY_REINFORCEMENT':
        return 'Armatures secondaires';
      case null:
        return null;
    }
  });

  readonly accessibilityLabel = computed(() => {
    const percentage = this.displayedPercentage();
    const governingVerification = this.governingVerificationLabel();
    const status = this.presentation().label.toLocaleLowerCase('fr-FR');

    if (percentage === null) {
      return governingVerification === null
        ? `Statut ${status}, aucune vérification gouvernante disponible.`
        : `Statut ${status}, vérification gouvernante ${governingVerification.toLocaleLowerCase('fr-FR')}.`;
    }

    return governingVerification === null
      ? `Taux d’utilisation ${percentage} %, statut ${status}, aucune vérification gouvernante disponible.`
      : `Taux d’utilisation ${percentage} %, statut ${status}, vérification gouvernante ${governingVerification.toLocaleLowerCase('fr-FR')}.`;
  });
}
