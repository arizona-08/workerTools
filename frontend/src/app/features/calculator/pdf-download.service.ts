import { DOCUMENT, isPlatformBrowser } from '@angular/common';
import { HttpErrorResponse, HttpResponse } from '@angular/common/http';
import { Injectable, PLATFORM_ID, inject } from '@angular/core';

export function filenameFromContentDisposition(contentDisposition: string | null, fallback: string): string {
  const encoded = contentDisposition?.match(/filename\*=UTF-8''([^;]+)/i)?.[1];
  const plain = contentDisposition?.match(/filename\s*=\s*"?([^";]+)"?/i)?.[1];
  let filename = encoded ?? plain ?? fallback;

  try {
    filename = decodeURIComponent(filename);
  } catch {
    filename = plain ?? fallback;
  }

  const safeFilename = filename.replace(/[\\/]/g, '_').trim();
  return safeFilename || fallback;
}

/** Téléchargement navigateur uniquement ; le PDF reste généré et validé par le backend. */
@Injectable({ providedIn: 'root' })
export class PdfDownloadService {
  private readonly platformId = inject(PLATFORM_ID);
  private readonly document = inject(DOCUMENT);

  download(response: HttpResponse<Blob>, fallbackFilename: string): void {
    const blob = response.body;
    const contentType = response.headers.get('content-type') ?? blob?.type ?? '';
    if (blob === null || !contentType.toLowerCase().includes('application/pdf')) {
      throw new Error('INVALID_PDF_RESPONSE');
    }
    if (!isPlatformBrowser(this.platformId)) {
      return;
    }

    const urlApi = this.document.defaultView?.URL;
    if (urlApi === undefined) {
      throw new Error('BROWSER_DOWNLOAD_UNAVAILABLE');
    }

    const objectUrl = urlApi.createObjectURL(blob);
    const anchor = this.document.createElement('a');
    anchor.href = objectUrl;
    anchor.download = filenameFromContentDisposition(response.headers.get('content-disposition'), fallbackFilename);
    anchor.style.display = 'none';
    this.document.body.appendChild(anchor);
    anchor.click();
    anchor.remove();
    urlApi.revokeObjectURL(objectUrl);
  }

  async errorMessage(error: HttpErrorResponse): Promise<string | null> {
    const blob = error.error;
    if (blob?.type?.includes('application/json') && typeof blob.text === 'function') {
      try {
        const payload = JSON.parse(await blob.text()) as { message?: unknown };
        return typeof payload.message === 'string' ? payload.message : null;
      } catch {
        return null;
      }
    }

    return null;
  }
}
