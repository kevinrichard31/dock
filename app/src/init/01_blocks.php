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
use App\Modules\Block\Block;
use App\Modules\Block\BlockChain;
use App\Modules\Wallet\Wallet;
use App\Lib\Logger;
use App\Lib\Crypto;
use App\Lib\ValidatorSignatureHelper;
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
            
            // Chemin pour sauvegarder les clés
            $keysPath = __DIR__ . '/../../keys';

            // Générer et sauvegarder les clés si elles n'existent pas
            $keys = Crypto::loadKeys($keysPath);
            if (empty($keys)) {
                Logger::info('Generating new system keys...');
                $keys = Crypto::generateKeys();
                Crypto::saveKeys($keys, $keysPath);
                Logger::success('System keys generated and saved.', ['path' => $keysPath]);
            } else {
                Logger::info('System keys loaded from file.');
            }

            $db = Database::getInstance()->getConnection();
            
            // Créer les données d'allocation dans le bloc Genesis
            $allocationData = [
                'type' => 'genesis_allocation',
                'description' => 'Initial coin allocation',
                'total_supply' => 1000000,
                'public_key' => $keys['public'],
                'allocations' => [
                    [
                        'recipient' => $keys['public'],
                        'amount' => 1000000,
                        'type' => 'system_reserve'
                    ]
                ]
            ];

            // Sign the allocation data
            $dataToSign = json_encode([
                'type' => $allocationData['type'],
                'public_key' => $allocationData['public_key'],
                'total_supply' => $allocationData['total_supply']
            ]);
            
            $signature = Crypto::sign($dataToSign, $keys['private']);
            $allocationData['signature'] = $signature;
            
            // Créer le bloc de départ avec les données d'allocation (Proof of Stake)
            $genesisBlock = new Block(0, '0', [$allocationData]);
            
            // Sauvegarder le bloc
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
