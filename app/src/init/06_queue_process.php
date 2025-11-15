<?php

/**
 * 06_queue_process.php
 * ÉTAPE 6: Traiter la queue des transactions
 * 
 * Cette étape:
 * - Traite tous les items en attente dans la queue
 * - Crée les transactions dans la base de données (table transactions)
 * - Valide et traite chaque transaction
 * - Met à jour le statut de chaque item
 */

namespace App\Init;

use App\Config\Database;
use App\Modules\Queue\Queue;
use App\Modules\Queue\QueueManager;
use App\Modules\Transaction\TransactionManager;
use App\Lib\Logger;
use PDO;

class InitQueueProcess
{
    public static function execute(): void
    {
        Logger::info('=== ÉTAPE 6: Traitement de la Queue des Transactions ===');

        try {
            $db = Database::getInstance()->getConnection();

            // Récupérer tous les items en attente
            Logger::info('About to get pending queue items');
            $pendingItems = Queue::getByStatus('pending');
            Logger::info('Got pending queue items', ['count' => count($pendingItems)]);

            if (empty($pendingItems)) {
                Logger::info('No pending transactions in queue');
                return;
            }

            Logger::info('Processing queue items', ['count' => count($pendingItems)]);

            $processedCount = 0;
            $completedCount = 0;
            $failedCount = 0;

            foreach ($pendingItems as $queueItem) {
                $queueId = $queueItem->getId();
                $fromAddress = $queueItem->getFromAddress();
                $toAddress = $queueItem->getToAddress();
                $amount = $queueItem->getAmount();
                $hash = $queueItem->getHash();
                $blockIndex = $queueItem->getBlockIndex();
                $timestamp = $queueItem->getTimestamp();

                Logger::info('Processing queue item', [
                    'queue_id' => $queueId,
                    'from' => substr($fromAddress, 0, 20) . '...',
                    'to' => substr($toAddress, 0, 20) . '...',
                    'amount' => $amount
                ]);

                try {
                    // ÉTAPE 1: Créer la transaction si elle n'existe pas déjà
                    // Vérifier si la transaction existe déjà dans la base
                    if (TransactionManager::transactionExists($hash)) {
                        Logger::warning('Transaction already exists in database, skipping creation', [
                            'hash' => substr($hash, 0, 20) . '...'
                        ]);
                        
                        // Marquer comme échouée car doublon
                        $failSql = "UPDATE queue SET status = 'failed' WHERE id = :id";
                        $failStmt = $db->prepare($failSql);
                        $failStmt->execute([':id' => $queueId]);
                        $failedCount++;
                        continue;
                    }

                    // Créer la transaction
                    Logger::info('Creating transaction', [
                        'from' => substr($fromAddress, 0, 20) . '...',
                        'to' => substr($toAddress, 0, 20) . '...',
                        'amount' => $amount,
                        'hash' => substr($hash, 0, 20) . '...'
                    ]);

                    $transaction = TransactionManager::createTransaction(
                        $fromAddress,
                        $toAddress,
                        $amount,
                        $hash,
                        $timestamp,
                        $blockIndex
                    );

                    if (!$transaction) {
                        Logger::error('Failed to create transaction', [
                            'hash' => substr($hash, 0, 20) . '...'
                        ]);

                        // Marquer comme échouée
                        $failSql = "UPDATE queue SET status = 'failed' WHERE id = :id";
                        $failStmt = $db->prepare($failSql);
                        $failStmt->execute([':id' => $queueId]);
                        $failedCount++;
                        continue;
                    }

                    $transactionId = $transaction->getId();
                    
                    Logger::success('Transaction created', [
                        'transaction_id' => $transactionId,
                        'hash' => substr($hash, 0, 20) . '...'
                    ]);

                    // ÉTAPE 2: Mettre à jour le statut à processing
                    Logger::info('Updating queue item status to processing', ['queue_id' => $queueId]);
                    
                    $updateSql = "UPDATE queue SET status = 'processing' WHERE id = :id";
                    $updateStmt = $db->prepare($updateSql);
                    $updateResult = $updateStmt->execute([':id' => $queueId]);
                    
                    Logger::info('Queue item status updated', ['queue_id' => $queueId, 'result' => $updateResult]);

                    // ÉTAPE 3: Valider la transaction
                    Logger::info('Validating transaction', ['from' => substr($fromAddress, 0, 20) . '...']);
                    
                    $isValid = !empty($fromAddress) && !empty($toAddress) && $amount > 0 && !empty($hash) && strlen($hash) >= 32;
                    
                    Logger::info('Validation result', ['valid' => $isValid]);

                    if (!$isValid) {
                        Logger::warning('Transaction validation failed', [
                            'queue_id' => $queueId,
                            'transaction_id' => $transactionId,
                            'hash' => substr($hash, 0, 20) . '...'
                        ]);

                        // Marquer comme échouée directement
                        $failSql = "UPDATE queue SET status = 'failed' WHERE id = :id";
                        $failStmt = $db->prepare($failSql);
                        $failStmt->execute([':id' => $queueId]);
                        
                        $failedCount++;
                        continue;
                    }

                    Logger::info('Before apply transaction', ['queue_id' => $queueId]);
                    
                    // ÉTAPE 4: Appliquer la transaction (mise à jour des soldes, etc.)
                    $applied = true; // Pour le moment, accepter toutes les transactions valides
                    
                    Logger::info('After apply transaction', ['queue_id' => $queueId, 'applied' => $applied]);

                    if (!$applied) {
                        Logger::warning('Failed to apply transaction', [
                            'queue_id' => $queueId,
                            'transaction_id' => $transactionId,
                            'hash' => substr($hash, 0, 20) . '...'
                        ]);

                        // Marquer comme échouée directement
                        $failSql = "UPDATE queue SET status = 'failed' WHERE id = :id";
                        $failStmt = $db->prepare($failSql);
                        $failStmt->execute([':id' => $queueId]);
                        
                        $failedCount++;
                        continue;
                    }

                    // ÉTAPE 5: Supprimer de la queue une fois complétée
                    $deleteSql = "DELETE FROM queue WHERE id = :id";
                    $deleteStmt = $db->prepare($deleteSql);
                    $deleteStmt->execute([':id' => $queueId]);
                    
                    $completedCount++;

                    Logger::success('Transaction processed and removed from queue', [
                        'queue_id' => $queueId,
                        'transaction_id' => $transactionId,
                        'hash' => substr($hash, 0, 20) . '...'
                    ]);

                } catch (\Exception $e) {
                    Logger::error('Error processing queue item', [
                        'queue_id' => $queueId,
                        'error' => $e->getMessage()
                    ]);

                    // Marquer comme échouée directement
                    $failSql = "UPDATE queue SET status = 'failed' WHERE id = :id";
                    $failStmt = $db->prepare($failSql);
                    $failStmt->execute([':id' => $queueId]);
                    
                    $failedCount++;
                }

                $processedCount++;
            }

            Logger::success('Queue processing completed', [
                'total_processed' => $processedCount,
                'completed' => $completedCount,
                'failed' => $failedCount
            ]);

        } catch (\Exception $e) {
            Logger::error('Failed to process queue', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Valider une transaction
     */
    private static function validateTransaction(
        string $fromAddress,
        string $toAddress,
        float $amount,
        string $hash
    ): bool {
        try {
            // Vérifier que les adresses ne sont pas vides
            if (empty($fromAddress) || empty($toAddress)) {
                return false;
            }

            // Vérifier que le montant est positif
            if ($amount <= 0) {
                return false;
            }

            // Vérifier que le hash est valide
            if (empty($hash) || strlen($hash) < 32) {
                return false;
            }

            // Note: Les allocations genesis peuvent avoir la même adresse source et destination
            // donc on ne vérifie pas que fromAddress !== toAddress

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Appliquer une transaction
     */
    private static function applyTransaction(
        string $fromAddress,
        string $toAddress,
        float $amount,
        ?int $blockIndex
    ): bool {
        try {
            $db = Database::getInstance()->getConnection();

            // Pour le moment, nous acceptons la transaction sans vérification de solde
            // (car les wallets sont synchronisées depuis la blockchain)
            // Plus tard, nous pourrions ajouter une vérification de solde

            Logger::debug('Transaction applied', [
                'from' => substr($fromAddress, 0, 20) . '...',
                'to' => substr($toAddress, 0, 20) . '...',
                'amount' => $amount,
                'block_index' => $blockIndex
            ]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
