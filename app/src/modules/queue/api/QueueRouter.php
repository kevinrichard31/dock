<?php

/**
 * QueueRouter.php
 * Routeur API pour la queue
 */

namespace App\Modules\Queue\API;

use App\API\Router;

class QueueRouter
{
    public static function register(Router $router): void
    {
        // GET /api/queue - Récupérer toute la queue
        $router->get('/queue', function () {
            return [
                'success' => true,
                'data' => QueueAPI::getAll()
            ];
        });

        // GET /api/queue/:id - Récupérer un item de la queue
        $router->get('/queue/:id', function ($id) {
            $item = QueueAPI::getById((int)$id);
            if (!$item) {
                return [
                    'success' => false,
                    'error' => 'Queue item not found'
                ];
            }
            return [
                'success' => true,
                'data' => $item
            ];
        });

        // GET /api/queue/stats - Récupérer les stats de la queue
        $router->get('/queue/stats', function () {
            return [
                'success' => true,
                'data' => QueueAPI::getStats()
            ];
        });

        // GET /api/queue/status/:status - Récupérer les items par statut
        $router->get('/queue/status/:status', function ($status) {
            $validStatuses = ['pending', 'processing', 'completed', 'failed'];
            if (!in_array($status, $validStatuses)) {
                return [
                    'success' => false,
                    'error' => 'Invalid status'
                ];
            }
            return [
                'success' => true,
                'data' => QueueAPI::getByStatus($status)
            ];
        });

        // GET /api/queue/pending - Récupérer les items en attente
        $router->get('/queue/pending', function () {
            return [
                'success' => true,
                'data' => QueueAPI::getPending()
            ];
        });

        // GET /api/queue/processing - Récupérer les items en traitement
        $router->get('/queue/processing', function () {
            return [
                'success' => true,
                'data' => QueueAPI::getProcessing()
            ];
        });

        // GET /api/queue/completed - Récupérer les items complétés
        $router->get('/queue/completed', function () {
            return [
                'success' => true,
                'data' => QueueAPI::getCompleted()
            ];
        });

        // GET /api/queue/failed - Récupérer les items échoués
        $router->get('/queue/failed', function () {
            return [
                'success' => true,
                'data' => QueueAPI::getFailed()
            ];
        });
    }
}
