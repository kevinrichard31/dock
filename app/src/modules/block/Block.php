<?php

namespace App\Modules\Block;

use App\Config\Database;
use App\Lib\Crypto;
use PDO;

class Block
{
    private int $index;
    private string $hash;
    private string $previousHash;
    private int $timestamp;
    private array $transactions;
    private int $nonce;
    private string $merkleRoot;
    private int $difficulty;

    public function __construct(
        int $index,
        string $previousHash = '0',
        array $transactions = [],
        int $difficulty = 4
    ) {
        $this->index = $index;
        $this->previousHash = $previousHash;
        $this->transactions = $transactions;
        $this->timestamp = time();
        $this->nonce = 0;
        $this->difficulty = $difficulty;
        $this->merkleRoot = Crypto::calculateMerkleRoot($transactions);
        $this->hash = $this->calculateHash();
    }

    /**
     * Calculate block hash
     */
    public function calculateHash(): string
    {
        $data = $this->index . $this->previousHash . $this->timestamp . 
                json_encode($this->transactions) . $this->nonce . $this->merkleRoot;
        return Crypto::hash($data);
    }

    /**
     * Mine the block (Proof of Work)
     */
    public function mineBlock(): void
    {
        $target = str_repeat('0', $this->difficulty);
        
        while (substr($this->hash, 0, $this->difficulty) !== $target) {
            $this->nonce++;
            $this->hash = $this->calculateHash();
        }
    }

    /**
     * Save block to database
     */
    public function save(): bool
    {
        $db = Database::getInstance()->getConnection();
        
        $sql = "INSERT INTO blocks (index_num, hash, previous_hash, timestamp, merkle_root, nonce, difficulty, data) 
                VALUES (:index, :hash, :previous_hash, :timestamp, :merkle_root, :nonce, :difficulty, :data)";
        
        $stmt = $db->prepare($sql);
        
        return $stmt->execute([
            ':index' => $this->index,
            ':hash' => $this->hash,
            ':previous_hash' => $this->previousHash,
            ':timestamp' => $this->timestamp,
            ':merkle_root' => $this->merkleRoot,
            ':nonce' => $this->nonce,
            ':difficulty' => $this->difficulty,
            ':data' => json_encode($this->transactions)
        ]);
    }

    /**
     * Verify block integrity
     */
    public function isValid(): bool
    {
        return $this->hash === $this->calculateHash() &&
               $this->merkleRoot === Crypto::calculateMerkleRoot($this->transactions);
    }

    // Getters
    public function getIndex(): int { return $this->index; }
    public function getHash(): string { return $this->hash; }
    public function getPreviousHash(): string { return $this->previousHash; }
    public function getTimestamp(): int { return $this->timestamp; }
    public function getTransactions(): array { return $this->transactions; }
    public function getNonce(): int { return $this->nonce; }
    public function getMerkleRoot(): string { return $this->merkleRoot; }
    public function getDifficulty(): int { return $this->difficulty; }

    /**
     * Get block data as array
     */
    public function toArray(): array
    {
        return [
            'index' => $this->index,
            'hash' => $this->hash,
            'previousHash' => $this->previousHash,
            'timestamp' => $this->timestamp,
            'transactions' => $this->transactions,
            'nonce' => $this->nonce,
            'merkleRoot' => $this->merkleRoot,
            'difficulty' => $this->difficulty
        ];
    }

    /**
     * Create block from database record
     */
    public static function fromDatabase(array $record): self
    {
        $block = new self(
            $record['index_num'],
            $record['previous_hash'],
            json_decode($record['data'], true) ?? [],
            $record['difficulty']
        );
        
        $block->hash = $record['hash'];
        $block->nonce = $record['nonce'];
        $block->timestamp = $record['timestamp'];
        
        return $block;
    }
}
