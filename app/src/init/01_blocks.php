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

use App\Modules\Block\BlockChain;
use App\Lib\Logger;

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

            Logger::info('Creating Genesis Block...');
            
            // Créer et miner le bloc de départ
            $genesisBlock = BlockChain::createGenesisBlock();
            $genesisBlock->mineBlock();
            $genesisBlock->save();

            Logger::success('Genesis Block created successfully', [
                'hash' => $genesisBlock->getHash(),
                'nonce' => $genesisBlock->getNonce(),
                'timestamp' => $genesisBlock->getTimestamp()
            ]);

        } catch (\Exception $e) {
            Logger::error('Failed to initialize blockchain', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
