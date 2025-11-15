<?php

namespace App\Modules\Wallet;

use App\Config\Database;
use App\Lib\Crypto;
use PDO;

class Wallet
{
    private string $address;
    private string $publicKey;
    private string $privateKey;
    private float $balance;
    private int $userId;
    private array $transactions = [];

    public function __construct(
        int $userId,
        ?string $address = null,
        ?string $publicKey = null,
        ?string $privateKey = null
    ) {
        $this->userId = $userId;
        
        if ($address && $publicKey && $privateKey) {
            $this->address = $address;
            $this->publicKey = $publicKey;
            $this->privateKey = $privateKey;
        } else {
            $this->generateKeys();
        }
        
        $this->balance = 0;
    }

    /**
     * Generate cryptographic keys
     */
    private function generateKeys(): void
    {
        $seed = Crypto::randomHex(32);
        $this->privateKey = Crypto::hash($seed . time());
        $this->publicKey = Crypto::hash($this->privateKey);
        $this->address = Crypto::generateAddress($this->publicKey);
    }

    /**
     * Save wallet to database
     */
    public function save(): bool
    {
        $db = Database::getInstance()->getConnection();
        
        $sql = "INSERT INTO wallets (user_id, address, public_key, private_key, balance) 
                VALUES (:user_id, :address, :public_key, :private_key, :balance)
                ON DUPLICATE KEY UPDATE 
                balance = VALUES(balance)";
        
        $stmt = $db->prepare($sql);
        
        return $stmt->execute([
            ':user_id' => $this->userId,
            ':address' => $this->address,
            ':public_key' => $this->publicKey,
            ':private_key' => $this->privateKey,
            ':balance' => $this->balance
        ]);
    }

    /**
     * Load wallet from database
     */
    public static function loadFromDatabase(int $userId): ?self
    {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT * FROM wallets WHERE user_id = :user_id LIMIT 1";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$record) {
            return null;
        }

        $wallet = new self(
            $userId,
            $record['address'],
            $record['public_key'],
            $record['private_key']
        );
        
        $wallet->balance = (float)$record['balance'];
        return $wallet;
    }

    /**
     * Load wallet by address
     */
    public static function loadByAddress(string $address): ?self
    {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT * FROM wallets WHERE address = :address LIMIT 1";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':address' => $address]);
        
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$record) {
            return null;
        }

        $wallet = new self(
            $record['user_id'],
            $record['address'],
            $record['public_key'],
            $record['private_key']
        );
        
        $wallet->balance = (float)$record['balance'];
        return $wallet;
    }

    /**
     * Add balance
     */
    public function addBalance(float $amount): self
    {
        $this->balance += $amount;
        return $this;
    }

    /**
     * Set balance (replace current balance)
     */
    public function setBalance(float $amount): self
    {
        $this->balance = $amount;
        return $this;
    }

    /**
     * Subtract balance
     */
    public function subtractBalance(float $amount): bool
    {
        if ($this->balance >= $amount) {
            $this->balance -= $amount;
            return true;
        }
        return false;
    }

    /**
     * Create transaction
     */
    public function createTransaction(string $toAddress, float $amount): ?array
    {
        if (!$this->subtractBalance($amount)) {
            return null;
        }

        $transaction = [
            'from' => $this->address,
            'to' => $toAddress,
            'amount' => $amount,
            'timestamp' => time(),
            'hash' => Crypto::randomHex(32)
        ];

        $this->transactions[] = $transaction;
        return $transaction;
    }

    /**
     * Get wallet transactions
     */
    public function getTransactions(): array
    {
        return $this->transactions;
    }

    /**
     * Get wallet data as array
     */
    public function toArray(): array
    {
        return [
            'userId' => $this->userId,
            'address' => $this->address,
            'publicKey' => $this->publicKey,
            'privateKey' => $this->privateKey,
            'balance' => $this->balance,
            'transactionCount' => count($this->transactions)
        ];
    }

    // Getters
    public function getAddress(): string { return $this->address; }
    public function getPublicKey(): string { return $this->publicKey; }
    public function getBalance(): float { return $this->balance; }
    public function getUserId(): int { return $this->userId; }
}
