<?php

/**
 * Queue.php
 * Classe représentant une transaction en attente dans la queue
 */

namespace App\Modules\Queue;

use App\Config\Database;
use PDO;

class Queue
{
    private int $id;
    private int $transactionId;
    private string $fromAddress;
    private string $toAddress;
    private float $amount;
    private string $hash;
    private string $status; // pending, processing, completed, failed
    private ?int $blockIndex;
    private int $timestamp;

    public function __construct(
        int $transactionId,
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
        $this->transactionId = $transactionId;
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

    public function getTransactionId(): int
    {
        return $this->transactionId;
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
                $sql = "UPDATE queue SET status = :status, block_index = :block_index, 
                        updated_at = CURRENT_TIMESTAMP WHERE id = :id";
                $stmt = $db->prepare($sql);
                return $stmt->execute([
                    ':status' => $this->status,
                    ':block_index' => $this->blockIndex,
                    ':id' => $this->id
                ]);
            } else {
                // Insert
                $sql = "INSERT INTO queue (transaction_id, from_address, to_address, amount, hash, status, block_index, timestamp) 
                        VALUES (:transaction_id, :from_address, :to_address, :amount, :hash, :status, :block_index, :timestamp)";
                $stmt = $db->prepare($sql);
                $result = $stmt->execute([
                    ':transaction_id' => $this->transactionId,
                    ':from_address' => $this->fromAddress,
                    ':to_address' => $this->toAddress,
                    ':amount' => $this->amount,
                    ':hash' => $this->hash,
                    ':status' => $this->status,
                    ':block_index' => $this->blockIndex,
                    ':timestamp' => $this->timestamp
                ]);

                if ($result) {
                    $this->id = (int)$db->lastInsertId();
                }

                return $result;
            }
        } catch (\Exception $e) {
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
            'transaction_id' => $this->transactionId,
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
