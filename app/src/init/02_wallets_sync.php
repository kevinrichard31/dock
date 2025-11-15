<?php

/**
 * 02_wallets_sync.php
 * ÉTAPE 2: Synchroniser les wallets avec la blockchain
 * 
 * Cette étape:
 * - Parcourt tous les blocs de la blockchain
 * - Extrait les allocations et transactions
 * - Crée/met à jour les wallets correspondants
 * - Synchronise les soldes avec la blockchain
 */

namespace App\Init;

use App\Config\Database;
use App\Lib\Logger;
use PDO;

class InitWalletsSync
{
    public static function execute(): void
    {
        Logger::info('=== ÉTAPE 2: Synchronisation des Wallets avec la Blockchain ===');

        try {
            $db = Database::getInstance()->getConnection();

            // Récupérer tous les blocs
            $blocksSql = "SELECT * FROM blocks ORDER BY index_num ASC";
            $blocksStmt = $db->query($blocksSql);
            $blocks = $blocksStmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($blocks)) {
                Logger::warning('No blocks found in blockchain');
                return;
            }

            Logger::info('Processing blocks', ['count' => count($blocks)]);

            // Wallet tracking
            $walletBalances = [];

            // Traiter chaque bloc
            foreach ($blocks as $block) {
                $blockData = json_decode($block['data'], true);

                if (is_array($blockData)) {
                    foreach ($blockData as $transaction) {
                        // Traiter les allocations du Genesis Block
                        if ($transaction['type'] === 'genesis_allocation' && isset($transaction['allocations'])) {
                            foreach ($transaction['allocations'] as $allocation) {
                                $recipient = $allocation['recipient'];
                                $amount = $allocation['amount'];

                                if (!isset($walletBalances[$recipient])) {
                                    $walletBalances[$recipient] = 0;
                                }
                                $walletBalances[$recipient] += $amount;

                                Logger::info('Genesis allocation processed', [
                                    'recipient' => $recipient,
                                    'amount' => $amount
                                ]);
                            }
                        }

                        // Traiter les transactions normales (from/to) - type 'transaction' ou 'transfer'
                        if (($transaction['type'] === 'transaction' || $transaction['type'] === 'transfer') && isset($transaction['from'], $transaction['to'], $transaction['amount'])) {
                            $from = $transaction['from'];
                            $to = $transaction['to'];
                            $amount = $transaction['amount'];

                            if (!isset($walletBalances[$from])) {
                                $walletBalances[$from] = 0;
                            }
                            if (!isset($walletBalances[$to])) {
                                $walletBalances[$to] = 0;
                            }

                            $walletBalances[$from] -= $amount;
                            $walletBalances[$to] += $amount;

                            Logger::info('Transaction processed', [
                                'type' => $transaction['type'],
                                'from' => substr($from, 0, 20) . '...',
                                'to' => substr($to, 0, 20) . '...',
                                'amount' => $amount
                            ]);
                        }
                    }
                }
            }

            // Créer/mettre à jour les wallets avec les soldes synchronisés
            Logger::info('Creating/updating wallets', ['count' => count($walletBalances)]);

            foreach ($walletBalances as $publicKey => $balance) {
                // Chercher si le wallet existe
                $checkSql = "SELECT id FROM wallets WHERE public_key = :public_key LIMIT 1";
                $checkStmt = $db->prepare($checkSql);
                $checkStmt->execute([':public_key' => $publicKey]);
                $existingWallet = $checkStmt->fetch(PDO::FETCH_ASSOC);

                if ($existingWallet) {
                    // Mettre à jour le solde
                    $updateSql = "UPDATE wallets SET balance = :balance WHERE public_key = :public_key";
                    $updateStmt = $db->prepare($updateSql);
                    $updateStmt->execute([
                        ':balance' => $balance,
                        ':public_key' => $publicKey
                    ]);
                    Logger::info('Wallet updated', [
                        'public_key' => substr($publicKey, 0, 10) . '...',
                        'balance' => $balance
                    ]);
                } else {
                    // Créer un nouvel utilisateur et wallet
                    $userName = 'User_' . substr($publicKey, 0, 8);
                    $userEmail = substr($publicKey, 0, 20) . '@wallet.local';

                    // Insérer l'utilisateur
                    $userSql = "INSERT INTO users (name, email) VALUES (:name, :email)";
                    $userStmt = $db->prepare($userSql);
                    $userStmt->execute([
                        ':name' => $userName,
                        ':email' => $userEmail
                    ]);
                    $userId = $db->lastInsertId();

                    // Insérer le wallet
                    $walletSql = "INSERT INTO wallets (user_id, public_key, private_key, balance) 
                                  VALUES (:user_id, :public_key, :private_key, :balance)";
                    $walletStmt = $db->prepare($walletSql);
                    $walletStmt->execute([
                        ':user_id' => $userId,
                        ':public_key' => $publicKey,
                        ':private_key' => 'N/A',
                        ':balance' => $balance
                    ]);

                    Logger::info('Wallet created', [
                        'user_id' => $userId,
                        'public_key' => substr($publicKey, 0, 10) . '...',
                        'balance' => $balance
                    ]);
                }
            }

            Logger::success('Wallets synchronized successfully', [
                'total_wallets' => count($walletBalances)
            ]);

        } catch (\Exception $e) {
            Logger::error('Failed to synchronize wallets', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
