<?php

namespace App\Modules\Validator\Api;

/**
 * ValidatorRouter
 * Routes les requêtes API pour les validateurs
 */
class ValidatorRouter
{
    /**
     * Route API request
     */
    public static function route(array $request): array
    {
        $action = $request['action'] ?? null;
        $method = $request['method'] ?? 'GET';

        return match ($action) {
            'get_all' => ValidatorAPI::getValidators(),
            'get_approved' => ValidatorAPI::getApprovedValidators(),
            'get_pending' => ValidatorAPI::getPendingValidators(),
            'get_validator' => ValidatorAPI::getValidator($request['public_key'] ?? ''),
            'register' => ValidatorAPI::registerValidatorRequest($request['public_key'] ?? ''),
            'submit_wallet' => ValidatorAPI::submitWalletAsValidator($request['public_key'] ?? '', $request['signature'] ?? ''),
            'stats' => ValidatorAPI::getStats(),
            'is_approved' => ValidatorAPI::isApprovedValidator($request['public_key'] ?? ''),
            default => [
                'success' => false,
                'error' => 'Unknown action: ' . $action
            ]
        };
    }
}
