<?php

namespace App\Modules\Block;

use App\Config\Database;
use PDO;

class BlockChain
{
    private array $chain = [];

    public function __construct()
    {
        $this->loadFromDatabase();
    }

    /**
     * Load blockchain from database
     */
    private function loadFromDatabase(): void
    {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT * FROM blocks ORDER BY index_num ASC";
        $stmt = $db->query($sql);
        
        $blocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($blocks as $blockData) {
            $this->chain[] = Block::fromDatabase($blockData);
        }
    }

    /**
     * Get the latest block
     */
    public function getLatestBlock(): ?Block
    {
        return end($this->chain) ?: null;
    }

    /**
     * Add a new block to the chain (Proof of Stake)
     */
    public function addBlock(array $transactions, ?string $validatorAddress = null): Block
    {
        $previousBlock = $this->getLatestBlock();
        $previousHash = $previousBlock ? $previousBlock->getHash() : '0';
        $index = count($this->chain);

        $block = new Block($index, $previousHash, $transactions, $validatorAddress);

        if ($block->save()) {
            $this->chain[] = $block;
            return $block;
        }

        throw new \Exception('Failed to save block to database');
    }

    /**
     * Get block by index
     */
    public function getBlock(int $index): ?Block
    {
        return $this->chain[$index] ?? null;
    }

    /**
     * Get all blocks
     */
    public function getChain(): array
    {
        return $this->chain;
    }

    /**
     * Get chain length
     */
    public function getLength(): int
    {
        return count($this->chain);
    }

    /**
     * Verify entire blockchain integrity
     */
    public function isValid(): bool
    {
        for ($i = 1; $i < count($this->chain); $i++) {
            $currentBlock = $this->chain[$i];
            $previousBlock = $this->chain[$i - 1];

            if (!$currentBlock->isValid()) {
                return false;
            }

            if ($currentBlock->getPreviousHash() !== $previousBlock->getHash()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create genesis block (Proof of Stake)
     */
    public static function createGenesisBlock(): Block
    {
        return new Block(
            0,
            '0',
            [['type' => 'genesis', 'data' => 'Genesis Block']],
            null // No validator for genesis
        );
    }

    /**
     * Get chain stats
     */
    public function getStats(): array
    {
        $totalTransactions = 0;
        foreach ($this->chain as $block) {
            $totalTransactions += count($block->getTransactions());
        }

        return [
            'length' => $this->getLength(),
            'totalTransactions' => $totalTransactions,
            'consensus' => 'Proof of Stake',
            'isValid' => $this->isValid()
        ];
    }

    /**
     * Get collateral amount for validators from genesis block
     * Looks for the default collateral in genesis allocation blocks
     * Returns the most recent collateral found
     */
    public function getCollateral(): float
    {
        $collateral = null;

        // Search through all blocks for collateral information (get the most recent)
        foreach ($this->chain as $block) {
            $transactions = $block->getTransactions();
            
            foreach ($transactions as $transaction) {
                // Check genesis allocation blocks
                if ($transaction['type'] === 'genesis_allocation' && isset($transaction['collateral'])) {
                    $collateral = (float)$transaction['collateral'];
                }
            }
        }

        if ($collateral !== null) {
            return $collateral;
        }

        // throw exception if no collateral found
        throw new \Exception('No collateral information found in genesis block.');
    }
}

