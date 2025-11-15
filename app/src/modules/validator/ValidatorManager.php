<?php

namespace App\Modules\Validator;

use App\Config\Database;
use App\Lib\Crypto;
use App\Lib\ValidatorSignatureHelper;
use App\Lib\Logger;
use App\Modules\Block\BlockChain;
use PDO;

class ValidatorManager
{
    /**
     * Register a new validator
     */
    public static function registerValidator(string $publicKey): ?Validator
    {
        // Check if validator already exists
        $existing = Validator::loadByPublicKey($publicKey);
        if ($existing) {
            return $existing;
        }

        $validator = new Validator($publicKey);
        if ($validator->save()) {
            return $validator;
        }

        return null;
    }

    /**
     * Register validator with signature verification and complete data
     * Used by both API and initialization
     */
    public static function registerValidatorWithSignature(
        string $publicKey,
        string $signature,
        string $ipAddress,
        bool $isApproved = true
    ): array {
        try {
            // Check if validator already exists
            if (self::validatorExists($publicKey)) {
                return [
                    'success' => false,
                    'error' => 'Validator already registered',
                    'validator' => self::getValidator($publicKey)->toArray()
                ];
            }

            // Get collateral from blockchain if not provided
            if ($collateral === null) {
                $blockchain = new BlockChain();
                $collateral = $blockchain->getCollateral();
            }

            // Prepare data for signature verification
            $dataToSign = json_encode([
                'type' => 'validator_registration',
                'public_key' => $publicKey,
                'ip_address' => $ipAddress,
                'collateral' => $collateral
            ]);

            // Verify the signature
            if (!Crypto::verifySignature($dataToSign, $signature, $publicKey)) {
                return [
                    'success' => false,
                    'error' => 'Invalid signature',
                    'public_key' => $publicKey
                ];
            }

            Logger::info('Signature verified for validator registration', [
                'public_key' => substr($publicKey, 0, 20) . '...'
            ]);

            // Register the validator
            $db = Database::getInstance()->getConnection();
            $sql = "INSERT INTO validators (public_key, ip_address, collateral, is_approved) 
                    VALUES (:public_key, :ip_address, :collateral, :is_approved)";
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                ':public_key' => $publicKey,
                ':ip_address' => $ipAddress,
                ':collateral' => $collateral,
                ':is_approved' => $isApproved ? 1 : 0
            ]);

            if (!$result) {
                return [
                    'success' => false,
                    'error' => 'Failed to register validator in database'
                ];
            }

            Logger::success('Validator registered successfully', [
                'public_key' => substr($publicKey, 0, 20) . '...',
                'ip_address' => $ipAddress,
                'collateral' => $collateral,
                'approved' => $isApproved
            ]);

            return [
                'success' => true,
                'message' => 'Validator registered successfully',
                'validator' => [
                    'public_key' => $publicKey,
                    'ip_address' => $ipAddress,
                    'collateral' => $collateral,
                    'is_approved' => $isApproved
                ]
            ];
        } catch (\Exception $e) {
            Logger::error('Failed to register validator with signature', [
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get validator by public key
     */
    public static function getValidator(string $publicKey): ?Validator
    {
        return Validator::loadByPublicKey($publicKey);
    }

    /**
     * Check if address is an approved validator
     */
    public static function isApprovedValidator(string $publicKey): bool
    {
        $validator = Validator::loadByPublicKey($publicKey);
        return $validator && $validator->isApproved();
    }

    /**
     * Approve validator (used in validation consensus)
     */
    public static function approveValidator(string $publicKey): bool
    {
        $validator = self::getValidator($publicKey);
        if ($validator) {
            return $validator->approve();
        }
        return false;
    }

    /**
     * Get random approved validator for block validation
     */
    public static function getRandomValidator(): ?Validator
    {
        $validators = Validator::getApprovedValidators();
        if (empty($validators)) {
            return null;
        }

        $random = $validators[array_rand($validators)];
        return Validator::loadByPublicKey($random['public_key']);
    }

    /**
     * Get validators with highest collateral (for sorting/priority)
     */
    public static function getValidatorsByCollateral(?int $limit = null): array
    {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT * FROM validators ORDER BY collateral DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . intval($limit);
        }
        
        $stmt = $db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all validators
     */
    public static function getAllValidators(): array
    {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT * FROM validators ORDER BY created_at DESC";
        
        $stmt = $db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Slash validator for misbehavior
     */
    public static function slashValidator(string $publicKey, float $amount): bool
    {
        $validator = self::getValidator($publicKey);
        if ($validator) {
            return $validator->slash($amount);
        }
        return false;
    }

    /**
     * Update validator collateral from genesis allocation block
     */
    public static function updateCollateralFromBlock(string $publicKey, float $collateral): bool
    {
        $validator = self::getValidator($publicKey);
        if ($validator) {
            $validator->updateCollateral($collateral);
            return $validator->save();
        }
        return false;
    }

    /**
     * Deactivate validator
     */
    public static function deactivateValidator(string $publicKey): bool
    {
        $validator = self::getValidator($publicKey);
        if ($validator) {
            return $validator->save();
        }
        return false;
    }

    /**
     * Get validator stats
     */
    public static function getStats(): array
    {
        return Validator::getStats();
    }

    /**
     * Check if validator exists
     */
    public static function validatorExists(string $publicKey): bool
    {
        return Validator::loadByPublicKey($publicKey) !== null;
    }

    /**
     * Count validators by status
     */
    public static function countByStatus(string $status): int
    {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT COUNT(*) as count FROM validators WHERE is_approved = :is_approved";
        
        $stmt = $db->prepare($sql);
        $isApproved = ($status === 'approved') ? 1 : 0;
        $stmt->execute([':is_approved' => $isApproved]);
        
        return (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }
}
