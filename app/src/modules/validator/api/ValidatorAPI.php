<?php

namespace App\Modules\Validator;

use App\Lib\Logger;

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
            $stats = ValidatorManager::getStats();
            
            return [
                'success' => true,
                'data' => $stats
            ];
        } catch (\Exception $e) {
            Logger::error('Failed to get validator stats', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
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
}
