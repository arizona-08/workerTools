import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';

import { AuthRegistrationService, RegistrationPayload } from './auth-registration.service';

describe('AuthRegistrationService', () => {
  let service: AuthRegistrationService;
  let http: HttpTestingController;

  const payload: RegistrationPayload = {
    name: 'Jeanne Dupont',
    email: 'jeanne.dupont@example.test',
    password: 'mot-de-passe-fiable',
    password_confirmation: 'mot-de-passe-fiable',
  };

  beforeEach(() => {
    TestBed.configureTestingModule({ providers: [provideHttpClient(), provideHttpClientTesting()] });
    service = TestBed.inject(AuthRegistrationService);
    http = TestBed.inject(HttpTestingController);
  });

  afterEach(() => http.verify());

  it('establishes CSRF protection then posts the registration payload to the dedicated endpoint', () => {
    service.register(payload).subscribe((response) => expect(response.user.email).toBe(payload.email));

    const csrfRequest = http.expectOne('/sanctum/csrf-cookie');
    expect(csrfRequest.request.method).toBe('GET');
    expect(csrfRequest.request.withCredentials).toBe(true);
    csrfRequest.flush(null);

    const request = http.expectOne('/api/auth/register');
    expect(request.request.method).toBe('POST');
    expect(request.request.body).toEqual(payload);
    expect(request.request.withCredentials).toBe(true);
    request.flush({ user: { id: 1, name: payload.name, email: payload.email } });
  });
});
