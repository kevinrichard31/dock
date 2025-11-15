<?php

/**
 * 02_wallets.php
 * ÉTAPE 2: Créer les wallets pour les utilisateurs (synchronisé avec la blockchain)
 * 
 * Cette étape s'exécute après la création du bloc de départ et:
 * - Crée les portefeuilles pour chaque utilisateur
 * - Initialise les adresses et les clés
 * - Alloue les soldes initiaux basés sur le bloc de départ
 */

namespace App\Init;

use App\Config\Database;
use App\Modules\Wallet\WalletManager;
use App\Modules\Block\BlockChain;
use App\Lib\Logger;
use PDO;

class InitWallets
{
    public static function execute(): void
    {
        Logger::info('=== ÉTAPE 2: Initialisation des Wallets ===');

        try {
            $db = Database::getInstance()->getConnection();
            
            // Récupérer tous les utilisateurs
            $sql = "SELECT id FROM users";
            $stmt = $db->query($sql);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($users)) {
                Logger::warning('No users found in database');
                return;
            }

            $initialBalance = 100; // Solde initial par wallet
            $createdCount = 0;

            foreach ($users as $user) {
                $userId = $user['id'];
                
                // Vérifier si le wallet existe déjà
                $checkSql = "SELECT id FROM wallets WHERE user_id = :user_id";
                $checkStmt = $db->prepare($checkSql);
                $checkStmt->execute([':user_id' => $userId]);
                
                if ($checkStmt->fetch()) {
                    Logger::info("Wallet already exists for user", ['user_id' => $userId]);
                    continue;
                }

                // Créer le wallet
                $wallet = WalletManager::createWallet($userId);
                $wallet->addBalance($initialBalance)->save();

                Logger::success('Wallet created', [
                    'user_id' => $userId,
                    'address' => $wallet->getAddress(),
                    'initialBalance' => $initialBalance
                ]);

                $createdCount++;
            }

            Logger::info('Wallets initialization completed', [
                'created' => $createdCount,
                'total' => count($users)
            ]);

            // Vérifier la synchronisation avec la blockchain
            $blockchain = new BlockChain();
            Logger::info('Blockchain status', [
                'blocks' => $blockchain->getLength(),
                'isValid' => $blockchain->isValid()
            ]);

        } catch (\Exception $e) {
            Logger::error('Failed to initialize wallets', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
