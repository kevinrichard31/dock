<?php

namespace App\Modules\Home\Views;

class HomeController
{
    public static function getHome(): array
    {
            return [
            'title' => 'Blockchain Dashboard',
            'modules' => [
                [
                    'name' => 'Blockchain',
                    'icon' => '',
                    'description' => 'Visualiser la blockchain et les blocs minés',
                    'link' => '/blocks'
                ],
                [
                    'name' => 'Wallets',
                    'icon' => '',
                    'description' => 'Gérer les portefeuilles et les adresses',
                    'link' => '/wallets'
                ],
                [
                    'name' => 'Transactions',
                    'icon' => '',
                    'description' => 'Historique des transactions',
                    'link' => '/transactions'
                ],
                [
                    'name' => 'Stats',
                    'icon' => '',
                    'description' => 'Statistiques système',
                    'link' => '/api/stats'
                ]
            ]
        ];
    }
}
