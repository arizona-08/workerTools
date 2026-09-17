# CORE-02 — Audit backend

## État actuel

- Laravel 13, Sanctum, Pest et Pint sont installés.
- `routes/api.php` expose actuellement une route de diagnostic et la route utilisateur protégée ; `routes/auth.php` contient les routes d'authentification.
- Les contrôleurs et tests sont encore ceux du squelette, sans domaine de calcul existant.

## Emplacements retenus pour les prochaines EPIC

Les classes métier seront ajoutées de manière progressive sous `backend/app/StructuralCalculation/` :

- `Materials/` pour les référentiels béton et acier ;
- `Eurocode/` pour les profils et règles normatives ;
- `Inputs/`, `Results/` et `Verifications/` lorsque les contrats de calcul seront nécessaires ;
- des use cases dédiés avant les contrôleurs HTTP, qui resteront légers.

Cette arborescence n'est pas créée à l'avance : aucun calcul ni règle normative ne relève de CORE-02.
