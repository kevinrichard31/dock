<?php

namespace App\Modules\Block\Views;

class BlockViewRouter
{
    /**
     * Route page views
     */
    public static function route(string $path): ?string
    {
        if ($path === '/blocks') {
            $response = BlockController::getBlocks();
            $data = [
                'blocks' => $response['data'] ?? [],
                'status' => $response['status'] ?? 'error'
            ];
            return self::renderView('blocks', $data);
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
