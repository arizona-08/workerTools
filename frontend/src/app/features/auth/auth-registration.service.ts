import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, switchMap } from 'rxjs';

import { AuthSessionService } from './auth-session.service';

export interface RegistrationPayload {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}

export interface RegisteredUser {
  id: number;
  name: string;
  email: string;
}

export interface RegistrationResponse {
  user: RegisteredUser;
}

/** Client HTTP minimal du parcours d'inscription ; l'état utilisateur sera traité par AUTH-05. */
@Injectable({ providedIn: 'root' })
export class AuthRegistrationService {
  private readonly http = inject(HttpClient);
  private readonly session = inject(AuthSessionService);

  register(payload: RegistrationPayload): Observable<RegistrationResponse> {
    return this.session.establishCsrfProtection().pipe(
      switchMap(() => this.http.post<RegistrationResponse>('/api/auth/register', payload, { withCredentials: true })),
    );
  }
}
