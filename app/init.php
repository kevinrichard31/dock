<?php

/**
 * Bootstrap Script - Initialisation complète du système
 * 
 * Ce script exécute tous les fichiers d'initialisation dans l'ordre:
 * 1. Création du bloc de départ (Genesis Block)
 * 2. Création des wallets synchronisés avec la blockchain
 * 
 * Stratégie d'exécution:
 * - Chaque étape est isolée et indépendante
 * - Les blocs sont créés en premier (base de la blockchain)
 * - Les wallets sont créés ensuite (basés sur les blocs)
 */

require_once __DIR__ . '/vendor/autoload.php';

// Load init files manually
require_once __DIR__ . '/src/init/00_reset.php';
require_once __DIR__ . '/src/init/01_blocks.php';
require_once __DIR__ . '/src/init/02_wallets_sync.php';
require_once __DIR__ . '/src/init/03_validators_init.php';
require_once __DIR__ . '/src/init/04_validators_sync.php';

use App\Config\Database;
use App\Lib\Logger;
use App\Init\InitReset;
use App\Init\InitBlocks;
use App\Init\InitWalletsSync;
use App\Init\InitWallets;
use App\Init\InitValidators;
use App\Init\InitValidatorsSync;

// Initialize logger
Logger::init();

Logger::info('╔════════════════════════════════════════════════╗');
Logger::info('║   Blockchain System - Initialization Start    ║');
Logger::info('╚════════════════════════════════════════════════╝');

try {
    // Test database connection
    Logger::info('Testing database connection...');
    $db = Database::getInstance()->getConnection();
    Logger::success('Database connection established');

    // Execute initialization steps in order
    $steps = [
        ['class' => InitReset::class, 'name' => 'Reset'],
        ['class' => InitBlocks::class, 'name' => 'Blocks'],
        ['class' => InitWalletsSync::class, 'name' => 'Wallets Sync'],
        ['class' => InitValidators::class, 'name' => 'Validators Init'],
        ['class' => InitValidatorsSync::class, 'name' => 'Validators Sync'],
    ];

    foreach ($steps as $step) {
        Logger::info("\n─────────────────────────────────────────────────");
        Logger::info("Step: {$step['name']}");
        Logger::info("─────────────────────────────────────────────────\n");
        
        try {
            $step['class']::execute();
            Logger::success("✓ {$step['name']} step completed\n");
        } catch (\Exception $e) {
            Logger::error("✗ {$step['name']} step failed", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw $e;
        }
    }

    Logger::info('╔════════════════════════════════════════════════╗');
    Logger::info('║   Initialization Complete Successfully        ║');
    Logger::info('╚════════════════════════════════════════════════╝\n');

} catch (\Exception $e) {
    Logger::error('╔════════════════════════════════════════════════╗');
    Logger::error('║   Initialization Failed                        ║');
    Logger::error('╚════════════════════════════════════════════════╝');
    Logger::error($e->getMessage());
    exit(1);
}
