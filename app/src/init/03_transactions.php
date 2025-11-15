<?php

/**
 * 03_transactions.php
 * ÉTAPE 3: Créer les transactions et les ajouter aux blocs
 * 
 * Cette étape s'exécute après la création des wallets et:
 * - Exécute des transactions de démonstration
 * - Ajoute les transactions à de nouveaux blocs
 * - Mine les blocs avec la preuve de travail
 * - Synchronise les soldes des wallets
 */

namespace App\Init;

use App\Config\Database;
use App\Modules\Wallet\WalletManager;
use App\Modules\Block\BlockChain;
use App\Lib\Logger;
use PDO;

class InitTransactions
{
    public static function execute(): void
    {
        Logger::info('=== ÉTAPE 3: Initialisation des Transactions ===');

        try {
            $db = Database::getInstance()->getConnection();

            // Récupérer tous les wallets
            $sql = "SELECT id, address FROM wallets LIMIT 10";
            $stmt = $db->query($sql);
            $wallets = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($wallets) < 2) {
                Logger::warning('Not enough wallets to create transactions');
                return;
            }

            $blockchain = new BlockChain();
            $transactions = [];
            $transactionCount = 0;

            // Créer des transactions de démonstration
            for ($i = 0; $i < min(3, count($wallets) - 1); $i++) {
                $fromWallet = WalletManager::getWalletByAddress($wallets[$i]['address']);
                $toWallet = WalletManager::getWalletByAddress($wallets[$i + 1]['address']);

                if (!$fromWallet || !$toWallet) {
                    continue;
                }

                $amount = 10;
                
                // Créer la transaction
                $transaction = $fromWallet->createTransaction($toWallet->getAddress(), $amount);
                
                if ($transaction) {
                    $transactions[] = $transaction;
                    $toWallet->addBalance($amount);
                    
                    $fromWallet->save();
                    $toWallet->save();

                    Logger::success('Transaction created', [
                        'from' => substr($fromWallet->getAddress(), 0, 10),
                        'to' => substr($toWallet->getAddress(), 0, 10),
                        'amount' => $amount
                    ]);

                    $transactionCount++;
                }
            }

            // Ajouter les transactions à un nouveau bloc
            if (!empty($transactions)) {
                Logger::info('Mining new block with transactions...', [
                    'transactionCount' => count($transactions)
                ]);

                $block = $blockchain->addBlock($transactions);

                Logger::success('New block added to blockchain', [
                    'blockIndex' => $block->getIndex(),
                    'hash' => $block->getHash(),
                    'transactions' => count($block->getTransactions()),
                    'nonce' => $block->getNonce()
                ]);
            }

            Logger::info('Transactions initialization completed', [
                'transactionsCreated' => $transactionCount,
                'blocksInChain' => $blockchain->getLength()
            ]);

        } catch (\Exception $e) {
            Logger::error('Failed to initialize transactions', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
