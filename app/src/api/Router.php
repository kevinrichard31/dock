<?php

namespace App\API;

use App\Modules\Block\BlockRouter;
use App\Modules\Block\BlockAPI;
use App\Modules\Wallet\WalletRouter;
use App\Modules\Wallet\WalletAPI;
use App\Modules\Init\Api\InitRouter;

class Router
{
    private string $method;
    private string $path;

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $this->path = str_replace('/api/', '', $this->path);
    }

    /**
     * Route the request to the appropriate handler
     */
    public function route(): array
    {
        try {
            // Route to appropriate module router
            if (InitRouter::matches($this->path)) {
                return InitRouter::route($this->path);
            } elseif (BlockRouter::matches($this->path)) {
                $router = new BlockRouter($this->method, $this->path);
                return $router->route();
            } elseif (WalletRouter::matches($this->path)) {
                $router = new WalletRouter($this->method, $this->path);
                return $router->route();
            } elseif ($this->path === 'stats' || $this->path === '') {
                return $this->getStats();
            } else {
                http_response_code(404);
                return [
                    'status' => 'error',
                    'message' => 'Endpoint not found',
                    'available' => [
                        'GET /api/stats',
                        'GET /api/blocks',
                        'GET /api/blocks/{index}',
                        'GET /api/wallets',
                        'GET /api/wallets/{address}',
                        'GET /api/wallets/{address}/balance',
                        'POST /api/init - Run full initialization',
                        'POST /api/init/blocks - Initialize blockchain',
                        'POST /api/init/wallets - Initialize wallets'
                    ]
                ];
            }
        } catch (\Exception $e) {
            http_response_code(500);
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get combined stats
     */
    private function getStats(): array
    {
        return [
            'status' => 'success',
            'blockchain' => BlockAPI::getStats(),
            'wallets' => WalletAPI::getStats()
        ];
    }
}
