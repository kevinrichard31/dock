<?php

/**
 * API REST - Point d'entrée principal
 * 
 * Les endpoints sont gérés par les modules:
 * - BlockAPI: gestion des blocs
 * - WalletAPI: gestion des wallets
 * - Router: routage centralisé
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\API\Router;

header('Content-Type: application/json');

try {
    $router = new Router();
    $response = $router->route();
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
