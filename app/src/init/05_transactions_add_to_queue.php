<?php

/**
 * 05_transactions_add_to_queue.php
 * ÉTAPE 5: Extraire les données de transactions de la blockchain et les ajouter à la queue
 * 
 * Cette étape:
 * - Parcourt tous les blocs de la blockchain
 * - Extrait toutes les données de transactions (standards, allocations genesis, enregistrements validateurs)
 * - Les ajoute à la queue pour traitement
 * 
 * Note: Les transactions ne sont PAS créées dans cette étape.
 * Elles seront créées lors du traitement de la queue (étape 6).
 */

namespace App\Init;

use App\Config\Database;
use App\Modules\Queue\QueueManager;
use App\Modules\Crypto\Crypto;
use App\Modules\Crypto\SignatureManager;
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
                            $publicKey = $item['public_key'] ?? null;
                            $signature = $item['signature'] ?? null;
                            
                            // Générer le hash de la transaction si non présent
                            $hash = $item['hash'] ?? self::hashTransaction(
                                $fromAddress,
                                $toAddress,
                                $amount,
                                $timestamp
                            );

                            // Vérifier si déjà dans la queue
                            if (QueueManager::existsInQueue($hash)) {
                                $skippedCount++;
                                Logger::debug('Transaction already in queue', [
                                    'hash' => substr($hash, 0, 20) . '...'
                                ]);
                                continue;
                            }

                            $extractedCount++;
                            
                            Logger::info('Transaction extracted', [
                                'from' => substr($fromAddress, 0, 20) . '...',
                                'to' => substr($toAddress, 0, 20) . '...',
                                'amount' => $amount,
                                'hash' => substr($hash, 0, 20) . '...',
                                'block_index' => $blockIndex
                            ]);

                            // Ajouter à la queue sans créer la transaction (sera créée dans queue_process)
                            $queueItem = QueueManager::addToQueue(
                                $fromAddress,
                                $toAddress,
                                $amount,
                                $hash,
                                $timestamp,
                                $blockIndex,
                                $signature,
                                $publicKey,
                                'transaction'
                            );

                            //logger l'ajout à la queue
                            Logger::info('Transaction added to queue', [
                                'hash' => substr($hash, 0, 20) . '...',
                                'signed' => !empty($signature)
                            ]);

                            if ($queueItem) {
                                $queuedCount++;
                                Logger::info('Transaction added to queue', [
                                    'hash' => substr($hash, 0, 20) . '...',
                                    'signed' => !empty($signature)
                                ]);
                            }
                        }

                        // Extraire les transactions depuis les allocations genesis
                        if ($item['type'] === 'genesis_allocation' && isset($item['allocations'])) {
                            foreach ($item['allocations'] as $allocation) {
                                $fromAddress = $item['public_key'] ?? 'genesis';
                                $toAddress = $allocation['recipient'];
                                $amount = (float)$allocation['amount'];
                                $publicKey = $item['public_key'] ?? null;
                                $signature = $item['signature'] ?? null;
                                
                                $hash = self::hashTransaction(
                                    $fromAddress,
                                    $toAddress,
                                    $amount,
                                    $timestamp,
                                    'genesis_allocation'
                                );

                                // Vérifier si déjà dans la queue
                                if (QueueManager::existsInQueue($hash)) {
                                    $skippedCount++;
                                    continue;
                                }

                                $extractedCount++;
                                
                                Logger::info('Genesis allocation extracted', [
                                    'from' => substr($fromAddress, 0, 20) . '...',
                                    'to' => substr($toAddress, 0, 20) . '...',
                                    'amount' => $amount,
                                    'hash' => substr($hash, 0, 20) . '...',
                                    'block_index' => $blockIndex
                                ]);

                                // Ajouter à la queue sans créer la transaction (sera créée dans queue_process)
                                $queueItem = QueueManager::addToQueue(
                                    $fromAddress,
                                    $toAddress,
                                    $amount,
                                    $hash,
                                    $timestamp,
                                    $blockIndex,
                                    $signature,
                                    $publicKey,
                                    'genesis_allocation'
                                );

                                if ($queueItem) {
                                    $queuedCount++;
                                    Logger::info('Genesis transaction added to queue', [
                                        'hash' => substr($hash, 0, 20) . '...',
                                        'signed' => !empty($signature)
                                    ]);
                                }
                            }
                        }

                        // Extraire les enregistrements de validateurs
                        if ($item['type'] === 'validator_registration' && isset($item['public_key'])) {
                            $fromAddress = $item['public_key']; // Le validateur qui s'enregistre
                            $toAddress = $item['public_key']; // Transaction vers lui-même
                            $amount = $item['collateral'] ?? 10000;
                            $publicKey = $item['public_key'];
                            $signature = $item['signature'] ?? null;
                            
                            $hash = self::hashTransaction(
                                $fromAddress,
                                $toAddress,
                                $amount,
                                $timestamp,
                                'validator_registration'
                            );

                            // Vérifier si déjà dans la queue
                            if (QueueManager::existsInQueue($hash)) {
                                $skippedCount++;
                                continue;
                            }

                            $extractedCount++;
                            
                            Logger::info('Validator registration extracted', [
                                'public_key' => substr($fromAddress, 0, 20) . '...',
                                'collateral' => $amount,
                                'hash' => substr($hash, 0, 20) . '...',
                                'block_index' => $blockIndex
                            ]);

                            // Ajouter à la queue sans créer la transaction (sera créée dans queue_process)
                            $queueItem = QueueManager::addToQueue(
                                $fromAddress,
                                $toAddress,
                                $amount,
                                $hash,
                                $timestamp,
                                $blockIndex,
                                $signature,
                                $publicKey,
                                'validator_registration'
                            );

                            if ($queueItem) {
                                $queuedCount++;
                                Logger::info('Validator registration added to queue', [
                                    'hash' => substr($hash, 0, 20) . '...',
                                    'signed' => !empty($signature)
                                ]);
                            }
                        }
                    }
                }
            }

            Logger::success('Transaction data extracted and queued successfully', [
                'total_blocks' => count($blocks),
                'data_extracted' => $extractedCount,
                'items_queued' => $queuedCount,
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
