<?php

namespace App\Modules\Block;

class BlockAPI
{
    /**
     * Get blockchain stats
     */
    public static function getStats(): array
    {
        $blockchain = new BlockChain();
        return $blockchain->getStats();
    }

    /**
     * Get all blocks
     */
    public static function getAllBlocks(): array
    {
        $blockchain = new BlockChain();
        $blocks = [];
        
        for ($i = 0; $i < $blockchain->getLength(); $i++) {
            $block = $blockchain->getBlock($i);
            if ($block) {
                $blocks[] = $block->toArray();
            }
        }
        
        return $blocks;
    }

    /**
     * Get block by index
     */
    public static function getBlockByIndex(int $index): ?array
    {
        $blockchain = new BlockChain();
        $block = $blockchain->getBlock($index);
        
        return $block ? $block->toArray() : null;
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
