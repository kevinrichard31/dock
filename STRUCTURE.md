# Structure complète du projet Blockchain

```
dock/
├── docker-compose.yml                    # Orchestration Docker
├── README.md                             # Documentation principale
├── BLOCKCHAIN_DOCUMENTATION.md           # Documentation détaillée blockchain
├── startup.sh                            # Script de démarrage (Linux/Mac)
├── startup.ps1                           # Script de démarrage (Windows)
│
├── nginx/
│   ├── nginx.conf
│   └── conf.d/
│       └── default.conf
│
├── php/
│   ├── Dockerfile
│   └── php.ini
│
├── mariadb/
│   └── init.sql                          # ✓ MODIFIÉ: Tables blockchain
│
└── app/                                  # Répertoire principal de l'application
    ├── init.php                          # ✓ NOUVEAU: Script bootstrap
    ├── composer.json                     # ✓ NOUVEAU: Autoload PSR-4
    │
    ├── public/
    │   └── index.php                     # ✓ NOUVEAU: Exemple d'utilisation
    │
    └── src/
        ├── config/
        │   └── Database.php              # ✓ NOUVEAU: Singleton DB avec PDO
        │
        ├── lib/
        │   ├── Crypto.php                # ✓ NOUVEAU: Cryptographie (SHA-256, etc.)
        │   └── Logger.php                # ✓ NOUVEAU: Logger structuré
        │
        ├── modules/
        │   │
        │   ├── block/
        │   │   ├── Block.php             # ✓ NOUVEAU: Classe Block avec PoW
        │   │   └── BlockChain.php        # ✓ NOUVEAU: Gestionnaire blockchain
        │   │
        │   └── wallet/
        │       ├── Wallet.php            # ✓ NOUVEAU: Classe Wallet
        │       └── WalletManager.php     # ✓ NOUVEAU: Gestionnaire wallets
        │
        └── init/
            ├── 01_blocks.php             # ✓ NOUVEAU: Étape 1 - Genesis Block
            ├── 02_wallets.php            # ✓ NOUVEAU: Étape 2 - Création wallets
            └── 03_transactions.php       # ✓ NOUVEAU: Étape 3 - Transactions
```

## Nouveaux fichiers créés (10 fichiers)

### Modules principaux
1. **app/src/modules/block/Block.php** - Classe représentant un bloc avec PoW
2. **app/src/modules/block/BlockChain.php** - Gestionnaire de la blockchain
3. **app/src/modules/wallet/Wallet.php** - Classe portefeuille avec clés et adresse
4. **app/src/modules/wallet/WalletManager.php** - Gestionnaire des portefeuilles

### Configuration et utilitaires
5. **app/src/config/Database.php** - Singleton PDO pour la base de données
6. **app/src/lib/Crypto.php** - Cryptographie (SHA-256, adresses, Merkle)
7. **app/src/lib/Logger.php** - Logger structuré avec niveaux

### Initialisation (stratégie par étapes)
8. **app/src/init/01_blocks.php** - Crée le Genesis Block
9. **app/src/init/02_wallets.php** - Crée les wallets synchronisés
10. **app/src/init/03_transactions.php** - Crée les transactions et blocs

### Scripts d'exécution
11. **app/init.php** - Bootstrap qui exécute tous les stages
12. **app/public/index.php** - Exemple d'utilisation

### Configuration
13. **app/composer.json** - Autoload PSR-4
14. **mariadb/init.sql** - Tables blockchain (modifié)

### Scripts d'aide
15. **startup.sh** - Script de démarrage Linux/Mac
16. **startup.ps1** - Script de démarrage Windows

## Fonctionnalités

### ✓ Module Block
- Création de blocs avec index, hash, timestamp
- Proof of Work (PoW) avec difficulté configurable
- Arbre de Merkle pour les transactions
- Validation d'intégrité
- Persistance en base de données

### ✓ Module Wallet
- Génération de paires de clés (publique/privée)
- Adresses uniques au format BCH_xxxxx
- Gestion des soldes
- Création et suivi des transactions
- Synchronisation avec la blockchain

### ✓ Système d'initialisation
- Étape 1: Genesis Block (bloc de départ)
- Étape 2: Wallets pour les utilisateurs
- Étape 3: Transactions de démonstration
- Chaque étape isolée et indépendante
- Logging complet de chaque opération

### ✓ Cryptographie
- SHA-256 simple et double hash
- Génération d'adresses blockchain
- Calcul de Merkle root
- Aléatoire cryptographique

### ✓ Base de données
- Tables: users, blocks, wallets, transactions
- Relations et clés étrangères
- Indexes pour performances
- Transactions ACID

## Flux d'exécution

```
┌─────────────────────────────────────┐
│  docker-compose up -d               │
│  (Nginx, PHP-FPM, MariaDB)          │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│  php /var/www/html/init.php         │
└──────────────┬──────────────────────┘
               │
        ┌──────┴─────────┐
        │                │
┌───────▼────────┐  ┌────▼──────────┐
│ 01_blocks.php  │  │ initDB        │
│ Genesis Block  │  │ Connexion     │
│ PoW Mining     │  │               │
└───────┬────────┘  └────┬──────────┘
        │                │
┌───────▼────────────────▼──────┐
│ 02_wallets.php                 │
│ Pour chaque user:              │
│ - Générer clés (pub/priv)     │
│ - Créer adresse BCH_xxxxx     │
│ - Balance = 100               │
└───────┬────────────────────────┘
        │
┌───────▼────────────────────────┐
│ 03_transactions.php            │
│ Transactions de démo:          │
│ - Alice → Bob: 10              │
│ - Bob → Charlie: 5             │
│ Mine bloc #1                   │
└───────┬────────────────────────┘
        │
┌───────▼────────────────────────┐
│ ✓ Système prêt                 │
│   - Blockchain active          │
│   - Wallets synchronisés       │
│   - Transactions enregistrées   │
└────────────────────────────────┘
```

## Stratégie d'initialisation

La clé du système est que **chaque fichier a une responsabilité unique**:

1. **01_blocks.php** crée UNIQUEMENT le bloc de départ
   - Indépendant des utilisateurs
   - Fondation de la blockchain

2. **02_wallets.php** crée les wallets EN FONCTION des utilisateurs
   - Synchronisé avec le Genesis Block
   - Accède à la blockchain déjà créée

3. **03_transactions.php** crée des transactions EN FONCTION des wallets
   - Utilise les wallets existants
   - Ajoute des blocs à la blockchain

Cette approche garantit:
- ✓ Pas de dépendances circulaires
- ✓ Chaque étape peut être rejouée
- ✓ Logging et débogage facile
- ✓ Extensibilité (ajouter des étapes facilement)
