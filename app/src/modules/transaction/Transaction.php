<?php

/**
 * Transaction.php
 * Classe représentant une transaction sur la blockchain
 */

namespace App\Modules\Transaction;

use App\Config\Database;
use PDO;

class Transaction
{
    private int $id;
    private string $fromAddress;
    private string $toAddress;
    private float $amount;
    private string $hash;
    private ?int $blockIndex;
    private int $timestamp;

    public function __construct(
        string $fromAddress,
        string $toAddress,
        float $amount,
        string $hash,
        int $timestamp,
        ?int $blockIndex = null,
        int $id = 0
    ) {
        $this->id = $id;
        $this->fromAddress = $fromAddress;
        $this->toAddress = $toAddress;
        $this->amount = $amount;
        $this->hash = $hash;
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

    public function getBlockIndex(): ?int
    {
        return $this->blockIndex;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function save(): bool
    {
        try {
            $db = Database::getInstance()->getConnection();

            // Vérifier si la transaction existe
            if ($this->id > 0) {
                $sql = "UPDATE transactions SET from_address = :from_address, to_address = :to_address, 
                        amount = :amount, block_index = :block_index WHERE id = :id";
                $stmt = $db->prepare($sql);
                return $stmt->execute([
                    ':from_address' => $this->fromAddress,
                    ':to_address' => $this->toAddress,
                    ':amount' => $this->amount,
                    ':block_index' => $this->blockIndex,
                    ':id' => $this->id
                ]);
            } else {
                $sql = "INSERT INTO transactions (from_address, to_address, amount, hash, block_index, timestamp) 
                        VALUES (:from_address, :to_address, :amount, :hash, :block_index, :timestamp)";
                $stmt = $db->prepare($sql);
                $result = $stmt->execute([
                    ':from_address' => $this->fromAddress,
                    ':to_address' => $this->toAddress,
                    ':amount' => $this->amount,
                    ':hash' => $this->hash,
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

    public static function findById(int $id): ?Transaction
    {
        try {
            $db = Database::getInstance()->getConnection();
            $sql = "SELECT * FROM transactions WHERE id = :id LIMIT 1";
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
                    $row['block_index'] ? (int)$row['block_index'] : null,
                    (int)$row['id']
                );
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }

    public static function findByHash(string $hash): ?Transaction
    {
        try {
            $db = Database::getInstance()->getConnection();
            $sql = "SELECT * FROM transactions WHERE hash = :hash LIMIT 1";
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
                    $row['block_index'] ? (int)$row['block_index'] : null,
                    (int)$row['id']
                );
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }

    public static function findByAddress(string $address, string $direction = 'both'): array
    {
        try {
            $db = Database::getInstance()->getConnection();

            $sql = "SELECT * FROM transactions WHERE ";
            if ($direction === 'from') {
                $sql .= "from_address = :address";
            } elseif ($direction === 'to') {
                $sql .= "to_address = :address";
            } else {
                $sql .= "(from_address = :address OR to_address = :address)";
            }
            $sql .= " ORDER BY timestamp DESC";

            $stmt = $db->prepare($sql);
            $stmt->execute([':address' => $address]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $transactions = [];
            foreach ($rows as $row) {
                $transactions[] = new self(
                    $row['from_address'],
                    $row['to_address'],
                    (float)$row['amount'],
                    $row['hash'],
                    (int)$row['timestamp'],
                    $row['block_index'] ? (int)$row['block_index'] : null,
                    (int)$row['id']
                );
            }

            return $transactions;
        } catch (\Exception $e) {
            return [];
        }
    }

    public static function getAll(): array
    {
        try {
            $db = Database::getInstance()->getConnection();
            $sql = "SELECT * FROM transactions ORDER BY timestamp DESC";
            $stmt = $db->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $transactions = [];
            foreach ($rows as $row) {
                $transactions[] = new self(
                    $row['from_address'],
                    $row['to_address'],
                    (float)$row['amount'],
                    $row['hash'],
                    (int)$row['timestamp'],
                    $row['block_index'] ? (int)$row['block_index'] : null,
                    (int)$row['id']
                );
            }

            return $transactions;
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
            'block_index' => $this->blockIndex,
            'timestamp' => $this->timestamp
        ];
    }
}
