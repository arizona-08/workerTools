import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';

import { SlabCalculationService } from './slab-calculation.service';

describe('SlabCalculationService', () => {
  let service: SlabCalculationService;
  let http: HttpTestingController;
  const payload = { configuration: { elementType: 'SLAB' }, geometry: { effectiveSpan: 5000, thickness: 200 }, materials: { concreteClass: 'C30/37' }, loads: { imposedLoad: 2 } };

  beforeEach(() => {
    TestBed.configureTestingModule({ providers: [provideHttpClient(), provideHttpClientTesting()] });
    service = TestBed.inject(SlabCalculationService);
    http = TestBed.inject(HttpTestingController);
  });

  afterEach(() => http.verify());

  it('posts the original input to the slab PDF endpoint as a Blob request', () => {
    service.exportPdf(payload).subscribe((response) => {
      expect(response.body).toBeInstanceOf(Blob);
    });

    const request = http.expectOne('/api/slab/calculations/pdf');
    expect(request.request.method).toBe('POST');
    expect(request.request.body).toEqual(payload);
    expect(request.request.responseType).toBe('blob');
    request.flush(new Blob(['%PDF-test'], { type: 'application/pdf' }), { headers: { 'content-type': 'application/pdf' } });
  });
});
