<?php

namespace App\Modules\Wallet\Views;

use App\Config\Database;

class WalletController
{
    /**
     * Get all wallets for view
     */
    public static function getWallets(): array
    {
        try {
            $db = Database::getInstance()->getConnection();
            
            $sql = "SELECT 
                        w.id,
                        w.user_id,
                        w.public_key,
                        w.private_key,
                        w.balance,
                        w.created_at
                    FROM wallets w
                    ORDER BY w.user_id ASC";
            
            $stmt = $db->query($sql);
            $wallets = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            return ['status' => 'success', 'data' => $wallets];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage(), 'data' => []];
        }
    }
}
