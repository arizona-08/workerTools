import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, switchMap } from 'rxjs';

import { AuthSessionService } from './auth-session.service';
import { RegisteredUser } from './auth-registration.service';

export interface LoginPayload {
  email: string;
  password: string;
}

export interface LoginResponse {
  user: RegisteredUser;
}

/** Connexion par session Sanctum ; aucun token ni mot de passe n'est conservé côté navigateur. */
@Injectable({ providedIn: 'root' })
export class AuthLoginService {
  private readonly http = inject(HttpClient);
  private readonly session = inject(AuthSessionService);

  login(payload: LoginPayload): Observable<LoginResponse> {
    return this.session.establishCsrfProtection().pipe(
      switchMap(() => this.http.post<LoginResponse>('/api/auth/login', payload, { withCredentials: true })),
    );
  }
}
