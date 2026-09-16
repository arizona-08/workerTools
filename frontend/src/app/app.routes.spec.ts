import { routes } from './app.routes';

describe('application routes', () => {
  it('keeps the calculator area public for guests', () => {
    const applicationRoute = routes.find((route) => route.path === 'app');
    const routePaths = applicationRoute?.children?.map((route) => route.path);

    expect(applicationRoute?.canActivateChild).toBeUndefined();
    expect(routePaths).toContain('calculator');
    expect(routePaths).toContain('dashboard');
  });
});
