<?php

/**
 * 00_reset.php
 * ÉTAPE 0: Réinitialiser la base de données - DROP et recréer toutes les tables
 * 
 * Cette étape s'exécute en premier et:
 * - Supprime toutes les tables existantes
 * - Recréé les tables vides
 */

namespace App\Init;

use App\Config\Database;
use App\Lib\Logger;
use PDO;

class InitReset
{
    public static function execute(): void
    {
        Logger::info('=== ÉTAPE 0: Réinitialisation de la Base de Données ===');

        try {
            $db = Database::getInstance()->getConnection();

            // Désactiver les contraintes de clé étrangère
            $db->exec('SET FOREIGN_KEY_CHECKS=0');

            // Récupérer toutes les tables
            $stmt = $db->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Supprimer toutes les tables
            foreach ($tables as $table) {
                Logger::info('Dropping table', ['table' => $table]);
                $db->exec("DROP TABLE IF EXISTS `$table`");
            }

            Logger::success('All tables dropped', ['count' => count($tables)]);

            // Réactiver les contraintes de clé étrangère
            $db->exec('SET FOREIGN_KEY_CHECKS=1');

            // Recréer les tables via SQL directement
            Logger::info('Recreating tables...');
            
            $tables = [
                // Users table
                "CREATE TABLE IF NOT EXISTS users (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    name VARCHAR(100) NOT NULL,
                    email VARCHAR(100) NOT NULL UNIQUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )",
                
                // Blockchain blocks table (Proof of Stake)
                "CREATE TABLE IF NOT EXISTS blocks (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    index_num INT NOT NULL UNIQUE,
                    hash VARCHAR(255) NOT NULL UNIQUE,
                    previous_hash VARCHAR(255) NOT NULL,
                    timestamp INT NOT NULL,
                    merkle_root VARCHAR(255) NOT NULL,
                    validator_address VARCHAR(255),
                    data LONGTEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_index (index_num),
                    INDEX idx_hash (hash),
                    INDEX idx_previous_hash (previous_hash),
                    INDEX idx_validator (validator_address)
                )",
                
                // Wallets table (synchronized with blockchain)
                "CREATE TABLE IF NOT EXISTS wallets (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    user_id INT NOT NULL UNIQUE,
                    public_key VARCHAR(255) NOT NULL UNIQUE,
                    private_key VARCHAR(255) NOT NULL,
                    balance DECIMAL(20, 8) NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id),
                    INDEX idx_public_key (public_key),
                    INDEX idx_balance (balance)
                )",
                
                // Transactions table
                "CREATE TABLE IF NOT EXISTS transactions (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    from_address VARCHAR(255) NOT NULL,
                    to_address VARCHAR(255) NOT NULL,
                    amount DECIMAL(20, 8) NOT NULL,
                    hash VARCHAR(255) NOT NULL UNIQUE,
                    block_index INT,
                    timestamp INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (block_index) REFERENCES blocks(index_num),
                    INDEX idx_from (from_address),
                    INDEX idx_to (to_address),
                    INDEX idx_block (block_index),
                    INDEX idx_timestamp (timestamp)
                )",
                
                // Validators table (Proof of Stake - collateral required)
                "CREATE TABLE IF NOT EXISTS validators (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    public_key VARCHAR(255) NOT NULL UNIQUE,
                    collateral DECIMAL(20, 8) NOT NULL DEFAULT 10000,
                    ip_address VARCHAR(45) NOT NULL DEFAULT '127.0.0.1',
                    is_approved INT NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_public_key (public_key),
                    INDEX idx_ip_address (ip_address),
                    INDEX idx_is_approved (is_approved)
                )",
                
                // Transactions Queue table
                "CREATE TABLE IF NOT EXISTS queue (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    from_address VARCHAR(255) NOT NULL,
                    to_address VARCHAR(255) NOT NULL,
                    amount DECIMAL(20, 8) NOT NULL,
                    hash VARCHAR(255) NOT NULL UNIQUE,
                    status ENUM('pending', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'pending',
                    block_index INT,
                    timestamp INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (block_index) REFERENCES blocks(index_num),
                    INDEX idx_status (status),
                    INDEX idx_from (from_address),
                    INDEX idx_to (to_address),
                    INDEX idx_hash (hash)
                )"
            ];
            
            foreach ($tables as $sql) {
                $db->exec($sql);
            }
            
            Logger::success('Tables recreated successfully');

        } catch (\Exception $e) {
            Logger::error('Failed to reset database', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
