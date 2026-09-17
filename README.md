# WorkerTools V1

[WorkerTools](https://workertools.sajed-engineering.com) est une application web de calcul d'éléments courants en béton armé.
Elle guide la saisie, exécute les calculs côté Laravel, affiche une conclusion
de conformité accompagnée de ses détails, puis peut générer une note de calcul
PDF. L'interface Angular ne recalcule aucune règle structurelle.

> WorkerTools V1 couvre un périmètre volontairement restreint. Il constitue une
> aide au calcul et à la traçabilité, pas une substitution à la validation d'un
> ingénieur qualifié ni à l'analyse complète de l'ouvrage.

## Périmètre réellement supporté

| Module | Cas disponible | Mode et modèle |
|---|---|---|
| Poutre | Poutre rectangulaire simplement appuyée | Dimensionnement ou vérification ; une travée ; charges verticales uniformément réparties |
| Poutre | Poutre rectangulaire en console | Dimensionnement ou vérification ; encastrement–extrémité libre ; charges verticales uniformément réparties |
| Dalle | Dalle pleine unidirectionnelle | Dimensionnement d'une bande de calcul de 1 m ; une travée simplement appuyée sur deux côtés opposés ; charges verticales uniformément réparties |

Les deux modules utilisent actuellement :

- béton armé de masse volumique normale ;
- les classes de béton `C20/25`, `C25/30` et `C30/37` ;
- l'acier d'armature `B500B` ;
- la classe d'exposition `XC1` pour une chaîne complète incluant la fissuration ;
- la situation persistante / transitoire ;
- une action variable principale de catégorie A ;
- le profil `NF_EN_1992_1_1_2005_FR` fondé sur EN 1992-1-1:2004 et
  NF EN 1992-1-1:2005.

Les paramètres nationaux réellement portés par le profil, leurs sources et
leurs réserves documentaires sont décrits dans
[le profil français](docs/normative/ec2-03-french-profile.md).

## Ce que calcule l'application

### Poutres

- poids propre, charges caractéristiques et combinaisons ELU / ELS ;
- efforts internes pour une poutre simplement appuyée ou une console ;
- flexion ELU, hauteur utile, domaine simplement armé et armature minimale ;
- propositions d'armatures longitudinales à un lit et leur vérification
  géométrique ;
- cisaillement : `VRd,c`, besoin d'étriers, proposition d'étriers et
  vérification `VRd,max` ; pour la console, la section de contrôle est à une
  hauteur utile de l'encastrement ;
- contraintes ELS, fissuration directe et contrôle simplifié de déformation
  par rapport `L/d` ;
- agrégation des vérifications, indicateur de conformité, détail traçable et
  note de calcul PDF.

### Dalles

- poids propre, charges surfaciques (finitions, cloisons, autres permanentes
  et charge d'exploitation) et combinaisons ELU / ELS ;
- passage des charges surfaciques aux charges linéaires de la bande de 1 m ;
- moment et effort tranchant de la bande simplement appuyée ;
- flexion ELU, proposition d'armatures principales et armatures secondaires ;
- fissuration directe et contrôle simplifié `L/d` ;
- agrégation des vérifications, résultat détaillé et note de calcul PDF.

Les résultats conservent les données brutes, unités, formules et avertissements
utiles à leur lecture. Un statut `NOT_COMPLIANT` ou `NOT_CHECKED` est présenté
explicitement : l'interface ne déduit jamais une conformité à partir d'une
couleur ou d'un taux seul.

## Limites importantes

Les cas suivants ne sont pas pris en charge dans V1 :

- poutres continues, multi-travées, encastrement–encastrement et sections T,
  L, circulaires, variables ou doublement armées ;
- charges ponctuelles, triangulaires, partielles ou moments appliqués ;
- actions sismiques, climatiques, effort normal, précontrainte et plusieurs
  actions variables ;
- dalles bidirectionnelles, dalles sur poteaux, poinçonnement, cisaillement de
  dalle, semelles et poteaux ;
- flèche physique en millimètres, fluage, retrait, acier comprimé et contrôle
  détaillé des éléments sensibles aux déformations.

Le contrôle de déformation disponible est uniquement la méthode simplifiée
`L/d` d'EC2 §7.4.2. Une conclusion globale ne vaut que pour les vérifications
effectivement exécutées et affichées.

## Interface et exports

L'interface fournit :

- des formulaires dédiés Poutre et Dalle, avec validation locale et messages
  métier retournés par l'API ;
- un résultat responsive avec indicateur de conformité, valeurs principales,
  message synthétique et accordéons de détail ;
- un téléchargement PDF construit côté backend à partir du même input que le
  calcul affiché ;
- une API publique de calcul, sans compte utilisateur requis.

Les pages `/auth`, `/auth/login` et `/auth/register` sont volontairement
désactivées pour le moment et redirigent vers le tableau de bord. Les briques
d'authentification sont conservées mais ne font pas partie du parcours V1
actif.

## API disponible

| Méthode | Route | Rôle |
|---|---|---|
| `GET` | `/api/beam/material-catalog` | Catalogue des matériaux et sous-modules Poutre disponibles |
| `POST` | `/api/beam/calculations` | Calcul d'une poutre et résultat structuré |
| `POST` | `/api/slab/calculations` | Calcul d'une dalle et résultat structuré |
| `POST` | `/api/beam/calculations/pdf` | Génération de la note de calcul Poutre |
| `POST` | `/api/slab/calculations/pdf` | Génération de la note de calcul Dalle |

Les réponses de calcul suivent le contrat `status`, `summary`,
`verifications`, `details` et `warnings`.

## Démarrage local

Prérequis : Docker Compose, Node.js / npm et PHP 8.3+ si les commandes backend
sont exécutées hors conteneur.

```bash
# À la racine : PostgreSQL, PHP-FPM et Nginx (Laravel sur http://localhost:8000)
docker compose up -d db backend nginx

# Dans un autre terminal : Angular (http://localhost:4200)
cd frontend
npm install
npm start
```

Angular transmet les requêtes `/api` et `/sanctum` à Nginx via son proxy local.
Les détails de configuration, dont les variables d'environnement, figurent
dans [docs/development.md](docs/development.md).

## Vérifier le projet

```bash
# Backend
cd backend
php artisan test
./vendor/bin/pint --dirty --test

# Frontend
cd frontend
npm test -- --watch=false
npm run build
```

Les cas numériques de référence et les limites testées sont documentés dans
[docs/qa](docs/qa), notamment pour les [poutres](docs/qa/beam-reference-cases.md)
et les [dalles](docs/qa/slab-reference-cases.md).

## Architecture et documentation

- [Architecture des sous-modules Poutre](docs/architecture/beam-submodules.md)
- [Variables et constantes métier](docs/domain/variables-and-constants.md)
- [Références normatives](docs/normative)
- [Génération des notes PDF](docs/pdf/backend-generation.md)
- [Backlog historique](backlog.md) et [évolutions V1](workerTools_v1_epics.md)
