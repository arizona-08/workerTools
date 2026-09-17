import { isPlatformBrowser } from '@angular/common';
import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Injectable, PLATFORM_ID, computed, inject, signal } from '@angular/core';
import { Observable, catchError, finalize, of, switchMap, tap } from 'rxjs';

import { RegisteredUser } from './auth-registration.service';
import { AuthSessionService } from './auth-session.service';

/** État minimal de la session courante, partagé par le guard et l’interface de compte. */
@Injectable({ providedIn: 'root' })
export class AuthCurrentUserService {
  private readonly http = inject(HttpClient);
  private readonly session = inject(AuthSessionService);
  private readonly platformId = inject(PLATFORM_ID);

  readonly currentUser = signal<RegisteredUser | null>(null);
  readonly isRestoring = signal(false);
  readonly isAuthenticated = computed(() => this.currentUser() !== null);

  restore(): Observable<RegisteredUser | null> {
    if (!isPlatformBrowser(this.platformId)) return of(null);

    this.isRestoring.set(true);
    return this.http.get<RegisteredUser>('/api/user', { withCredentials: true }).pipe(
      tap((user) => this.currentUser.set(user)),
      catchError((error: HttpErrorResponse) => {
        if (error.status === 401 || error.status === 419) this.currentUser.set(null);
        return of(null);
      }),
      finalize(() => this.isRestoring.set(false)),
    );
  }

  setCurrentUser(user: RegisteredUser): void {
    this.currentUser.set(user);
  }

  logout(): Observable<void> {
    return this.session.establishCsrfProtection().pipe(
      switchMap(() => this.http.post<void>('/api/auth/logout', {}, { withCredentials: true })),
      tap(() => this.currentUser.set(null)),
    );
  }
}
