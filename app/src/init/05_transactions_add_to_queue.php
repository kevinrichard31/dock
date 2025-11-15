<?php

/**
 * 05_transactions_add_to_queue.php
 * ÉTAPE 5: Extraire les transactions de la blockchain et les ajouter à la queue
 * 
 * Cette étape:
 * - Parcourt tous les blocs de la blockchain
 * - Extrait toutes les transactions
 * - Les ajoute à la table transactions
 * - Les ajoute à la queue pour traitement
 */

namespace App\Init;

use App\Config\Database;
use App\Modules\Transaction\TransactionManager;
use App\Modules\Queue\QueueManager;
use App\Lib\Logger;
use PDO;

class InitTransactionsAddToQueue
{
    public static function execute(): void
    {
        Logger::info('=== ÉTAPE 5: Extraction et Ajout des Transactions à la Queue ===');

        try {
            $db = Database::getInstance()->getConnection();

            // ÉTAPE 1: Récupérer tous les blocs et extraire les transactions
            $blocksSql = "SELECT * FROM blocks ORDER BY index_num ASC";
            $blocksStmt = $db->query($blocksSql);
            $blocks = $blocksStmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($blocks)) {
                Logger::warning('No blocks found in blockchain');
                return;
            }

            Logger::info('Processing blocks for transactions extraction and queue', ['count' => count($blocks)]);

            $extractedCount = 0;
            $queuedCount = 0;
            $skippedCount = 0;

            // Traiter chaque bloc
            foreach ($blocks as $block) {
                $blockIndex = (int)$block['index_num'];
                $blockData = json_decode($block['data'], true);
                $timestamp = (int)$block['timestamp'];

                if (is_array($blockData)) {
                    foreach ($blockData as $item) {
                        // Extraire les transactions standards
                        if ($item['type'] === 'transaction' && isset($item['from']) && isset($item['to'])) {
                            $fromAddress = $item['from'];
                            $toAddress = $item['to'];
                            $amount = (float)$item['amount'];
                            
                            // Générer le hash de la transaction si non présent
                            $hash = $item['hash'] ?? self::hashTransaction(
                                $fromAddress,
                                $toAddress,
                                $amount,
                                $timestamp
                            );

                            // Vérifier si la transaction existe déjà
                            if (TransactionManager::transactionExists($hash)) {
                                $skippedCount++;
                                Logger::debug('Transaction already exists', [
                                    'hash' => substr($hash, 0, 20) . '...'
                                ]);
                                continue;
                            }

                            // Créer la transaction
                            $transaction = TransactionManager::createTransaction(
                                $fromAddress,
                                $toAddress,
                                $amount,
                                $hash,
                                $timestamp,
                                $blockIndex
                            );

                            if ($transaction) {
                                $extractedCount++;
                                $transactionId = $transaction->getId();
                                
                                Logger::info('Transaction extracted', [
                                    'from' => substr($fromAddress, 0, 20) . '...',
                                    'to' => substr($toAddress, 0, 20) . '...',
                                    'amount' => $amount,
                                    'hash' => substr($hash, 0, 20) . '...',
                                    'block_index' => $blockIndex
                                ]);

                                // Ajouter à la queue
                                $queueItem = QueueManager::addToQueue(
                                    $transactionId,
                                    $fromAddress,
                                    $toAddress,
                                    $amount,
                                    $hash,
                                    $timestamp,
                                    $blockIndex
                                );

                                if ($queueItem) {
                                    $queuedCount++;
                                    Logger::info('Transaction added to queue', [
                                        'transaction_id' => $transactionId,
                                        'hash' => substr($hash, 0, 20) . '...'
                                    ]);
                                }
                            }
                        }

                        // Extraire les transactions depuis les allocations genesis
                        if ($item['type'] === 'genesis_allocation' && isset($item['allocations'])) {
                            foreach ($item['allocations'] as $allocation) {
                                $fromAddress = $item['public_key'] ?? 'genesis';
                                $toAddress = $allocation['recipient'];
                                $amount = (float)$allocation['amount'];
                                
                                $hash = self::hashTransaction(
                                    $fromAddress,
                                    $toAddress,
                                    $amount,
                                    $timestamp,
                                    'genesis_allocation'
                                );

                                // Vérifier si la transaction existe déjà
                                if (TransactionManager::transactionExists($hash)) {
                                    $skippedCount++;
                                    continue;
                                }

                                // Créer la transaction d'allocation
                                $transaction = TransactionManager::createTransaction(
                                    $fromAddress,
                                    $toAddress,
                                    $amount,
                                    $hash,
                                    $timestamp,
                                    $blockIndex
                                );

                                if ($transaction) {
                                    $extractedCount++;
                                    $transactionId = $transaction->getId();
                                    
                                    Logger::info('Genesis allocation extracted', [
                                        'from' => substr($fromAddress, 0, 20) . '...',
                                        'to' => substr($toAddress, 0, 20) . '...',
                                        'amount' => $amount,
                                        'hash' => substr($hash, 0, 20) . '...',
                                        'block_index' => $blockIndex
                                    ]);

                                    // Ajouter à la queue
                                    $queueItem = QueueManager::addToQueue(
                                        $transactionId,
                                        $fromAddress,
                                        $toAddress,
                                        $amount,
                                        $hash,
                                        $timestamp,
                                        $blockIndex
                                    );

                                    if ($queueItem) {
                                        $queuedCount++;
                                        Logger::info('Genesis transaction added to queue', [
                                            'transaction_id' => $transactionId,
                                            'hash' => substr($hash, 0, 20) . '...'
                                        ]);
                                    }
                                }
                            }
                        }

                        // Extraire les enregistrements de validateurs
                        if ($item['type'] === 'validator_registration' && isset($item['public_key'])) {
                            $fromAddress = $item['public_key']; // Le validateur qui s'enregistre
                            $toAddress = $item['public_key']; // Transaction vers lui-même
                            $amount = $item['collateral'] ?? 10000;
                            
                            $hash = self::hashTransaction(
                                $fromAddress,
                                $toAddress,
                                $amount,
                                $timestamp,
                                'validator_registration'
                            );

                            // Vérifier si la transaction existe déjà
                            if (TransactionManager::transactionExists($hash)) {
                                $skippedCount++;
                                continue;
                            }

                            // Créer la transaction d'enregistrement
                            $transaction = TransactionManager::createTransaction(
                                $fromAddress,
                                $toAddress,
                                $amount,
                                $hash,
                                $timestamp,
                                $blockIndex
                            );

                            if ($transaction) {
                                $extractedCount++;
                                $transactionId = $transaction->getId();
                                
                                Logger::info('Validator registration extracted', [
                                    'public_key' => substr($fromAddress, 0, 20) . '...',
                                    'collateral' => $amount,
                                    'hash' => substr($hash, 0, 20) . '...',
                                    'block_index' => $blockIndex
                                ]);

                                // Ajouter à la queue
                                $queueItem = QueueManager::addToQueue(
                                    $transactionId,
                                    $fromAddress,
                                    $toAddress,
                                    $amount,
                                    $hash,
                                    $timestamp,
                                    $blockIndex
                                );

                                if ($queueItem) {
                                    $queuedCount++;
                                    Logger::info('Validator registration added to queue', [
                                        'transaction_id' => $transactionId,
                                        'hash' => substr($hash, 0, 20) . '...'
                                    ]);
                                }
                            }
                        }
                    }
                }
            }

            Logger::success('Transactions extracted and queued successfully', [
                'total_blocks' => count($blocks),
                'transactions_extracted' => $extractedCount,
                'transactions_queued' => $queuedCount,
                'skipped' => $skippedCount
            ]);

        } catch (\Exception $e) {
            Logger::error('Failed to extract and queue transactions', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Générer un hash pour une transaction
     */
    private static function hashTransaction(
        string $fromAddress,
        string $toAddress,
        float $amount,
        int $timestamp,
        string $type = 'transaction'
    ): string {
        $data = $fromAddress . $toAddress . $amount . $timestamp . $type;
        return hash('sha256', $data);
    }
}
