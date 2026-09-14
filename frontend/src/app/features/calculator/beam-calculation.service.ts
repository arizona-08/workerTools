import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';

import { BeamCalculationPayload } from './forms/beam-form/beam-geometry';
import { BeamCalculationDetails } from './results/calculation-details/beam-calculation-details';
import { BeamResultSummary } from './results/result-summary-cards/beam-result-summary';
import { VerificationStatus, VerificationType } from './results/result-compliance-indicator/result-compliance-indicator';

export interface BeamCalculationResponse {
  summary: BeamResultSummary & {
    utilization: number | null;
    status: VerificationStatus;
    governingVerificationType: VerificationType | null;
  };
  verifications: Record<string, unknown>;
  details: BeamCalculationDetails;
}

/** Client HTTP du point d'entrée d'orchestration ; aucun calcul n'est fait côté Angular. */
@Injectable({ providedIn: 'root' })
export class BeamCalculationService {
  private readonly http = inject(HttpClient);

  calculate(payload: BeamCalculationPayload): Observable<BeamCalculationResponse> {
    return this.http.post<BeamCalculationResponse>('/api/beam/calculations', payload);
  }
}
