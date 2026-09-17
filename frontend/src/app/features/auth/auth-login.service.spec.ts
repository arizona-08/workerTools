import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';

import { AuthLoginService, LoginPayload } from './auth-login.service';

describe('AuthLoginService', () => {
  let service: AuthLoginService;
  let http: HttpTestingController;

  const payload: LoginPayload = { email: 'jeanne.dupont@example.test', password: 'mot-de-passe-fiable' };

  beforeEach(() => {
    TestBed.configureTestingModule({ providers: [provideHttpClient(), provideHttpClientTesting()] });
    service = TestBed.inject(AuthLoginService);
    http = TestBed.inject(HttpTestingController);
  });

  afterEach(() => http.verify());

  it('establishes CSRF protection then posts credentials with cookies enabled', () => {
    service.login(payload).subscribe((response) => expect(response.user.email).toBe(payload.email));

    const csrfRequest = http.expectOne('/sanctum/csrf-cookie');
    expect(csrfRequest.request.method).toBe('GET');
    expect(csrfRequest.request.withCredentials).toBe(true);
    csrfRequest.flush(null);

    const loginRequest = http.expectOne('/api/auth/login');
    expect(loginRequest.request.method).toBe('POST');
    expect(loginRequest.request.body).toEqual(payload);
    expect(loginRequest.request.withCredentials).toBe(true);
    loginRequest.flush({ user: { id: 1, name: 'Jeanne Dupont', email: payload.email } });
  });
});
