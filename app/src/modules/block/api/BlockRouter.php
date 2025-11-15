<?php

namespace App\Modules\Block\API;

class BlockRouter
{
    private string $method;
    private string $path;

    public function __construct(string $method, string $path)
    {
        $this->method = $method;
        $this->path = $path;
    }

    /**
     * Route block endpoints
     */
    public function route(): array
    {
        if ($this->method !== 'GET') {
            http_response_code(405);
            return BlockAPI::error('Method not allowed', 405);
        }

        // GET /api/blocks
        if ($this->path === 'blocks') {
            $blocks = BlockAPI::getAllBlocks();
            return BlockAPI::response('success', ['count' => count($blocks), 'blocks' => $blocks]);
        }

        // GET /api/blocks/{index}
        $parts = explode('/', $this->path);
        if (count($parts) === 2 && $parts[0] === 'blocks' && is_numeric($parts[1])) {
            $index = (int)$parts[1];
            $block = BlockAPI::getBlockByIndex($index);
            
            if (!$block) {
                http_response_code(404);
                return BlockAPI::error('Block not found', 404);
            }
            
            return BlockAPI::response('success', $block);
        }

        http_response_code(404);
        return BlockAPI::error('Block endpoint not found', 404);
    }

    /**
     * Check if path matches block routes
     */
    public static function matches(string $path): bool
    {
        return strpos($path, 'blocks') === 0;
    }
}
