<?php

/**
 * QueueAPI.php
 * API REST pour la queue
 */

namespace App\Modules\Queue\API;

use App\Modules\Queue\Queue;
use App\Modules\Queue\QueueManager;

class QueueAPI
{
    public static function getAll(): array
    {
        $queue = Queue::getAll();
        return array_map(fn($item) => $item->toArray(), $queue);
    }

    public static function getById(int $id): ?array
    {
        $item = Queue::findById($id);
        return $item ? $item->toArray() : null;
    }

    public static function getByStatus(string $status): array
    {
        $queue = Queue::getByStatus($status);
        return array_map(fn($item) => $item->toArray(), $queue);
    }

    public static function getStats(): array
    {
        return QueueManager::getQueueStats();
    }

    public static function getPending(): array
    {
        return self::getByStatus('pending');
    }

    public static function getProcessing(): array
    {
        return self::getByStatus('processing');
    }

    public static function getCompleted(): array
    {
        return self::getByStatus('completed');
    }

    public static function getFailed(): array
    {
        return self::getByStatus('failed');
    }
}
