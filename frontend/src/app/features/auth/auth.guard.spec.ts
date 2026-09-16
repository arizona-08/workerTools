import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';
import { Router, UrlTree } from '@angular/router';
import { Observable } from 'rxjs';
import { vi } from 'vitest';

import { authGuard } from './auth.guard';

describe('authGuard', () => {
  let http: HttpTestingController;
  const redirect = {};
  const router = { createUrlTree: vi.fn().mockReturnValue(redirect) };

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        { provide: Router, useValue: router },
      ],
    });
    http = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    http.verify();
    router.createUrlTree.mockClear();
  });

  it('allows a future private route for a server-authenticated user', () => {
    let result: boolean | UrlTree | undefined;
    const result$ = TestBed.runInInjectionContext(() => authGuard({} as never, {} as never)) as Observable<boolean | UrlTree>;
    result$.subscribe((value) => result = value);

    http.expectOne('/api/user').flush({ id: 1, name: 'Jeanne Dupont', email: 'jeanne.dupont@example.test' });

    expect(result).toBe(true);
  });

  it('redirects guests attempting to access a future private route to the login page', () => {
    let result: boolean | UrlTree | undefined;
    const result$ = TestBed.runInInjectionContext(() => authGuard({} as never, {} as never)) as Observable<boolean | UrlTree>;
    result$.subscribe((value) => result = value);

    http.expectOne('/api/user').flush({ message: 'Unauthenticated.' }, { status: 401, statusText: 'Unauthorized' });

    expect(router.createUrlTree).toHaveBeenCalledWith(['/auth/login']);
    expect(result).toBe(redirect);
  });
});
