import { DOCUMENT } from '@angular/common';
import { HttpHeaders, HttpResponse } from '@angular/common/http';
import { TestBed } from '@angular/core/testing';
import { vi } from 'vitest';

import { filenameFromContentDisposition, PdfDownloadService } from './pdf-download.service';

describe('PdfDownloadService', () => {
  it('extracts safe RFC 5987 and fallback filenames', () => {
    expect(filenameFromContentDisposition("attachment; filename*=UTF-8''note%20calcul.pdf", 'fallback.pdf')).toBe('note calcul.pdf');
    expect(filenameFromContentDisposition('attachment; filename="../../unsafe.pdf"', 'fallback.pdf')).toBe('.._.._unsafe.pdf');
    expect(filenameFromContentDisposition(null, 'note-calcul-dalle.pdf')).toBe('note-calcul-dalle.pdf');
  });

  it('creates then revokes an Object URL for a valid PDF', () => {
    TestBed.configureTestingModule({ providers: [PdfDownloadService] });
    const service = TestBed.inject(PdfDownloadService);
    const document = TestBed.inject(DOCUMENT) as Document;
    const url = document.defaultView!.URL as typeof URL & {
      createObjectURL?: (object: Blob) => string;
      revokeObjectURL?: (url: string) => void;
    };
    if (url.createObjectURL === undefined) {
      Object.defineProperty(url, 'createObjectURL', { configurable: true, value: () => 'blob:test' });
    }
    if (url.revokeObjectURL === undefined) {
      Object.defineProperty(url, 'revokeObjectURL', { configurable: true, value: () => undefined });
    }
    const createObjectURL = vi.spyOn(url, 'createObjectURL').mockReturnValue('blob:test');
    const revokeObjectURL = vi.spyOn(url, 'revokeObjectURL');
    vi.spyOn(HTMLAnchorElement.prototype, 'click');

    service.download(new HttpResponse({ body: new Blob(['%PDF'], { type: 'application/pdf' }), headers: new HttpHeaders({ 'content-type': 'application/pdf' }) }), 'fallback.pdf');

    expect(createObjectURL).toHaveBeenCalled();
    expect(revokeObjectURL).toHaveBeenCalledWith('blob:test');
  });
});
