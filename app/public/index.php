<?php

/**
 * Example usage of the Blockchain system
 * 
 * After initialization, you can use the modules like this
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use App\Modules\Block\BlockChain;
use App\Modules\Wallet\WalletManager;

// Initialize database connection
$db = Database::getInstance();

echo "╔════════════════════════════════════════════════╗\n";
echo "║   Blockchain System - Usage Example           ║\n";
echo "╚════════════════════════════════════════════════╝\n\n";

// Get blockchain stats
echo "=== Blockchain Statistics ===\n";
$blockchain = new BlockChain();
$stats = $blockchain->getStats();
echo "Blocks in chain: " . $stats['length'] . "\n";
echo "Total transactions: " . $stats['totalTransactions'] . "\n";
echo "Blockchain valid: " . ($stats['isValid'] ? 'Yes' : 'No') . "\n\n";

// Get wallet stats
echo "=== Wallet Statistics ===\n";
$walletStats = WalletManager::getStats();
echo "Total wallets: " . $walletStats['wallets'] . "\n";
echo "Total balance: " . $walletStats['totalBalance'] . "\n";
echo "Total transactions: " . $walletStats['transactions'] . "\n\n";

// List all wallets
echo "=== Wallets ===\n";
$wallets = WalletManager::getAllWallets();
foreach ($wallets as $wallet) {
    echo "User ID: {$wallet['user_id']}\n";
    echo "Address: {$wallet['address']}\n";
    echo "Balance: {$wallet['balance']}\n";
    echo "Created: {$wallet['created_at']}\n\n";
}

// List all blocks
echo "=== Blockchain ===\n";
for ($i = 0; $i < $blockchain->getLength(); $i++) {
    $block = $blockchain->getBlock($i);
    if ($block) {
        echo "Block #{$block->getIndex()}\n";
        echo "Hash: {$block->getHash()}\n";
        echo "Previous: {$block->getPreviousHash()}\n";
        echo "Timestamp: " . date('Y-m-d H:i:s', $block->getTimestamp()) . "\n";
        echo "Transactions: " . count($block->getTransactions()) . "\n";
        echo "Nonce: {$block->getNonce()}\n\n";
    }
}
