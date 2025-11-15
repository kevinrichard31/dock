# Script de démarrage du système Blockchain pour Windows

Write-Host "╔════════════════════════════════════════════════╗"
Write-Host "║   Blockchain System - Startup Script (Windows) ║"
Write-Host "╚════════════════════════════════════════════════╝"
Write-Host ""

# Vérifier que Docker Compose est lancé
Write-Host "[1/3] Vérification des services Docker..."
$dockerStatus = & docker-compose ps 2>$null | Select-String "php-fpm"

if (-not $dockerStatus) {
    Write-Host "Démarrage de Docker Compose..."
    & docker-compose up -d
    Write-Host "Attente de l'initialisation des services..."
    Start-Sleep -Seconds 5
} else {
    Write-Host "Services Docker déjà actifs"
}

# Installer les dépendances Composer
Write-Host ""
Write-Host "[2/3] Installation des dépendances Composer..."
& docker-compose exec -T php-fpm composer install --no-dev --working-dir=/var/www/html

# Exécuter le script d'initialisation
Write-Host ""
Write-Host "[3/3] Initialisation du système Blockchain..."
& docker-compose exec -T php-fpm php /var/www/html/init.php

Write-Host ""
Write-Host "╔════════════════════════════════════════════════╗"
Write-Host "║   Startup Complete                            ║"
Write-Host "╚════════════════════════════════════════════════╝"
Write-Host ""
Write-Host "Accès:"
Write-Host "  Application: http://localhost:81"
Write-Host "  MariaDB: localhost:3306"
Write-Host "  User: app_user"
Write-Host "  Password: app_password"
Write-Host ""
