<?php

/**
 * TransactionRouter.php
 * Routeur API pour les transactions
 */

namespace App\Modules\Transaction\API;

use App\API\Router;

class TransactionRouter
{
    public static function register(Router $router): void
    {
        // GET /api/transactions - Récupérer toutes les transactions
        $router->get('/transactions', function () {
            return [
                'success' => true,
                'data' => TransactionAPI::getAll()
            ];
        });

        // GET /api/transactions/:id - Récupérer une transaction par ID
        $router->get('/transactions/:id', function ($id) {
            $transaction = TransactionAPI::getById((int)$id);
            if (!$transaction) {
                return [
                    'success' => false,
                    'error' => 'Transaction not found'
                ];
            }
            return [
                'success' => true,
                'data' => $transaction
            ];
        });

        // GET /api/transactions/hash/:hash - Récupérer une transaction par hash
        $router->get('/transactions/hash/:hash', function ($hash) {
            $transaction = TransactionAPI::getByHash($hash);
            if (!$transaction) {
                return [
                    'success' => false,
                    'error' => 'Transaction not found'
                ];
            }
            return [
                'success' => true,
                'data' => $transaction
            ];
        });

        // GET /api/transactions/address/:address - Récupérer les transactions d'une adresse
        $router->get('/transactions/address/:address', function ($address) {
            return [
                'success' => true,
                'data' => TransactionAPI::getByAddress($address)
            ];
        });

        // GET /api/transactions/stats - Récupérer les stats des transactions
        $router->get('/transactions/stats', function () {
            return [
                'success' => true,
                'data' => TransactionAPI::getStats()
            ];
        });

        // GET /api/transactions/balance/:address - Récupérer le solde d'une adresse
        $router->get('/transactions/balance/:address', function ($address) {
            return [
                'success' => true,
                'data' => [
                    'address' => $address,
                    'balance' => TransactionAPI::getWalletBalance($address)
                ]
            ];
        });
    }
}
