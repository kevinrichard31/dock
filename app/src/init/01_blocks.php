<?php

/**
 * 01_blocks.php
 * ÉTAPE 1: Créer et initialiser la blockchain avec le bloc de départ (Genesis Block)
 * 
 * Cette étape s'exécute en premier et crée:
 * - Le bloc de départ (Genesis Block)
 * - La chaîne de blocs initiale
 */

namespace App\Init;

use App\Config\Database;
use App\Modules\Block\BlockChain;
use App\Modules\Wallet\Wallet;
use App\Lib\Logger;
use PDO;

class InitBlocks
{
    public static function execute(): void
    {
        Logger::info('=== ÉTAPE 1: Initialisation de la Blockchain ===');

        try {
            // Vérifier si le genesis block existe déjà
            $blockchain = new BlockChain();
            
            if ($blockchain->getLength() > 0) {
                Logger::warning('Blockchain already initialized', [
                    'blocks' => $blockchain->getLength()
                ]);
                return;
            }

            Logger::info('Creating Genesis Block with system wallet...');
            
            $db = Database::getInstance()->getConnection();
            
            // Créer l'utilisateur système s'il n'existe pas
            $checkUserSql = "SELECT id FROM users WHERE name = 'SYSTEM' LIMIT 1";
            $checkUserStmt = $db->query($checkUserSql);
            $systemUser = $checkUserStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$systemUser) {
                $createUserSql = "INSERT INTO users (name, email) VALUES ('SYSTEM', 'system@blockchain.local')";
                $db->exec($createUserSql);
                $systemUserId = $db->lastInsertId();
                Logger::info('System user created', ['id' => $systemUserId]);
            } else {
                $systemUserId = $systemUser['id'];
            }
            
            // Créer les données d'allocation dans le bloc Genesis
            $allocationData = [
                'type' => 'genesis_allocation',
                'description' => 'Initial coin allocation',
                'total_supply' => 1000000,
                'allocations' => [
                    [
                        'recipient' => 'SYSTEM',
                        'amount' => 1000000,
                        'type' => 'system_reserve'
                    ]
                ]
            ];
            
            // Créer le bloc de départ avec les données d'allocation (Proof of Stake)
            $genesisBlock = BlockChain::createGenesisBlock();
            // Ajouter les données d'allocation au bloc
            $genesisBlock->save();

            Logger::success('Genesis Block created successfully', [
                'hash' => $genesisBlock->getHash(),
                'timestamp' => $genesisBlock->getTimestamp(),
                'consensus' => 'Proof of Stake',
                'total_supply' => 1000000,
                'allocation' => $allocationData
            ]);

        } catch (\Exception $e) {
            Logger::error('Failed to initialize blockchain', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
