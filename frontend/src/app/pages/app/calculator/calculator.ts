import { HttpErrorResponse } from '@angular/common/http';
import { Component, ViewChild, computed, inject, signal } from '@angular/core';
import { finalize } from 'rxjs';
import { ModuleSelector } from '../../../components/module-selector/module-selector';
import { BeamForm } from '../../../features/calculator/forms/beam-form/beam-form';
import { SlabForm } from '../../../features/calculator/forms/slab-form/slab-form';
import { CalculatorModule, ModuleSelectorFieldType } from '../../../../types';
import { BeamCalculationResponse, BeamCalculationService } from '../../../features/calculator/beam-calculation.service';
import { SlabCalculationResponse, SlabCalculationService } from '../../../features/calculator/slab-calculation.service';
import { PdfDownloadService } from '../../../features/calculator/pdf-download.service';
import { BeamCalculationPayload } from '../../../features/calculator/forms/beam-form/beam-geometry';
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
  private readonly slabCalculations = inject(SlabCalculationService);
  private readonly pdfDownloads = inject(PdfDownloadService);
  @ViewChild(BeamForm) private beamForm?: BeamForm;
  @ViewChild(SlabForm) private slabForm?: SlabForm;
  readonly modules: readonly CalculatorModule[] = [
    { id: 'Poutre', label: 'Poutres', description: 'Élément linéaire en béton armé' },
    { id: 'Dalle', label: 'Dalles', description: 'Élément surfacique en béton armé' },
  ];

  selectedModule = signal<ModuleSelectorFieldType>('Poutre');
  readonly calculationResult = signal<BeamCalculationResponse | null>(null);
  readonly slabCalculationResult = signal<SlabCalculationResponse | null>(null);
  readonly calculationError = signal<string | null>(null);
  readonly isCalculating = signal(false);
  readonly isExportingPdf = signal(false);
  readonly pdfExportError = signal<string | null>(null);
  readonly lastSuccessfulBeamInput = signal<BeamCalculationPayload | null>(null);
  readonly lastSuccessfulSlabInput = signal<object | null>(null);
  readonly hasExportableCalculation = computed(() =>
    (this.calculationResult() !== null && this.lastSuccessfulBeamInput() !== null)
    || (this.slabCalculationResult() !== null && this.lastSuccessfulSlabInput() !== null),
  );

  handleModuleChange(moduleType: ModuleSelectorFieldType): void {
    this.selectedModule.set(moduleType);
    this.clearCalculationResult();
    this.calculationError.set(null);
  }

  selectedModuleDescription(): string {
    return this.modules.find((module) => module.id === this.selectedModule())?.description ?? '';
  }

  calculateBeam(): void {
    if (this.isCalculating()) return;

    const submoduleMessage = this.beamForm?.submoduleCalculationMessage();
    if (submoduleMessage !== null && submoduleMessage !== undefined) {
      this.clearCalculationResult();
      this.calculationError.set(submoduleMessage);

      return;
    }

    const payload = this.beamForm?.requestPayload();
    if (payload === null || payload === undefined) {
      this.calculationError.set('Complétez les champs obligatoires avant de lancer le calcul.');
      return;
    }

    this.isCalculating.set(true);
    this.clearCalculationResult();
    this.calculationError.set(null);
    this.calculations.calculate(payload).subscribe({
      next: (result) => {
        this.calculationResult.set(result);
        this.lastSuccessfulBeamInput.set(payload);
        this.isCalculating.set(false);
      },
      error: (error: { status?: number; error?: { message?: string } }) => {
        this.calculationError.set(this.errorMessage(error, 'Le calcul n’a pas pu être exécuté. Réessayez dans quelques instants.'));
        this.isCalculating.set(false);
      },
    });
  }

  isBeamCalculationAvailable(): boolean {
    return this.beamForm?.isCurrentSubmoduleCalculable() ?? false;
  }

  calculateSlab(): void {
    if (this.isCalculating()) return;

    const payload = this.slabForm?.requestPayload();
    if (payload === null || payload === undefined) { this.calculationError.set('Complétez les champs obligatoires avant de lancer le calcul.'); return; }
    this.isCalculating.set(true); this.clearCalculationResult(); this.calculationError.set(null);
    this.slabCalculations.calculate(payload).subscribe({
      next: (result) => { this.slabCalculationResult.set(result); this.lastSuccessfulSlabInput.set(payload); this.isCalculating.set(false); },
      error: (error: { status?: number; error?: { message?: string } }) => { this.calculationError.set(this.errorMessage(error, 'Le calcul de dalle n’a pas pu être exécuté. Réessayez dans quelques instants.')); this.isCalculating.set(false); },
    });
  }

  clearCalculationResult(): void {
    this.calculationResult.set(null);
    this.slabCalculationResult.set(null);
    this.lastSuccessfulBeamInput.set(null);
    this.lastSuccessfulSlabInput.set(null);
    this.pdfExportError.set(null);
  }

  exportCalculationNote(): void {
    if (this.isExportingPdf()) return;

    const beamInput = this.lastSuccessfulBeamInput();
    const slabInput = this.lastSuccessfulSlabInput();
    if (beamInput === null && slabInput === null) return;

    this.isExportingPdf.set(true);
    this.pdfExportError.set(null);
    const exportRequest = beamInput !== null
      ? this.calculations.exportPdf(beamInput)
      : this.slabCalculations.exportPdf(slabInput!);
    const fallbackFilename = beamInput !== null ? 'note-calcul-poutre.pdf' : 'note-calcul-dalle.pdf';

    exportRequest.pipe(finalize(() => this.isExportingPdf.set(false))).subscribe({
      next: (response) => {
        try {
          this.pdfDownloads.download(response, fallbackFilename);
        } catch {
          this.pdfExportError.set('Le téléchargement de la note de calcul n’a pas pu être lancé. Veuillez réessayer.');
        }
      },
      error: (error: HttpErrorResponse) => { void this.setPdfExportError(error); },
    });
  }

  private errorMessage(error: { status?: number; error?: { message?: string } }, fallback: string): string {
    return error.status === 400 || error.status === 422
      ? error.error?.message ?? 'Certaines données du calcul ne sont pas valides.'
      : fallback;
  }

  private async setPdfExportError(error: HttpErrorResponse): Promise<void> {
    const backendMessage = await this.pdfDownloads.errorMessage(error);
    if ((error.status === 400 || error.status === 422) && backendMessage !== null) {
      this.pdfExportError.set(backendMessage);
      return;
    }
    if (error.status === 0) {
      this.pdfExportError.set('La connexion au serveur a échoué pendant l’export. Vérifiez votre réseau puis réessayez.');
      return;
    }

    this.pdfExportError.set('Une erreur est survenue pendant la génération de la note de calcul. Veuillez réessayer.');
  }

  /** Formatage de présentation uniquement : les valeurs métier restent celles du backend. */
  formatResultNumber(value: number | null, maximumFractionDigits: number): string {
    if (value === null || !Number.isFinite(value)) {
      return '—';
    }

    return new Intl.NumberFormat('fr-FR', { maximumFractionDigits }).format(value);
  }

  verificationLabel(identifier: string): string {
    return ({
      FLEXURE: 'Flexion',
      SHEAR: 'Cisaillement',
      STRESS: 'Contraintes ELS',
      CRACK: 'Fissuration',
      DEFLECTION: 'Déformation',
      MAIN_REINFORCEMENT: 'Armatures principales',
      SECONDARY_REINFORCEMENT: 'Armatures secondaires',
    } as Record<string, string>)[identifier] ?? identifier;
  }

  verificationStatusLabel(status: string): string {
    return ({
      COMPLIANT: 'Conforme',
      NOT_COMPLIANT: 'Non conforme',
      NOT_CHECKED: 'Non vérifié',
      NOT_APPLICABLE: 'Non applicable',
      CALCULATION_METHOD_NOT_SUPPORTED: 'Méthode non prise en charge',
    } as Record<string, string>)[status] ?? status;
  }
}
