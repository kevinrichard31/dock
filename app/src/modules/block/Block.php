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
    private string $merkleRoot;
    private ?string $validatorAddress;

    public function __construct(
        int $index,
        string $previousHash = '0',
        array $transactions = [],
        ?string $validatorAddress = null
    ) {
        $this->index = $index;
        $this->previousHash = $previousHash;
        $this->transactions = $transactions;
        $this->timestamp = time();
        $this->validatorAddress = $validatorAddress;
        $this->merkleRoot = Crypto::calculateMerkleRoot($transactions);
        $this->hash = $this->calculateHash();
    }

    /**
     * Calculate block hash (Proof of Stake)
     */
    public function calculateHash(): string
    {
        $data = $this->index . $this->previousHash . $this->timestamp . 
                json_encode($this->transactions) . $this->merkleRoot . 
                ($this->validatorAddress ?? '');
        return Crypto::hash($data);
    }

    /**
     * Save block to database
     */
    public function save(): bool
    {
        $db = Database::getInstance()->getConnection();
        
        $sql = "INSERT INTO blocks (index_num, hash, previous_hash, timestamp, merkle_root, validator_address, data) 
                VALUES (:index, :hash, :previous_hash, :timestamp, :merkle_root, :validator_address, :data)";
        
        $stmt = $db->prepare($sql);
        
        return $stmt->execute([
            ':index' => $this->index,
            ':hash' => $this->hash,
            ':previous_hash' => $this->previousHash,
            ':timestamp' => $this->timestamp,
            ':merkle_root' => $this->merkleRoot,
            ':validator_address' => $this->validatorAddress,
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
    public function getMerkleRoot(): string { return $this->merkleRoot; }
    public function getValidatorAddress(): ?string { return $this->validatorAddress; }

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
            'merkleRoot' => $this->merkleRoot,
            'validatorAddress' => $this->validatorAddress
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
            $record['validator_address']
        );
        
        $block->hash = $record['hash'];
        $block->timestamp = $record['timestamp'];
        
        return $block;
    }
}
