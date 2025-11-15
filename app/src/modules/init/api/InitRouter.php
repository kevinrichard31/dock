<?php

namespace App\Modules\Init\Api;

class InitRouter
{
    /**
     * Route init API requests
     */
    public static function route(string $path): array
    {
        if ($path === 'init' || $path === 'init/all') {
            return InitAPI::runFullInit();
        } elseif ($path === 'init/blocks') {
            return InitAPI::initBlocks();
        } elseif ($path === 'init/wallets') {
            return InitAPI::initWallets();
        }

        return [
            'success' => false,
            'message' => 'Init route not found',
            'available_routes' => [
                'POST /api/init - Run full initialization',
                'POST /api/init/all - Run full initialization',
                'POST /api/init/blocks - Initialize blockchain',
                'POST /api/init/wallets - Initialize wallets'
            ]
        ];
    }

    /**
     * Check if path matches init routes
     */
    public static function matches(string $path): bool
    {
        return strpos($path, 'init') === 0;
    }
}
