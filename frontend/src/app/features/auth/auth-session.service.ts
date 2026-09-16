import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';

/** Prépare la protection CSRF de la session Sanctum avant une écriture authentifiée. */
@Injectable({ providedIn: 'root' })
export class AuthSessionService {
  private readonly http = inject(HttpClient);

  establishCsrfProtection(): Observable<void> {
    return this.http.get<void>('/sanctum/csrf-cookie', { withCredentials: true });
  }
}
