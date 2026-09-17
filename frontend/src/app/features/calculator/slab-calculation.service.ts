import { HttpClient, HttpResponse } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';

import { VerificationStatus, VerificationType } from './results/result-compliance-indicator/result-compliance-indicator';

export interface SlabReinforcementSummary { diameter: number; spacing: number; providedAreaPerMeter: number; }
export interface SlabCalculationResponse {
  status: VerificationStatus;
  summary: { status: VerificationStatus; utilization: number | null; governingVerificationType: VerificationType | null; designBendingMoment: number | null; effectiveDepth: number | null; requiredMainReinforcementArea: number | null; minimumMainReinforcementArea: number | null; mainReinforcement: SlabReinforcementSummary | null; secondaryReinforcement: SlabReinforcementSummary | null; };
  verifications: Array<{ identifier: VerificationType; status: VerificationStatus; utilization: number | null; governingValue: number | null; limitValue: number | null; method: string | null; warnings: string[] }>;
  details: Record<string, unknown>;
}

/** Client Slab : le backend reste la seule source de calcul et de conformité. */
@Injectable({ providedIn: 'root' })
export class SlabCalculationService {
  private readonly http = inject(HttpClient);
  calculate(payload: object): Observable<SlabCalculationResponse> { return this.http.post<SlabCalculationResponse>('/api/slab/calculations', payload); }
  exportPdf(payload: object): Observable<HttpResponse<Blob>> { return this.http.post('/api/slab/calculations/pdf', payload, { observe: 'response', responseType: 'blob' }); }
}
