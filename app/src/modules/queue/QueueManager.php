<?php

/**
 * QueueManager.php
 * Gestionnaire de la queue de transactions
 * Vérifie les signatures et gère le traitement des transactions
 */

namespace App\Modules\Queue;

use App\Config\Database;
use App\Modules\Crypto\Crypto;
use App\Modules\Crypto\SignatureManager;
use App\Lib\Logger;
use PDO;

class QueueManager
{
    public static function addToQueue(
        int $transactionId,
        string $fromAddress,
        string $toAddress,
        float $amount,
        string $hash,
        int $timestamp,
        ?int $blockIndex = null,
        ?string $signature = null,
        ?string $publicKey = null
    ): ?Queue {
        try {
            // Vérifier si la transaction est déjà dans la queue
            if (self::existsInQueue($hash)) {
                return null;
            }

            // Vérifier la signature si fournie
            if ($signature && $publicKey) {
                if (!self::verifyTransactionSignature($fromAddress, $toAddress, $amount, $timestamp, $signature, $publicKey)) {
                    Logger::warning('Invalid transaction signature', [
                        'from' => $fromAddress,
                        'to' => $toAddress,
                        'hash' => $hash
                    ]);
                    return null;
                }
            }

            $queueItem = new Queue(
                $transactionId,
                $fromAddress,
                $toAddress,
                $amount,
                $hash,
                $timestamp,
                'pending',
                $blockIndex
            );

            if ($queueItem->save()) {
                return $queueItem;
            }
        } catch (\Exception $e) {
            Logger::error('Error adding to queue', ['error' => $e->getMessage()]);
            return null;
        }

        return null;
    }

    public static function existsInQueue(string $hash): bool
    {
        try {
            $db = Database::getInstance()->getConnection();
            $sql = "SELECT COUNT(*) FROM queue WHERE hash = :hash";
            $stmt = $db->prepare($sql);
            $stmt->execute([':hash' => $hash]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Verify transaction signature
     */
    private static function verifyTransactionSignature(
        string $fromAddress,
        string $toAddress,
        float $amount,
        int $timestamp,
        string $signature,
        string $publicKey
    ): bool {
        try {
            $transactionData = [
                'from' => $fromAddress,
                'to' => $toAddress,
                'amount' => $amount,
                'timestamp' => $timestamp,
                'public_key' => $publicKey,
                'signature' => $signature
            ];

            return SignatureManager::verifyTransaction($transactionData);
        } catch (\Exception $e) {
            Logger::debug('Transaction signature verification failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public static function getQueueSize(): int
    {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT COUNT(*) FROM queue");
            return (int)$stmt->fetchColumn();
        } catch (\Exception $e) {
            return 0;
        }
    }

    public static function getPendingCount(): int
    {
        try {
            $db = Database::getInstance()->getConnection();
            $sql = "SELECT COUNT(*) FROM queue WHERE status = 'pending'";
            $stmt = $db->query($sql);
            return (int)$stmt->fetchColumn();
        } catch (\Exception $e) {
            return 0;
        }
    }

    public static function getProcessingCount(): int
    {
        try {
            $db = Database::getInstance()->getConnection();
            $sql = "SELECT COUNT(*) FROM queue WHERE status = 'processing'";
            $stmt = $db->query($sql);
            return (int)$stmt->fetchColumn();
        } catch (\Exception $e) {
            return 0;
        }
    }

    public static function getCompletedCount(): int
    {
        try {
            $db = Database::getInstance()->getConnection();
            $sql = "SELECT COUNT(*) FROM queue WHERE status = 'completed'";
            $stmt = $db->query($sql);
            return (int)$stmt->fetchColumn();
        } catch (\Exception $e) {
            return 0;
        }
    }

    public static function getFailedCount(): int
    {
        try {
            $db = Database::getInstance()->getConnection();
            $sql = "SELECT COUNT(*) FROM queue WHERE status = 'failed'";
            $stmt = $db->query($sql);
            return (int)$stmt->fetchColumn();
        } catch (\Exception $e) {
            return 0;
        }
    }

    public static function getQueueStats(): array
    {
        return [
            'total' => self::getQueueSize(),
            'pending' => self::getPendingCount(),
            'processing' => self::getProcessingCount(),
            'completed' => self::getCompletedCount(),
            'failed' => self::getFailedCount()
        ];
    }

    public static function processNextPending(): ?Queue
    {
        try {
            $db = Database::getInstance()->getConnection();

            // Récupérer le premier item en attente
            $sql = "SELECT * FROM queue WHERE status = 'pending' ORDER BY timestamp ASC LIMIT 1";
            $stmt = $db->query($sql);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $queueItem = new Queue(
                    (int)$row['transaction_id'],
                    $row['from_address'],
                    $row['to_address'],
                    (float)$row['amount'],
                    $row['hash'],
                    (int)$row['timestamp'],
                    $row['status'],
                    $row['block_index'] ? (int)$row['block_index'] : null,
                    (int)$row['id']
                );

                // Mettre le statut à processing
                $queueItem->setStatus('processing');
                $queueItem->save();

                return $queueItem;
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }

    public static function completeQueueItem(int $queueId, ?int $blockIndex = null): bool
    {
        try {
            $queueItem = Queue::findById($queueId);
            if ($queueItem) {
                $queueItem->setStatus('completed');
                if ($blockIndex !== null) {
                    $queueItem->setBlockIndex($blockIndex);
                }
                return $queueItem->save();
            }
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }

    public static function failQueueItem(int $queueId): bool
    {
        try {
            $queueItem = Queue::findById($queueId);
            if ($queueItem) {
                $queueItem->setStatus('failed');
                return $queueItem->save();
            }
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }

    public static function clearQueue(): bool
    {
        try {
            $db = Database::getInstance()->getConnection();
            $db->exec("DELETE FROM queue");
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
