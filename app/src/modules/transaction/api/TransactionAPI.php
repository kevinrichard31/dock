<?php

/**
 * TransactionAPI.php
 * API REST pour les transactions
 */

namespace App\Modules\Transaction\API;

use App\Modules\Transaction\Transaction;
use App\Modules\Transaction\TransactionManager;

class TransactionAPI
{
    public static function getAll(): array
    {
        $transactions = Transaction::getAll();
        return array_map(fn($t) => $t->toArray(), $transactions);
    }

    public static function getById(int $id): ?array
    {
        $transaction = Transaction::findById($id);
        return $transaction ? $transaction->toArray() : null;
    }

    public static function getByHash(string $hash): ?array
    {
        $transaction = Transaction::findByHash($hash);
        return $transaction ? $transaction->toArray() : null;
    }

    public static function getByAddress(string $address, string $direction = 'both'): array
    {
        $transactions = Transaction::findByAddress($address, $direction);
        return array_map(fn($t) => $t->toArray(), $transactions);
    }

    public static function getStats(): array
    {
        return [
            'total_transactions' => TransactionManager::getTransactionCount(),
            'pending_transactions' => count(TransactionManager::getPendingTransactions())
        ];
    }

    public static function getWalletBalance(string $address): float
    {
        return TransactionManager::getWalletBalance($address);
    }
}
