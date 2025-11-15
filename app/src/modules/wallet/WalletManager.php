<?php

namespace App\Modules\Wallet;

use App\Config\Database;
use PDO;

class WalletManager
{
    /**
     * Create wallet for user
     */
    public static function createWallet(int $userId): Wallet
    {
        $wallet = new Wallet($userId);
        $wallet->save();
        return $wallet;
    }

    /**
     * Create system wallet (user_id = 0)
     */
    public static function createSystemWallet(): Wallet
    {
        $wallet = new Wallet(0); // user_id = 0 for system
        $wallet->save();
        return $wallet;
    }

    /**
     * Get user wallet
     */
    public static function getUserWallet(int $userId): ?Wallet
    {
        return Wallet::loadFromDatabase($userId);
    }

    /**
     * Get wallet by address
     */
    public static function getWalletByAddress(string $address): ?Wallet
    {
        return Wallet::loadByAddress($address);
    }

    /**
     * Transfer funds between wallets
     */
    public static function transfer(string $fromAddress, string $toAddress, float $amount): bool
    {
        $fromWallet = self::getWalletByAddress($fromAddress);
        $toWallet = self::getWalletByAddress($toAddress);

        if (!$fromWallet || !$toWallet) {
            return false;
        }

        $transaction = $fromWallet->createTransaction($toAddress, $amount);
        if (!$transaction) {
            return false;
        }

        $toWallet->addBalance($amount);
        
        $fromWallet->save();
        $toWallet->save();

        // Log transaction
        self::logTransaction($transaction);

        return true;
    }

    /**
     * Log transaction to database
     */
    private static function logTransaction(array $transaction): void
    {
        $db = Database::getInstance()->getConnection();
        
        $sql = "INSERT INTO transactions (from_address, to_address, amount, hash, timestamp) 
                VALUES (:from, :to, :amount, :hash, :timestamp)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':from' => $transaction['from'],
            ':to' => $transaction['to'],
            ':amount' => $transaction['amount'],
            ':hash' => $transaction['hash'],
            ':timestamp' => $transaction['timestamp']
        ]);
    }

    /**
     * Get all wallets
     */
    public static function getAllWallets(): array
    {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT * FROM wallets ORDER BY user_id ASC";
        
        $stmt = $db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get wallet balance
     */
    public static function getBalance(string $address): float
    {
        $wallet = self::getWalletByAddress($address);
        return $wallet ? $wallet->getBalance() : 0;
    }

    /**
     * Get wallet stats
     */
    public static function getStats(): array
    {
        $db = Database::getInstance()->getConnection();
        
        $walletsCount = $db->query("SELECT COUNT(*) as count FROM wallets")->fetch()['count'];
        $totalBalance = $db->query("SELECT SUM(balance) as total FROM wallets")->fetch()['total'] ?? 0;
        $transactionsCount = $db->query("SELECT COUNT(*) as count FROM transactions")->fetch()['count'];

        return [
            'wallets' => $walletsCount,
            'totalBalance' => (float)$totalBalance,
            'transactions' => $transactionsCount
        ];
    }
}
