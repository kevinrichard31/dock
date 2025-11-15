<?php

namespace App\Modules\Validator;

use App\Config\Database;
use PDO;

class Validator
{
    private string $publicKey;
    private float $collateral;
    private string $status;
    private int $isApproved;
    private int $createdAt;
    private int $updatedAt;

    // Fixed collateral amount
    private const COLLATERAL_AMOUNT = 10000;

    public function __construct(
        string $publicKey,
        float $collateral = self::COLLATERAL_AMOUNT,
        string $status = 'active',
        int $isApproved = 0
    ) {
        $this->publicKey = $publicKey;
        $this->collateral = $collateral;
        $this->status = $status;
        $this->isApproved = $isApproved;
        $this->createdAt = time();
        $this->updatedAt = time();
    }

    /**
     * Save validator to database
     */
    public function save(): bool
    {
        $db = Database::getInstance()->getConnection();
        
        $sql = "INSERT INTO validators (public_key, collateral, status, is_approved) 
                VALUES (:public_key, :collateral, :status, :is_approved)
                ON DUPLICATE KEY UPDATE 
                collateral = VALUES(collateral),
                status = VALUES(status),
                is_approved = VALUES(is_approved),
                updated_at = CURRENT_TIMESTAMP";
        
        $stmt = $db->prepare($sql);
        
        return $stmt->execute([
            ':public_key' => $this->publicKey,
            ':collateral' => $this->collateral,
            ':status' => $this->status,
            ':is_approved' => $this->isApproved
        ]);
    }

    /**
     * Load validator from database by public key
     */
    public static function loadByPublicKey(string $publicKey): ?self
    {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT * FROM validators WHERE public_key = :public_key LIMIT 1";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':public_key' => $publicKey]);
        
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$record) {
            return null;
        }

        $validator = new self(
            $record['public_key'],
            (float)$record['collateral'],
            $record['status'],
            (int)$record['is_approved']
        );
        
        $validator->createdAt = strtotime($record['created_at']);
        $validator->updatedAt = strtotime($record['updated_at']);
        
        return $validator;
    }

    /**
     * Get all active validators
     */
    public static function getActiveValidators(): array
    {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT * FROM validators WHERE status = 'active' ORDER BY collateral DESC";
        
        $stmt = $db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all approved validators
     */
    public static function getApprovedValidators(): array
    {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT * FROM validators WHERE is_approved = 1 AND status = 'active' ORDER BY collateral DESC";
        
        $stmt = $db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all pending validators (waiting for approval)
     */
    public static function getPendingValidators(): array
    {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT * FROM validators WHERE is_approved = 0 AND status = 'active' ORDER BY created_at ASC";
        
        $stmt = $db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Approve validator
     */
    public function approve(): bool
    {
        $this->isApproved = 1;
        return $this->save();
    }

    /**
     * Slash validator (reduce collateral for misbehavior)
     */
    public function slash(float $amount): bool
    {
        if ($this->collateral >= $amount) {
            $this->collateral -= $amount;
            $this->save();
            return true;
        }
        return false;
    }

    /**
     * Set validator status
     */
    public function setStatus(string $status): self
    {
        if (in_array($status, ['active', 'inactive', 'slashed'])) {
            $this->status = $status;
        }
        return $this;
    }

    /**
     * Update collateral (called when genesis_allocation blocks are processed)
     */
    public function updateCollateral(float $collateral): self
    {
        $this->collateral = $collateral;
        return $this;
    }

    /**
     * Get validator stats
     */
    public static function getStats(): array
    {
        $db = Database::getInstance()->getConnection();
        
        $activeCount = $db->query("SELECT COUNT(*) as count FROM validators WHERE status = 'active'")->fetch()['count'];
        $approvedCount = $db->query("SELECT COUNT(*) as count FROM validators WHERE is_approved = 1")->fetch()['count'];
        $pendingCount = $db->query("SELECT COUNT(*) as count FROM validators WHERE is_approved = 0")->fetch()['count'];
        $totalCollateral = $db->query("SELECT SUM(collateral) as total FROM validators WHERE status = 'active'")->fetch()['total'] ?? 0;

        return [
            'active' => $activeCount,
            'approved' => $approvedCount,
            'pending' => $pendingCount,
            'totalCollateral' => (float)$totalCollateral
        ];
    }

    /**
     * Get validator data as array
     */
    public function toArray(): array
    {
        return [
            'publicKey' => $this->publicKey,
            'collateral' => $this->collateral,
            'status' => $this->status,
            'isApproved' => $this->isApproved === 1,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt
        ];
    }

    // Getters
    public function getPublicKey(): string { return $this->publicKey; }
    public function getCollateral(): float { return $this->collateral; }
    public function getStatus(): string { return $this->status; }
    public function isApproved(): bool { return $this->isApproved === 1; }
    public function getCreatedAt(): int { return $this->createdAt; }
    public function getUpdatedAt(): int { return $this->updatedAt; }
    
    public static function getCollateralAmount(): float { return self::COLLATERAL_AMOUNT; }
}
