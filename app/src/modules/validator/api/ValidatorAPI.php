<?php

namespace App\Modules\Validator\Api;

use App\Lib\Logger;
use App\Lib\ValidatorSignatureHelper;
use App\Modules\Validator\ValidatorManager;
use App\Modules\Validator\Validator;

/**
 * ValidatorAPI
 * Gère les opérations API des validateurs
 */
class ValidatorAPI
{
    /**
     * Get all validators
     */
    public static function getValidators(): array
    {
        try {
            $validators = ValidatorManager::getAllValidators();
            
            return [
                'success' => true,
                'data' => $validators,
                'count' => count($validators)
            ];
        } catch (\Exception $e) {
            Logger::error('Failed to get validators', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get approved validators only
     */
    public static function getApprovedValidators(): array
    {
        try {
            $validators = Validator::getApprovedValidators();
            
            return [
                'success' => true,
                'data' => $validators,
                'count' => count($validators)
            ];
        } catch (\Exception $e) {
            Logger::error('Failed to get approved validators', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get pending validators (awaiting approval)
     */
    public static function getPendingValidators(): array
    {
        try {
            $validators = Validator::getPendingValidators();
            
            return [
                'success' => true,
                'data' => $validators,
                'count' => count($validators)
            ];
        } catch (\Exception $e) {
            Logger::error('Failed to get pending validators', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get validator by public key
     */
    public static function getValidator(string $publicKey): array
    {
        try {
            $validator = ValidatorManager::getValidator($publicKey);
            
            if (!$validator) {
                return [
                    'success' => false,
                    'error' => 'Validator not found'
                ];
            }
            
            return [
                'success' => true,
                'data' => $validator->toArray()
            ];
        } catch (\Exception $e) {
            Logger::error('Failed to get validator', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Register new validator request
     */
    public static function registerValidatorRequest(string $publicKey): array
    {
        try {
            // Check if already exists
            if (ValidatorManager::validatorExists($publicKey)) {
                $validator = ValidatorManager::getValidator($publicKey);
                return [
                    'success' => false,
                    'error' => 'Validator already registered',
                    'status' => $validator->getStatus(),
                    'approved' => $validator->isApproved()
                ];
            }

            // Register new validator
            $validator = ValidatorManager::registerValidator($publicKey);
            
            if ($validator) {
                return [
                    'success' => true,
                    'message' => 'Validator registration request submitted',
                    'data' => $validator->toArray(),
                    'collateral_required' => Validator::getCollateralAmount()
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Failed to register validator'
            ];
        } catch (\Exception $e) {
            Logger::error('Failed to register validator', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get validator statistics
     */
    public static function getStats(): array
    {
        try {
            return ValidatorManager::getStats();
        } catch (\Exception $e) {
            Logger::error('Failed to get validator stats', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Check if is approved validator
     */
    public static function isApprovedValidator(string $publicKey): array
    {
        try {
            $isApproved = ValidatorManager::isApprovedValidator($publicKey);
            
            return [
                'success' => true,
                'is_approved' => $isApproved,
                'public_key' => $publicKey
            ];
        } catch (\Exception $e) {
            Logger::error('Failed to check validator status', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Submit wallet as validator (capture IP address and verify signature)
     */
    public static function submitWalletAsValidator(string $publicKey, string $signature = ''): array
    {
        try {
            if (empty($publicKey)) {
                return [
                    'success' => false,
                    'error' => 'Public key is required'
                ];
            }

            if (empty($signature)) {
                return [
                    'success' => false,
                    'error' => 'Signature is required'
                ];
            }

            // Get client IP from the HTTP request
            $ipAddress = self::getClientIP();

            // Prepare registration data for signature verification
            $registrationData = [
                'type' => 'validator_registration',
                'public_key' => $publicKey,
                'ip' => $ipAddress,
                'signature' => $signature
            ];

            // Verify the signature
            if (!ValidatorSignatureHelper::verifyRegistration($registrationData)) {
                return [
                    'success' => false,
                    'error' => 'Invalid signature'
                ];
            }

            // Check if validator already exists
            if (ValidatorManager::validatorExists($publicKey)) {
                return [
                    'success' => false,
                    'error' => 'Wallet already registered as validator'
                ];
            }

            // Register validator with IP
            $validator = ValidatorManager::registerValidator($publicKey);
            
            if ($validator) {
                // Store IP address in validator data
                $validator->setIpAddress($ipAddress);
                
                return [
                    'success' => true,
                    'message' => 'Wallet submitted as validator',
                    'data' => $validator->toArray(),
                    'ipaddress' => $ipAddress,
                    'collateral_required' => Validator::getCollateralAmount()
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Failed to submit wallet as validator'
            ];
        } catch (\Exception $e) {
            Logger::error('Failed to submit wallet as validator', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get client IP address
     */
    private static function getClientIP(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? '';
        }
    }
}
