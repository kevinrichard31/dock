<?php

/**
 * TransactionManager.php
 * Gestionnaire des transactions
 */

namespace App\Modules\Transaction;

use App\Config\Database;
use PDO;

class TransactionManager
{
    public static function createTransaction(
        string $fromAddress,
        string $toAddress,
        float $amount,
        string $hash,
        int $timestamp,
        ?int $blockIndex = null
    ): ?Transaction {
        try {
            $transaction = new Transaction(
                $fromAddress,
                $toAddress,
                $amount,
                $hash,
                $timestamp,
                $blockIndex
            );

            if ($transaction->save()) {
                return $transaction;
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }

    public static function transactionExists(string $hash): bool
    {
        try {
            $db = Database::getInstance()->getConnection();
            $sql = "SELECT COUNT(*) FROM transactions WHERE hash = :hash";
            $stmt = $db->prepare($sql);
            $stmt->execute([':hash' => $hash]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function getTransactionCount(): int
    {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT COUNT(*) FROM transactions");
            return (int)$stmt->fetchColumn();
        } catch (\Exception $e) {
            return 0;
        }
    }

    public static function getTransactionsByBlock(int $blockIndex): array
    {
        try {
            $db = Database::getInstance()->getConnection();
            $sql = "SELECT * FROM transactions WHERE block_index = :block_index ORDER BY id ASC";
            $stmt = $db->prepare($sql);
            $stmt->execute([':block_index' => $blockIndex]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $transactions = [];
            foreach ($rows as $row) {
                $transactions[] = new Transaction(
                    $row['from_address'],
                    $row['to_address'],
                    (float)$row['amount'],
                    $row['hash'],
                    (int)$row['timestamp'],
                    (int)$row['block_index'],
                    (int)$row['id']
                );
            }

            return $transactions;
        } catch (\Exception $e) {
            return [];
        }
    }

    public static function getPendingTransactions(): array
    {
        try {
            $db = Database::getInstance()->getConnection();
            $sql = "SELECT * FROM transactions WHERE block_index IS NULL ORDER BY timestamp ASC";
            $stmt = $db->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $transactions = [];
            foreach ($rows as $row) {
                $transactions[] = new Transaction(
                    $row['from_address'],
                    $row['to_address'],
                    (float)$row['amount'],
                    $row['hash'],
                    (int)$row['timestamp'],
                    null,
                    (int)$row['id']
                );
            }

            return $transactions;
        } catch (\Exception $e) {
            return [];
        }
    }

    public static function getWalletBalance(string $address): float
    {
        try {
            $db = Database::getInstance()->getConnection();

            // Somme des envois
            $sqlOut = "SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE from_address = :address";
            $stmtOut = $db->prepare($sqlOut);
            $stmtOut->execute([':address' => $address]);
            $outTotal = (float)$stmtOut->fetchColumn();

            // Somme des réceptions
            $sqlIn = "SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE to_address = :address";
            $stmtIn = $db->prepare($sqlIn);
            $stmtIn->execute([':address' => $address]);
            $inTotal = (float)$stmtIn->fetchColumn();

            return $inTotal - $outTotal;
        } catch (\Exception $e) {
            return 0;
        }
    }
}
