import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';

import { BeamCalculationPayload } from './forms/beam-form/beam-geometry';
import { BeamCalculationService } from './beam-calculation.service';

describe('BeamCalculationService', () => {
  let service: BeamCalculationService;
  let http: HttpTestingController;

  const payload: BeamCalculationPayload = {
    configuration: {
      calculationMode: 'DESIGN',
      elementType: 'BEAM',
      materialType: 'REINFORCED_CONCRETE',
      sectionType: 'RECTANGULAR',
      supportSystem: 'SIMPLY_SUPPORTED',
      loadModel: 'UNIFORMLY_DISTRIBUTED',
      designCodeProfile: 'NF_EN_1992_1_1_2005_FR',
      designSituation: 'PERSISTENT_TRANSIENT',
    },
    geometry: { effectiveSpan: 6500, width: 300, height: 600, unit: 'mm' },
    materials: { concreteClass: 'C30/37', steelGrade: 'B500B', exposureClasses: ['XC1'] },
    loads: {
      permanent: { includeSelfWeight: true, additionalPermanentLoad: 5, unit: 'kN/m' },
      variable: { category: 'A', characteristicLoad: 3.5, unit: 'kN/m' },
    },
  };

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting()],
    });

    service = TestBed.inject(BeamCalculationService);
    http = TestBed.inject(HttpTestingController);
  });

  afterEach(() => http.verify());

  it('posts the unchanged payload to the relative calculation endpoint', () => {
    service.calculate(payload).subscribe((response) => {
      expect(response.summary.status).toBe('COMPLIANT');
    });

    const request = http.expectOne('/api/beam/calculations');
    expect(request.request.method).toBe('POST');
    expect(request.request.body).toEqual(payload);
    request.flush({
      summary: { utilization: 0.91, status: 'COMPLIANT', governingVerificationType: 'FLEXURE' },
      verifications: {},
      details: {},
    });
  });

  it('forwards a backend validation error without translating it into a client calculation', () => {
    service.calculate(payload).subscribe({
      next: () => {
        throw new Error('La requête aurait dû échouer.');
      },
      error: (error) => expect(error.status).toBe(422),
    });

    const request = http.expectOne('/api/beam/calculations');
    request.flush({ message: 'Configuration non prise en charge.' }, { status: 422, statusText: 'Unprocessable Entity' });
  });

  it('posts the original input to the PDF endpoint and requests a Blob response', () => {
    service.exportPdf(payload).subscribe((response) => {
      expect(response.body).toBeInstanceOf(Blob);
      expect(response.headers.get('content-disposition')).toContain('note-calcul-poutre.pdf');
    });

    const request = http.expectOne('/api/beam/calculations/pdf');
    expect(request.request.method).toBe('POST');
    expect(request.request.body).toEqual(payload);
    expect(request.request.responseType).toBe('blob');
    request.flush(new Blob(['%PDF-test'], { type: 'application/pdf' }), {
      headers: { 'content-type': 'application/pdf', 'content-disposition': 'attachment; filename="note-calcul-poutre.pdf"' },
    });
  });
});
