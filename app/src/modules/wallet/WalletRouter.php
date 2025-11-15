<?php

namespace App\Modules\Wallet;

class WalletRouter
{
    private string $method;
    private string $path;

    public function __construct(string $method, string $path)
    {
        $this->method = $method;
        $this->path = $path;
    }

    /**
     * Route wallet endpoints
     */
    public function route(): array
    {
        if ($this->method !== 'GET') {
            http_response_code(405);
            return WalletAPI::error('Method not allowed', 405);
        }

        // GET /api/wallets
        if ($this->path === 'wallets') {
            $wallets = WalletAPI::getAllWallets();
            return WalletAPI::response('success', ['count' => count($wallets), 'wallets' => $wallets]);
        }

        $parts = explode('/', $this->path);
        
        // GET /api/wallets/{address}/balance
        if (count($parts) === 3 && $parts[0] === 'wallets' && $parts[2] === 'balance') {
            $address = $parts[1];
            $balance = WalletAPI::getBalance($address);
            
            if ($balance === null) {
                http_response_code(404);
                return WalletAPI::error('Wallet not found', 404);
            }
            
            return WalletAPI::response('success', ['address' => $address, 'balance' => $balance]);
        }

        // GET /api/wallets/{address}
        if (count($parts) === 2 && $parts[0] === 'wallets') {
            $address = $parts[1];
            $wallet = WalletAPI::getWalletByAddress($address);
            
            if (!$wallet) {
                http_response_code(404);
                return WalletAPI::error('Wallet not found', 404);
            }
            
            return WalletAPI::response('success', $wallet);
        }

        http_response_code(404);
        return WalletAPI::error('Wallet endpoint not found', 404);
    }

    /**
     * Check if path matches wallet routes
     */
    public static function matches(string $path): bool
    {
        return strpos($path, 'wallets') === 0;
    }
}
