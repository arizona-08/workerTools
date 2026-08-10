import { Routes } from '@angular/router';
import { PublicLayout } from './layouts/public-layout/public-layout';
import { AuthLayout } from './layouts/auth-layout/auth-layout';
import { MainAppLayout } from './layouts/main-app-layout/main-app-layout';
import { Login } from './pages/app/auth/login/login';
import { Register } from './pages/app/auth/register/register';
import { Dashboard } from './pages/app/dashboard/dashboard';
import { Calculator } from './pages/app/calculator/calculator';

export const routes: Routes = [
  // Pages Seo
  {
    path: '',
    component: PublicLayout,
  },

  // Pages Auth
  {
    path: 'auth',
    component: AuthLayout,
    children: [
      {
        path: '',
        pathMatch: 'full',
        redirectTo: 'login',
      },
      {
        path: 'login',
        component: Login,
        title: 'Connexion'
      },
      {
        path: 'register',
        component: Register,
        title: 'Inscription'
      }
    ]
  },

  // Pages Application
  {
    path: 'app',
    component: MainAppLayout,
    children: [
      {
        path: '',
        pathMatch: 'full',
        redirectTo: 'dashboard',
      },
      {
        path: 'dashboard',
        component: Dashboard
      },
      {
        path: 'calculator',
        component: Calculator
      }
    ]
  },

  
];
