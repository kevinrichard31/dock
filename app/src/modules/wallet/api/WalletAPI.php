<?php

namespace App\Modules\Wallet\API;

use App\Config\Database;

class WalletAPI
{
    /**
     * Get wallet stats
     */
    public static function getStats(): array
    {
        $db = Database::getInstance()->getConnection();
        
        try {
            $walletsCount = $db->query("SELECT COUNT(*) as count FROM wallets")->fetch()['count'];
            $totalBalance = $db->query("SELECT SUM(balance) as total FROM wallets")->fetch()['total'] ?? 0;
            $transactionsCount = $db->query("SELECT COUNT(*) as count FROM transactions")->fetch()['count'];

            return [
                'wallets' => (int)$walletsCount,
                'totalBalance' => (float)$totalBalance,
                'transactions' => (int)$transactionsCount
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get all wallets (public info only)
     */
    public static function getAllWallets(): array
    {
        $db = Database::getInstance()->getConnection();
        
        try {
            $sql = "SELECT * FROM wallets ORDER BY user_id ASC";
            $stmt = $db->query($sql);
            $wallets = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Remove sensitive data (private keys)
            return array_map(function($wallet) {
                unset($wallet['private_key']);
                return $wallet;
            }, $wallets);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get wallet by address (public info only)
     */
    public static function getWalletByAddress(string $address): ?array
    {
        $db = Database::getInstance()->getConnection();
        
        try {
            $sql = "SELECT * FROM wallets WHERE address = :address LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->execute([':address' => $address]);
            $wallet = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$wallet) {
                return null;
            }
            
            return [
                'address' => $wallet['address'],
                'balance' => $wallet['balance'],
                'transactions' => 0
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get wallet balance
     */
    public static function getBalance(string $address): ?float
    {
        $db = Database::getInstance()->getConnection();
        
        try {
            $sql = "SELECT balance FROM wallets WHERE address = :address LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->execute([':address' => $address]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result ? (float)$result['balance'] : null;
        } catch (\Exception $e) {
            return null;
        }
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
