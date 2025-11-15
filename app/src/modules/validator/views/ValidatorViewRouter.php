<?php

namespace App\Modules\Validator\Views;

class ValidatorViewRouter
{
    /**
     * Route page views
     */
    public static function route(array $request): ?string
    {
        $action = $request['action'] ?? 'list';

        switch ($action) {
            case 'list':
                return self::renderValidatorsList();
            case 'request_form':
                return self::renderRequestForm();
            case 'pending':
                return self::renderPendingValidators();
            case 'approved':
                return self::renderApprovedValidators();
            default:
                return self::renderValidatorsList();
        }
    }

    /**
     * Render validators list view
     */
    private static function renderValidatorsList(): string
    {
        $response = ValidatorController::listValidators();
        return self::renderView('validators', $response);
    }

    /**
     * Render request validator form
     */
    private static function renderRequestForm(): string
    {
        $response = ValidatorController::requestValidatorForm();
        return self::renderView('validators', $response);
    }

    /**
     * Render pending validators
     */
    private static function renderPendingValidators(): string
    {
        $response = ValidatorController::listPendingValidators();
        return self::renderView('validators', $response);
    }

    /**
     * Render approved validators
     */
    private static function renderApprovedValidators(): string
    {
        $response = ValidatorController::listApprovedValidators();
        return self::renderView('validators', $response);
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

