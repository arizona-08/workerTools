import { ChangeDetectionStrategy, Component, computed, input } from '@angular/core';

type DetailRecord = Record<string, unknown>;

interface Candidate {
  barCount: number;
  barDiameter: number;
  providedArea: number | null;
  targetArea: number | null;
  excessArea: number | null;
  utilizationRatio: number | null;
}

interface GeometryCheck {
  candidate: Candidate;
  requiredWidth: number | null;
  remainingWidth: number | null;
  minimumClearSpacing: number | null;
  rejectionReason: string | null;
}

/** Présentation des candidats d'armatures déjà calculés par le backend, sans recalcul. */
@Component({
  selector: 'app-reinforcement-details',
  templateUrl: './reinforcement-details.html',
  styleUrl: './reinforcement-details.css',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ReinforcementDetails {
  readonly details = input.required<DetailRecord>();

  readonly generated = computed(() => this.record(this.details()['generatedCandidates']));
  readonly geometry = computed(() => this.record(this.details()['geometryCandidates']));
  readonly targetArea = computed(() => this.number(this.record(this.details()['targetArea'])?.['targetArea']) ?? this.number(this.details()['requiredArea']));
  readonly candidateCount = computed(() => this.number(this.generated()?.['candidateCount']) ?? this.generatedCandidates().length);
  readonly generatedCandidates = computed(() => this.records(this.generated()?.['candidates']).map((candidate) => this.candidate(candidate)).filter((candidate): candidate is Candidate => candidate !== null));
  readonly acceptedCandidates = computed(() => this.records(this.geometry()?.['acceptedCandidates']).map((check) => this.geometryCheck(check)).filter((check): check is GeometryCheck => check !== null));
  readonly rejectedCandidates = computed(() => this.records(this.geometry()?.['rejectedCandidates']).map((check) => this.geometryCheck(check)).filter((check): check is GeometryCheck => check !== null));
  readonly selectedReinforcement = computed(() => this.record(this.details()['longitudinalReinforcement']));
  readonly selectedCandidate = computed(() => this.candidate(
    this.record(this.details()['selectedCandidate'])?.['originalCandidate']
    ?? this.details()['selectedCandidate']
    ?? this.selectedReinforcement()
    ?? this.details(),
  ));
  readonly selectedSource = computed(() => {
    const source = this.selectedReinforcement()?.['source'] ?? this.details()['source'];

    return typeof source === 'string' ? source : null;
  });

  formatNumber(value: number | null, maximumFractionDigits = 2): string {
    return value === null || !Number.isFinite(value) ? '—' : new Intl.NumberFormat('fr-FR', { maximumFractionDigits }).format(value);
  }

  formatRatio(value: number | null): string {
    return value === null || !Number.isFinite(value) ? '—' : `${Math.round(value * 100)} %`;
  }

  candidateLabel(candidate: Candidate): string {
    return `${candidate.barCount} HA${this.formatNumber(candidate.barDiameter, 0)}`;
  }

  rejectionLabel(reason: string | null): string {
    return reason === null ? '—' : ({
      INSUFFICIENT_HORIZONTAL_SPACE: 'Largeur disponible insuffisante',
      LONGITUDINAL_SPACING_EXCEEDED: 'Espacement longitudinal insuffisant',
      TRANSVERSE_LEG_SPACING_EXCEEDED: 'Espacement avec les étriers insuffisant',
    } as Record<string, string>)[reason] ?? reason;
  }

  sourceLabel(source: string | null): string {
    return source === 'PROPOSED' ? 'Ferraillage proposé' : source === 'PROVIDED' ? 'Ferraillage fourni' : 'Ferraillage retenu';
  }

  private candidate(value: unknown): Candidate | null {
    const candidate = this.record(value);
    if (candidate === null) {
      return null;
    }
    const barCount = this.number(candidate?.['barCount']);
    const barDiameter = this.number(candidate?.['barDiameter']);

    return barCount === null || barDiameter === null ? null : {
      barCount,
      barDiameter,
      providedArea: this.number(candidate['providedArea']),
      targetArea: this.number(candidate['targetArea']),
      excessArea: this.number(candidate['excessArea']),
      utilizationRatio: this.number(candidate['utilizationRatio']),
    };
  }

  private geometryCheck(value: DetailRecord): GeometryCheck | null {
    const candidate = this.candidate(value['candidate']);

    return candidate === null ? null : {
      candidate,
      requiredWidth: this.number(value['requiredWidth']),
      remainingWidth: this.number(value['remainingWidth']),
      minimumClearSpacing: this.number(value['minimumClearSpacing']),
      rejectionReason: typeof value['rejectionReason'] === 'string' ? value['rejectionReason'] : null,
    };
  }

  private record(value: unknown): DetailRecord | null {
    return typeof value === 'object' && value !== null && !Array.isArray(value) ? value as DetailRecord : null;
  }

  private records(value: unknown): DetailRecord[] {
    return Array.isArray(value) ? value.filter((item): item is DetailRecord => this.record(item) !== null) : [];
  }

  private number(value: unknown): number | null {
    return typeof value === 'number' && Number.isFinite(value) ? value : null;
  }
}
