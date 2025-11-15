<?php

namespace App\Modules\Init\Api;

use App\Init\InitBlocks;
use App\Init\InitWallets;
use App\Lib\Logger;

class InitAPI
{
    /**
     * Run full initialization
     */
    public static function runFullInit(): array
    {
        Logger::info('=== API: Full Initialization Started ===');
        
        try {
            $results = [
                'success' => true,
                'message' => 'Initialization completed successfully',
                'steps' => [],
                'timestamp' => time()
            ];

            // Step 1: Initialize Blocks
            try {
                Logger::info('Running Blocks initialization...');
                InitBlocks::execute();
                $results['steps'][] = [
                    'name' => 'Blocks',
                    'status' => 'completed',
                    'message' => 'Genesis Block and system wallet created'
                ];
            } catch (\Exception $e) {
                $results['steps'][] = [
                    'name' => 'Blocks',
                    'status' => 'failed',
                    'message' => $e->getMessage()
                ];
                throw $e;
            }

            // Step 2: Initialize Wallets
            try {
                Logger::info('Running Wallets initialization...');
                InitWallets::execute();
                $results['steps'][] = [
                    'name' => 'Wallets',
                    'status' => 'completed',
                    'message' => 'User wallets created and initialized'
                ];
            } catch (\Exception $e) {
                $results['steps'][] = [
                    'name' => 'Wallets',
                    'status' => 'failed',
                    'message' => $e->getMessage()
                ];
                throw $e;
            }

            Logger::success('Initialization completed via API', $results);
            return $results;

        } catch (\Exception $e) {
            Logger::error('Initialization failed via API', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return [
                'success' => false,
                'message' => 'Initialization failed',
                'error' => $e->getMessage(),
                'timestamp' => time()
            ];
        }
    }

    /**
     * Initialize only blocks
     */
    public static function initBlocks(): array
    {
        try {
            Logger::info('Running Blocks initialization via API...');
            InitBlocks::execute();
            
            return [
                'success' => true,
                'message' => 'Genesis Block created successfully',
                'step' => 'Blocks',
                'timestamp' => time()
            ];
        } catch (\Exception $e) {
            Logger::error('Blocks initialization failed', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Blocks initialization failed',
                'error' => $e->getMessage(),
                'timestamp' => time()
            ];
        }
    }

    /**
     * Initialize only wallets
     */
    public static function initWallets(): array
    {
        try {
            Logger::info('Running Wallets initialization via API...');
            InitWallets::execute();
            
            return [
                'success' => true,
                'message' => 'Wallets created successfully',
                'step' => 'Wallets',
                'timestamp' => time()
            ];
        } catch (\Exception $e) {
            Logger::error('Wallets initialization failed', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Wallets initialization failed',
                'error' => $e->getMessage(),
                'timestamp' => time()
            ];
        }
    }
}
