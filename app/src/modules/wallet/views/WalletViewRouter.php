<?php

namespace App\Modules\Wallet\Views;

class WalletViewRouter
{
    /**
     * Route page views
     */
    public static function route(string $path): ?string
    {
        if ($path === '/wallets') {
            $response = WalletController::getWallets();
            $data = [
                'wallets' => $response['data'] ?? [],
                'count' => count($response['data'] ?? []),
                'status' => $response['status'] ?? 'error'
            ];
            return self::renderView('wallets', $data);
        }
        return null;
    }

    /**
     * Render view file
     */
    private static function renderView(string $view, array $data = []): string
    {
        ob_start();
        include __DIR__ . '/' . $view . '.php';
        $content = ob_get_clean();
        echo $content;
        return $content;
    }
}
