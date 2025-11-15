-- Initialize MariaDB database
-- This file runs automatically when the container starts for the first time

USE app_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Blockchain blocks table (Proof of Stake)
CREATE TABLE IF NOT EXISTS blocks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    index_num INT NOT NULL UNIQUE,
    hash VARCHAR(255) NOT NULL UNIQUE,
    previous_hash VARCHAR(255) NOT NULL,
    timestamp INT NOT NULL,
    merkle_root VARCHAR(255) NOT NULL,
    validator_address VARCHAR(50),
    data LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_index (index_num),
    INDEX idx_hash (hash),
    INDEX idx_previous_hash (previous_hash),
    INDEX idx_validator (validator_address)
);

-- Wallets table (synchronized with blockchain)
CREATE TABLE IF NOT EXISTS wallets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    address VARCHAR(50) NOT NULL UNIQUE,
    public_key VARCHAR(255) NOT NULL,
    private_key VARCHAR(255) NOT NULL,
    balance DECIMAL(20, 8) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_address (address),
    INDEX idx_balance (balance)
);

-- Transactions table
CREATE TABLE IF NOT EXISTS transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    from_address VARCHAR(50) NOT NULL,
    to_address VARCHAR(50) NOT NULL,
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
);

-- Validators table (Proof of Stake - collateral required)
CREATE TABLE IF NOT EXISTS validators (
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
);

-- Initial users (for demo)
INSERT INTO users (name, email) VALUES
('Alice', 'alice@example.com'),
('Bob', 'bob@example.com'),
('Charlie', 'charlie@example.com');
