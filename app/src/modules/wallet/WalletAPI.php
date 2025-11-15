<?php

namespace App\Modules\Wallet;

class WalletAPI
{
    /**
     * Get wallet stats
     */
    public static function getStats(): array
    {
        return WalletManager::getStats();
    }

    /**
     * Get all wallets (public info only)
     */
    public static function getAllWallets(): array
    {
        $wallets = WalletManager::getAllWallets();
        
        // Remove sensitive data (private keys)
        return array_map(function($wallet) {
            unset($wallet['private_key']);
            return $wallet;
        }, $wallets);
    }

    /**
     * Get wallet by address (public info only)
     */
    public static function getWalletByAddress(string $address): ?array
    {
        $wallet = WalletManager::getWalletByAddress($address);
        
        if (!$wallet) {
            return null;
        }
        
        return [
            'address' => $wallet->getAddress(),
            'balance' => $wallet->getBalance(),
            'transactions' => count($wallet->getTransactions())
        ];
    }

    /**
     * Get wallet balance
     */
    public static function getBalance(string $address): ?float
    {
        $wallet = WalletManager::getWalletByAddress($address);
        return $wallet ? $wallet->getBalance() : null;
    }

    /**
     * Format API response
     */
    public static function response(string $status, $data = null, string $message = null): array
    {
        $response = ['status' => $status];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        if ($message !== null) {
            $response['message'] = $message;
        }
        
        return $response;
    }

    /**
     * Format error response
     */
    public static function error(string $message, int $code = 400): array
    {
        http_response_code($code);
        return self::response('error', null, $message);
    }
}
