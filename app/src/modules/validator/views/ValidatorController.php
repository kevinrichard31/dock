<?php

namespace App\Modules\Validator\Views;

use App\Lib\Logger;
use App\Modules\Validator\Validator;
use App\Modules\Validator\ValidatorManager;

/**
 * ValidatorController
 * Gère l'affichage des pages de validateurs
 */
class ValidatorController
{
    /**
     * Afficher la liste des validateurs
     */
    public static function listValidators(): array
    {
        try {
            $validators = ValidatorManager::getAllValidators();
            $stats = ValidatorManager::getStats();

            return [
                'success' => true,
                'validators' => $validators,
                'stats' => $stats,
                'collateral_required' => Validator::getCollateralAmount()
            ];
        } catch (\Exception $e) {
            Logger::error('Failed to load validators list', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Afficher le formulaire pour devenir validateur
     */
    public static function requestValidatorForm(): array
    {
        try {
            $stats = ValidatorManager::getStats();

            return [
                'success' => true,
                'form' => 'request_validator',
                'collateral_required' => Validator::getCollateralAmount(),
                'stats' => $stats
            ];
        } catch (\Exception $e) {
            Logger::error('Failed to load validator form', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Afficher les détails d'un validateur
     */
    public static function viewValidator(string $publicKey): array
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
                'validator' => $validator->toArray()
            ];
        } catch (\Exception $e) {
            Logger::error('Failed to load validator details', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Afficher la liste des validateurs en attente d'approbation
     */
    public static function listPendingValidators(): array
    {
        try {
            $validators = Validator::getPendingValidators();

            return [
                'success' => true,
                'validators' => $validators,
                'count' => count($validators)
            ];
        } catch (\Exception $e) {
            Logger::error('Failed to load pending validators', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Afficher la liste des validateurs approuvés
     */
    public static function listApprovedValidators(): array
    {
        try {
            $validators = Validator::getApprovedValidators();

            return [
                'success' => true,
                'validators' => $validators,
                'count' => count($validators)
            ];
        } catch (\Exception $e) {
            Logger::error('Failed to load approved validators', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
