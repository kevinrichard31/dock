<?php

namespace App\Modules\Block;

use App\Config\Database;
use PDO;

class BlockChain
{
    private array $chain = [];
    private int $difficulty = 4;

    public function __construct(int $difficulty = 4)
    {
        $this->difficulty = $difficulty;
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
     * Add a new block to the chain
     */
    public function addBlock(array $transactions): Block
    {
        $previousBlock = $this->getLatestBlock();
        $previousHash = $previousBlock ? $previousBlock->getHash() : '0';
        $index = count($this->chain);

        $block = new Block($index, $previousHash, $transactions, $this->difficulty);
        $block->mineBlock();

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
     * Create genesis block
     */
    public static function createGenesisBlock(): Block
    {
        return new Block(
            0,
            '0',
            [['type' => 'genesis', 'data' => 'Genesis Block']],
            4
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
            'difficulty' => $this->difficulty,
            'isValid' => $this->isValid()
        ];
    }
}
