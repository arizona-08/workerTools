import { routes } from './app.routes';

describe('application routes', () => {
  it('keeps the calculator area public for guests', () => {
    const applicationRoute = routes.find((route) => route.path === 'app');
    const routePaths = applicationRoute?.children?.map((route) => route.path);

    expect(applicationRoute?.canActivateChild).toBeUndefined();
    expect(routePaths).toContain('calculator');
    expect(routePaths).toContain('dashboard');
  });

  it('redirects every authentication page while authentication is disabled', () => {
    const authenticationRoute = routes.find((route) => route.path === 'auth');
    const children = authenticationRoute?.children ?? [];

    expect(authenticationRoute?.component).toBeUndefined();
    expect(children.find((route) => route.path === 'login')?.redirectTo).toBe('/app/dashboard');
    expect(children.find((route) => route.path === 'register')?.redirectTo).toBe('/app/dashboard');
    expect(children.find((route) => route.path === '**')?.redirectTo).toBe('/app/dashboard');
  });
});
