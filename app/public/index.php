<?php

/**
 * Main Web Entry Point
 * Routes requests to appropriate View Routers
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Modules\Home\Views\HomeViewRouter;
use App\Modules\Block\Views\BlockViewRouter;
use App\Modules\Wallet\Views\WalletViewRouter;

// Get the requested path
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/app', '', $path);  // Remove /app prefix if exists
$path = trim($path, '/');

// Route to appropriate view router
if (empty($path) || $path === '') {
    echo HomeViewRouter::route('/');
} elseif ($path === 'blocks' || $path === 'blocks/') {
    echo BlockViewRouter::route('/blocks');
} elseif ($path === 'wallets' || $path === 'wallets/') {
    echo WalletViewRouter::route('/wallets');
} else {
    // 404 Not Found
    http_response_code(404);
    echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>404 - Page non trouvée</title>
    <style>
        body { font-family: Arial; text-align: center; padding: 50px; }
        h1 { color: #666; }
        a { color: #667eea; text-decoration: none; }
    </style>
</head>
<body>
    <h1>404</h1>
    <p>Page non trouvée: <strong>' . htmlspecialchars($path) . '</strong></p>
    <a href="/">← Retour accueil</a>
</body>
</html>';
    exit;
}
