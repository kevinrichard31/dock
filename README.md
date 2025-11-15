# Docker Stack: PHP-FPM + Nginx + MariaDB

Configuration Docker complète avec PHP-FPM, Nginx et MariaDB.

## Structure du projet

```
dock/
├── docker-compose.yml       # Configuration des services
├── .env                     # Variables d'environnement
├── .gitignore              # Fichiers ignorés par git
├── php/
│   ├── Dockerfile          # Image PHP-FPM personnalisée
│   └── php.ini             # Configuration PHP
├── nginx/
│   ├── nginx.conf          # Configuration Nginx principale
│   └── conf.d/
│       └── default.conf    # Configuration du site
├── mariadb/
│   └── init.sql            # Scripts d'initialisation
└── app/
    └── public/
        └── index.php       # Point d'entrée PHP
```

## Installation et démarrage

### Prérequis
- Docker et Docker Compose installés

### Commandes

**Démarrer les services :**
```bash
docker-compose up -d
```

**Arrêter les services :**
```bash
docker-compose down
```

**Voir les logs :**
```bash
docker-compose logs -f [service]
```

**Accéder à un conteneur :**
```bash
docker-compose exec php-fpm sh
docker-compose exec mariadb mysql -u app_user -p app_db
```

## Accès

- **Application PHP**: http://localhost
- **MariaDB**: localhost:3306
  - Utilisateur: `app_user`
  - Mot de passe: `app_password`
  - Base de données: `app_db`

## Configuration

Modifiez les variables dans `.env` ou dans `docker-compose.yml` pour adapter :
- Ports
- Identifiants de base de données
- Timezone PHP
- Limites de fichiers

## Fichiers importants

- `docker-compose.yml` : Orchestre les 3 services (Nginx, PHP-FPM, MariaDB)
- `php/Dockerfile` : Image PHP avec extensions nécessaires
- `nginx/conf.d/default.conf` : Configuration du serveur web
- `mariadb/init.sql` : Données initiales et schéma

## Développement

Placez vos fichiers PHP dans le dossier `app/public/` pour qu'ils soient accessibles via le navigateur.

Les modifications dans `app/` sont reflétées en temps réel grâce aux volumes montés.
