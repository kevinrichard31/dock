<?php

/**
 * Queue.php
 * Classe représentant une transaction en attente dans la queue
 */

namespace App\Modules\Queue;

use App\Config\Database;
use App\Lib\Logger;
use PDO;

class Queue
{
    private int $id;
    private string $fromAddress;
    private string $toAddress;
    private float $amount;
    private string $hash;
    private string $status; // pending, processing, completed, failed
    private ?int $blockIndex;
    private int $timestamp;

    public function __construct(
        string $fromAddress,
        string $toAddress,
        float $amount,
        string $hash,
        int $timestamp,
        string $status = 'pending',
        ?int $blockIndex = null,
        int $id = 0
    ) {
        $this->id = $id;
        $this->fromAddress = $fromAddress;
        $this->toAddress = $toAddress;
        $this->amount = $amount;
        $this->hash = $hash;
        $this->status = $status;
        $this->timestamp = $timestamp;
        $this->blockIndex = $blockIndex;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getFromAddress(): string
    {
        return $this->fromAddress;
    }

    public function getToAddress(): string
    {
        return $this->toAddress;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getBlockIndex(): ?int
    {
        return $this->blockIndex;
    }

    public function setBlockIndex(?int $blockIndex): void
    {
        $this->blockIndex = $blockIndex;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function save(): bool
    {
        try {
            $db = Database::getInstance()->getConnection();

            if ($this->id > 0) {
                // Update
                Logger::info('Updating queue item', ['id' => $this->id]);
                $sql = "UPDATE queue SET status = :status, block_index = :block_index, 
                        updated_at = CURRENT_TIMESTAMP WHERE id = :id";
                $stmt = $db->prepare($sql);
                $result = $stmt->execute([
                    ':status' => $this->status,
                    ':block_index' => $this->blockIndex,
                    ':id' => $this->id
                ]);
                
                if (!$result) {
                    Logger::error('Failed to update queue item', [
                        'id' => $this->id,
                        'error' => $stmt->errorInfo()
                    ]);
                }
                
                return $result;
            } else {
                // Insert
                Logger::info('Inserting new queue item', [
                    'from' => substr($this->fromAddress, 0, 20) . '...',
                    'to' => substr($this->toAddress, 0, 20) . '...',
                    'amount' => $this->amount,
                    'hash' => substr($this->hash, 0, 20) . '...'
                ]);
                
                $sql = "INSERT INTO queue (from_address, to_address, amount, hash, status, block_index, timestamp) 
                        VALUES (:from_address, :to_address, :amount, :hash, :status, :block_index, :timestamp)";
                $stmt = $db->prepare($sql);
                
                if (!$stmt) {
                    Logger::error('Failed to prepare insert statement', [
                        'error' => $db->errorInfo()
                    ]);
                    return false;
                }
                
                $result = $stmt->execute([
                    ':from_address' => $this->fromAddress,
                    ':to_address' => $this->toAddress,
                    ':amount' => $this->amount,
                    ':hash' => $this->hash,
                    ':status' => $this->status,
                    ':block_index' => $this->blockIndex,
                    ':timestamp' => $this->timestamp
                ]);
                
                if (!$result) {
                    Logger::error('Failed to insert queue item', [
                        'error' => $stmt->errorInfo(),
                        'from_address' => substr($this->fromAddress, 0, 20) . '...',
                        'to_address' => substr($this->toAddress, 0, 20) . '...',
                        'amount' => $this->amount,
                        'hash' => substr($this->hash, 0, 20) . '...',
                        'status' => $this->status,
                        'block_index' => $this->blockIndex,
                        'timestamp' => $this->timestamp
                    ]);
                    return false;
                }
                
                $this->id = (int)$db->lastInsertId();
                Logger::success('Queue item inserted', ['id' => $this->id]);

                return $result;
            }
        } catch (\Exception $e) {
            Logger::error('Exception in Queue::save()', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    public static function findById(int $id): ?Queue
    {
        try {
            $db = Database::getInstance()->getConnection();
            $sql = "SELECT * FROM queue WHERE id = :id LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                return new self(
                    $row['from_address'],
                    $row['to_address'],
                    (float)$row['amount'],
                    $row['hash'],
                    (int)$row['timestamp'],
                    $row['status'],
                    $row['block_index'] ? (int)$row['block_index'] : null,
                    (int)$row['id']
                );
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }

    public static function findByHash(string $hash): ?Queue
    {
        try {
            $db = Database::getInstance()->getConnection();
            $sql = "SELECT * FROM queue WHERE hash = :hash LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->execute([':hash' => $hash]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                return new self(
                    $row['from_address'],
                    $row['to_address'],
                    (float)$row['amount'],
                    $row['hash'],
                    (int)$row['timestamp'],
                    $row['status'],
                    $row['block_index'] ? (int)$row['block_index'] : null,
                    (int)$row['id']
                );
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }

    public static function getAll(): array
    {
        try {
            $db = Database::getInstance()->getConnection();
            $sql = "SELECT * FROM queue ORDER BY timestamp DESC";
            $stmt = $db->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $queue = [];
            foreach ($rows as $row) {
                $queue[] = new self(
                    $row['from_address'],
                    $row['to_address'],
                    (float)$row['amount'],
                    $row['hash'],
                    (int)$row['timestamp'],
                    $row['status'],
                    $row['block_index'] ? (int)$row['block_index'] : null,
                    (int)$row['id']
                );
            }

            return $queue;
        } catch (\Exception $e) {
            return [];
        }
    }

    public static function getByStatus(string $status): array
    {
        try {
            $db = Database::getInstance()->getConnection();
            $sql = "SELECT * FROM queue WHERE status = :status ORDER BY timestamp ASC";
            $stmt = $db->prepare($sql);
            $stmt->execute([':status' => $status]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $queue = [];
            foreach ($rows as $row) {
                $queue[] = new self(
                    $row['from_address'],
                    $row['to_address'],
                    (float)$row['amount'],
                    $row['hash'],
                    (int)$row['timestamp'],
                    $row['status'],
                    $row['block_index'] ? (int)$row['block_index'] : null,
                    (int)$row['id']
                );
            }

            return $queue;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'from_address' => $this->fromAddress,
            'to_address' => $this->toAddress,
            'amount' => $this->amount,
            'hash' => $this->hash,
            'status' => $this->status,
            'block_index' => $this->blockIndex,
            'timestamp' => $this->timestamp
        ];
    }
}
