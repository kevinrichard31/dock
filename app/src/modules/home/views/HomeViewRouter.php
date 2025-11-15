<?php

namespace App\Modules\Home\Views;

class HomeViewRouter
{
    /**
     * Route page views
     */
    public static function route(string $path): ?string
    {
        if ($path === '/' || $path === '') {
            $data = HomeController::getHome();
            return self::renderView('home', $data);
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
        return $content;
    }
}
