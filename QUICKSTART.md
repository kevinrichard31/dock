# Quick Start Guide - Blockchain System

## 🚀 Démarrage rapide en 3 étapes

### Étape 1: Démarrer Docker Compose
```bash
cd C:\Users\kevin\Desktop\dock
docker-compose up -d
```

Cela démarre:
- ✓ Nginx (port 81)
- ✓ PHP-FPM (port 9000)
- ✓ MariaDB (port 3306)

### Étape 2: Installer les dépendances
```bash
docker-compose exec php-fpm composer install --working-dir=/var/www/html
```

Cela crée l'autoload PSR-4.

### Étape 3: Initialiser la blockchain
```bash
docker-compose exec php-fpm php /var/www/html/init.php
```

Cela exécute:
1. Genesis Block (bloc de départ)
2. Création des wallets (Alice, Bob, Charlie)
3. Transactions de démonstration

---

## 📊 Accès après initialisation

### Application Web
```
http://localhost:81
```

### API REST
```
GET  http://localhost:81/api/blockchain
GET  http://localhost:81/api/blocks
GET  http://localhost:81/api/wallets
GET  http://localhost:81/api/stats
```

### Base de données MariaDB
```
Host: localhost
Port: 3306
User: app_user
Password: app_password
Database: app_db
```

Connexion via MySQL client:
```bash
docker-compose exec mariadb mysql -u app_user -p app_db
```

---

## 📁 Structure des fichiers

```
dock/
├── app/src/
│   ├── modules/
│   │   ├── block/      (Blockchain)
│   │   └── wallet/     (Wallets)
│   ├── init/           (01_blocks, 02_wallets, 03_transactions)
│   ├── config/         (Database connection)
│   ├── lib/            (Crypto, Logger)
│   ├── init.php        (Bootstrap)
│   ├── composer.json   (Autoload)
│   └── public/
│       ├── index.php   (Exemple)
│       └── api.php     (API REST)
│
├── BLOCKCHAIN_DOCUMENTATION.md   (Documentation complète)
├── STRUCTURE.md                   (Vue d'ensemble)
└── SUMMARY.md                     (Résumé)
```

---

## 🔄 Processus d'initialisation

```
init.php
  ├─► 01_blocks.php
  │    ✓ Crée Genesis Block (#0)
  │    ✓ Mine avec PoW
  │
  ├─► 02_wallets.php
  │    ✓ Pour chaque user: Alice, Bob, Charlie
  │    ✓ Génère adresse BCH_xxxxx
  │    ✓ Balance = 100 chacun
  │
  └─► 03_transactions.php
       ✓ Alice → Bob: 10
       ✓ Bob → Charlie: 5
       ✓ Mine bloc #1
```

---

## 💻 Commandes utiles

### Logs du système
```bash
docker-compose logs -f php-fpm
```

### Accéder au shell PHP
```bash
docker-compose exec php-fpm sh
```

### Accéder à MariaDB
```bash
docker-compose exec mariadb mysql -u app_user -p app_db
```

### Voir les tables
```sql
SHOW TABLES;
SELECT * FROM blocks;
SELECT * FROM wallets;
SELECT * FROM transactions;
```

### Arrêter les services
```bash
docker-compose down
```

---

## 🔍 Vérification après initialisation

### Via la base de données
```sql
-- Voir les blocs
SELECT index_num, hash, timestamp FROM blocks;

-- Voir les wallets
SELECT user_id, address, balance FROM wallets;

-- Voir les transactions
SELECT from_address, to_address, amount FROM transactions;
```

### Via l'API
```bash
# Stats globales
curl http://localhost:81/api/stats

# Détails blockchain
curl http://localhost:81/api/blockchain

# Tous les blocs
curl http://localhost:81/api/blocks

# Tous les wallets
curl http://localhost:81/api/wallets
```

---

## 🎯 Fonctionnalités principales

✓ **Blockchain** - Blocs minés avec Proof of Work
✓ **Wallets** - Adresses BCH_, clés publique/privée
✓ **Transactions** - Immuables et enregistrées
✓ **Cryptographie** - SHA-256, Merkle root
✓ **BD Synchronisée** - Blocs, wallets, transactions
✓ **Initialisation** - Étapes séquentielles et isolées
✓ **Logging** - Chaque opération loggée
✓ **API REST** - Endpoints de consultation

---

## 📖 Pour en savoir plus

Consulter les fichiers de documentation:

- `BLOCKCHAIN_DOCUMENTATION.md` - Documentation complète (concepts, tables, exemples)
- `STRUCTURE.md` - Vue d'ensemble de l'architecture
- `SUMMARY.md` - Résumé des implémentations

---

## ⚡ Troubleshooting

### Docker Compose ne démarre pas
```bash
docker-compose ps
docker-compose up -d --no-deps --build
```

### Erreur de connexion BD
```bash
docker-compose logs mariadb
# Attendre quelques secondes après le démarrage
```

### init.php ne s'exécute pas
```bash
docker-compose exec php-fpm php /var/www/html/init.php
# Vérifier les logs
docker-compose logs php-fpm
```

### Port 81 déjà utilisé
Modifier dans `docker-compose.yml`:
```yaml
ports:
  - "8080:80"  # Utiliser 8080 à la place
```

---

**✨ Vous êtes prêt à explorer le système blockchain!**
