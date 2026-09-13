# Développement local

## Flux Angular vers Laravel

En développement, Angular est exécuté sur l’hôte et Docker fournit PostgreSQL,
PHP-FPM et Nginx :

```text
Navigateur → Angular (http://localhost:4200) → proxy /api → Nginx (http://localhost:8000) → PHP-FPM/Laravel
```

Nginx est le point d’entrée HTTP de Laravel. PHP-FPM écoute uniquement sur le
réseau Docker (`backend:9000`) et n’est pas exposé à l’hôte.

### Démarrer les services backend

Depuis la racine du dépôt :

```bash
docker compose up -d db backend nginx
```

Le port public Nginx est `8000` et le document root est
`/var/www/html/public` dans le conteneur Nginx.

### Démarrer Angular

Dans un second terminal :

```bash
cd frontend
npm install
npm start
```

`proxy.conf.json`, déclaré dans `angular.json`, transmet les appels relatifs
commençant par `/api` vers `http://localhost:8000`. Les services Angular doivent
donc conserver des URL relatives, par exemple
`/api/beam/calculations`; aucun port Docker ne doit être codé dans un composant.

### Vérification manuelle

```bash
curl http://localhost:8000/api/beam/material-catalog
curl http://localhost:4200/api/beam/material-catalog
```

Le premier appel vérifie Nginx/Laravel directement ; le second vérifie le proxy
Angular. Laravel autorise explicitement l’origine de développement
`http://localhost:4200` via `ALLOWED_FRONTEND_ORIGIN`; il n’utilise pas `*` et
aucune authentification ou option `withCredentials` n’est ajoutée par cette
configuration.
