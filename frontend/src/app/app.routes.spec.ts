import { routes } from './app.routes';
import { MainAppLayout } from './layouts/main-app-layout/main-app-layout';
import { Calculator } from './pages/app/calculator/calculator';

describe('application routes', () => {
  it('uses the calculator as the public home page', () => {
    const homeRoute = routes.find((route) => route.path === '');

    expect(homeRoute?.component).toBe(MainAppLayout);
    expect(homeRoute?.pathMatch).toBe('full');
    expect(homeRoute?.children?.find((route) => route.path === '')?.component).toBe(Calculator);
  });

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
