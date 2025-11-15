<?php

namespace App\Modules\Block\Views;

use App\Modules\Block\BlockChain;

class BlockController
{
    /**
     * Get all blocks for view
     */
    public static function getBlocks(): array
    {
        try {
            $blockchain = new BlockChain();
            $blocks = [];
            
            for ($i = 0; $i < $blockchain->getLength(); $i++) {
                $block = $blockchain->getBlock($i);
                if ($block) {
                    $blocks[] = [
                        'index_num' => $block->getIndex(),
                        'hash' => $block->getHash(),
                        'previous_hash' => $block->getPreviousHash(),
                        'timestamp' => $block->getTimestamp(),
                        'merkle_root' => $block->getMerkleRoot(),
                        'validator_address' => $block->getValidatorAddress(),
                        'transactions_count' => count($block->getTransactions()),
                        'data' => json_encode($block->getTransactions())
                    ];
                }
            }
            
            return ['status' => 'success', 'data' => $blocks];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage(), 'data' => []];
        }
    }
}
