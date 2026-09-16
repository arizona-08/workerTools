import { CanActivateChildFn, Router } from '@angular/router';
import { inject } from '@angular/core';
import { map } from 'rxjs';

import { AuthCurrentUserService } from './auth-current-user.service';

/** Réservé aux futures routes personnelles, notamment l’historique des calculs. */
export const authGuard: CanActivateChildFn = () => {
  const authentication = inject(AuthCurrentUserService);
  const router = inject(Router);

  return authentication.restore().pipe(
    map((user) => user === null ? router.createUrlTree(['/auth/login']) : true),
  );
};
