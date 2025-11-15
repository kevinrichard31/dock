# Système Blockchain - Documentation

## 📋 Vue d'ensemble

Ce projet implémente un système blockchain complet avec PHP, Nginx et MariaDB.

La structure d'initialisation utilise une **stratégie par étapes** où chaque composant a sa propre logique isolée et s'exécute dans un ordre spécifique.

## 🏗️ Architecture

### Structure du projet

```
app/
├── src/
│   ├── modules/
│   │   ├── block/           # Module Blockchain
│   │   │   ├── Block.php           # Classe représentant un bloc
│   │   │   └── BlockChain.php      # Gestionnaire de la chaîne
│   │   └── wallet/          # Module Portefeuille
│   │       ├── Wallet.php          # Classe du portefeuille
│   │       └── WalletManager.php   # Gestionnaire des portefeuilles
│   ├── init/                # Scripts d'initialisation
│   │   ├── 01_blocks.php           # Étape 1: Genesis Block
│   │   ├── 02_wallets.php          # Étape 2: Création des wallets
│   │   └── 03_transactions.php     # Étape 3: Transactions
│   ├── config/
│   │   └── Database.php            # Configuration de la base de données
│   └── lib/
│       ├── Crypto.php              # Fonctions cryptographiques
│       └── Logger.php              # Logging et debugging
├── init.php                 # Script de bootstrap principal
├── composer.json            # Configuration Composer (autoload)
└── public/
    └── index.php            # Exemple d'utilisation
```

## 🚀 Initialisation du système

### Étapes d'initialisation (dans l'ordre)

#### **01_blocks.php - Création du bloc de départ**
- Crée le **Genesis Block** (bloc #0)
- Applique l'algorithme Proof of Work (PoW)
- Enregistre le bloc dans la base de données
- C'est la base de toute la blockchain

```
Genesis Block
├─ Index: 0
├─ Hash: 256 bits
├─ Previous Hash: '0'
├─ Merkle Root: hash de toutes les transactions
├─ Nonce: déterminé par le mining
└─ Difficulty: 4
```

#### **02_wallets.php - Création des portefeuilles**
- Lit tous les utilisateurs de la base de données
- Crée un portefeuille pour chaque utilisateur
- Génère les paires de clés (publique/privée)
- Génère une adresse unique (BCH_xxxxx...)
- Initialise le solde à 100 unités
- **Synchronisé avec le bloc de départ**

```
Wallet (Alice)
├─ Address: BCH_a1b2c3d4e5...
├─ Public Key: hash(private_key)
├─ Private Key: hash(seed + timestamp)
└─ Balance: 100
```

#### **03_transactions.php - Transactions et blocs supplémentaires**
- Crée des transactions de démonstration entre wallets
- Ajoute les transactions à un nouveau bloc
- Mine le bloc avec PoW
- Met à jour les soldes des wallets
- Remplit les tables de données

```
Bloc #1
├─ Transactions: [Alice → Bob: 10, Bob → Charlie: 5]
├─ Mining: PoW appliqué
└─ Enregistré dans la blockchain
```

## 📊 Tables de base de données

### `users`
Utilisateurs du système
```sql
id, username, email, password_hash, created_at, updated_at
```

### `blocks`
Blocs de la blockchain
```sql
id, index_num, hash, previous_hash, timestamp, merkle_root, nonce, difficulty, data
```

### `wallets`
Portefeuilles synchronisés avec la blockchain
```sql
id, user_id, address, public_key, private_key, balance, created_at, updated_at
```

### `transactions`
Transactions dans les blocs
```sql
id, from_address, to_address, amount, hash, block_index, timestamp, created_at
```

## 🔐 Cryptographie

### Classes de soutien

**Crypto.php** - Fonctions cryptographiques
```php
- hash($data)                    // SHA-256
- doubleHash($data)              // SHA-256 double (Bitcoin-style)
- randomHex($length)             // Hex aléatoire
- generateAddress($data)         // Génère adresse BCH_xxxxx
- calculateMerkleRoot($txs)      // Arbre de Merkle
```

**Logger.php** - Logging structuré
```php
- info($message, $context)       // Logs informatifs
- success($message, $context)    // Logs de succès
- error($message, $context)      // Logs d'erreur
- warning($message, $context)    // Logs d'avertissement
```

## 🎯 Flux d'exécution complet

```
1. PHP container démarre
   ↓
2. init.php s'exécute
   ├─ Test de connexion DB
   ├─ InitBlocks::execute()
   │  ├─ Vérification si blockchain existe
   │  ├─ Création du Genesis Block
   │  └─ Mining du bloc
   ├─ InitWallets::execute()
   │  ├─ Récupération des utilisateurs
   │  ├─ Création d'un wallet par utilisateur
   │  ├─ Génération des clés
   │  └─ Initialisation des soldes (100 chacun)
   └─ InitTransactions::execute()
      ├─ Récupération des wallets
      ├─ Création de transactions de démo
      ├─ Mining d'un nouveau bloc
      └─ Synchronisation des soldes
   ↓
3. Système prêt à l'emploi
   ├─ Blockchain opérationnelle
   ├─ Wallets synchronisés
   └─ Transactions enregistrées
```

## 💡 Conceptes clés

### Bloc
Un bloc contient:
- Un indice dans la chaîne
- Le hash du bloc précédent (liaison)
- Une liste de transactions
- Un timestamp
- Une racine de Merkle
- Un nonce (déterminé par PoW)

### Wallet
Un wallet contient:
- Une adresse unique (BCH_xxxxx...)
- Une paire de clés (public/privé)
- Un solde
- Historique des transactions

### Transaction
Une transaction:
- Transfère des fonds
- De une adresse à une autre
- Enregistrée dans un bloc
- Immuable une fois minée

### Proof of Work (PoW)
- Difficulté: 4 (nombre de zéros au début du hash)
- Le nonce augmente jusqu'à trouver un hash valide
- Sécurise la blockchain contre les modifications

## 🔄 Synchronisation

Le système synchronise:
1. **Blockchain** → Blocs minés avec PoW
2. **Wallets** → Créés après le Genesis Block
3. **Transactions** → Remplissent les blocs
4. **Soldes** → Mis à jour en temps réel

La synchronisation garantit:
- Les wallets existent avant les transactions
- Les transactions correspondent aux blocs
- Les soldes restent cohérents
- L'intégrité de la blockchain

## 🚢 Docker - Commandes d'utilisation

```bash
# Démarrer les services
docker-compose up -d

# Afficher les logs
docker-compose logs -f php-fpm

# Accéder au conteneur PHP
docker-compose exec php-fpm sh

# Exécuter init.php
docker-compose exec php-fpm php /var/www/html/init.php

# Accéder à MariaDB
docker-compose exec mariadb mysql -u app_user -p app_db

# Arrêter les services
docker-compose down
```

## 📝 Exemple d'utilisation PHP

```php
// Charger les modules
use App\Modules\Block\BlockChain;
use App\Modules\Wallet\WalletManager;

// Accéder à la blockchain
$blockchain = new BlockChain();
echo $blockchain->getLength(); // Nombre de blocs

// Accéder aux portefeuilles
$wallets = WalletManager::getAllWallets();
foreach ($wallets as $wallet) {
    echo $wallet['address'] . ': ' . $wallet['balance'];
}

// Effectuer une transaction
WalletManager::transfer('BCH_from', 'BCH_to', 10);
```

## 🎓 Apprentissage

Ce système démontre:
- ✓ Architecture blockchain
- ✓ Cryptographie SHA-256
- ✓ Proof of Work
- ✓ Gestion de wallets
- ✓ Transactions immuables
- ✓ Structure de base de données
- ✓ Synchronisation des données
- ✓ Pattern de conception (Singleton, Factory, etc.)
