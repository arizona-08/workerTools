import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';

import { AuthCurrentUserService } from './auth-current-user.service';

describe('AuthCurrentUserService', () => {
  let service: AuthCurrentUserService;
  let http: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({ providers: [provideHttpClient(), provideHttpClientTesting()] });
    service = TestBed.inject(AuthCurrentUserService);
    http = TestBed.inject(HttpTestingController);
  });

  afterEach(() => http.verify());

  it('restores the minimal current user from the protected endpoint', () => {
    service.restore().subscribe();

    const request = http.expectOne('/api/user');
    expect(request.request.withCredentials).toBe(true);
    request.flush({ id: 1, name: 'Jeanne Dupont', email: 'jeanne.dupont@example.test' });

    expect(service.currentUser()).toEqual({ id: 1, name: 'Jeanne Dupont', email: 'jeanne.dupont@example.test' });
    expect(service.isAuthenticated()).toBe(true);
  });

  it('clears the state when the server reports an expired or absent session', () => {
    service.setCurrentUser({ id: 1, name: 'Jeanne Dupont', email: 'jeanne.dupont@example.test' });

    service.restore().subscribe();
    http.expectOne('/api/user').flush({ message: 'Unauthenticated.' }, { status: 401, statusText: 'Unauthorized' });

    expect(service.currentUser()).toBeNull();
    expect(service.isAuthenticated()).toBe(false);
  });

  it('uses CSRF protection then clears the frontend user after logout', () => {
    service.setCurrentUser({ id: 1, name: 'Jeanne Dupont', email: 'jeanne.dupont@example.test' });
    service.logout().subscribe();

    const csrfRequest = http.expectOne('/sanctum/csrf-cookie');
    expect(csrfRequest.request.withCredentials).toBe(true);
    csrfRequest.flush(null);

    const logoutRequest = http.expectOne('/api/auth/logout');
    expect(logoutRequest.request.method).toBe('POST');
    expect(logoutRequest.request.withCredentials).toBe(true);
    logoutRequest.flush(null, { status: 204, statusText: 'No Content' });

    expect(service.currentUser()).toBeNull();
  });
});
