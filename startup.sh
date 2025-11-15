#!/bin/bash

# Script de démarrage du système Blockchain

echo "╔════════════════════════════════════════════════╗"
echo "║   Blockchain System - Startup Script          ║"
echo "╚════════════════════════════════════════════════╝"
echo ""

# Vérifier que Docker Compose est lancé
echo "[1/3] Vérification des services Docker..."
if ! docker-compose ps | grep -q "php-fpm"; then
    echo "Démarrage de Docker Compose..."
    docker-compose up -d
    echo "Attente de l'initialisation des services..."
    sleep 5
else
    echo "Services Docker déjà actifs"
fi

# Installer les dépendances Composer
echo ""
echo "[2/3] Installation des dépendances Composer..."
docker-compose exec -T php-fpm composer install --no-dev --working-dir=/var/www/html

# Exécuter le script d'initialisation
echo ""
echo "[3/3] Initialisation du système Blockchain..."
docker-compose exec -T php-fpm php /var/www/html/init.php

echo ""
echo "╔════════════════════════════════════════════════╗"
echo "║   Startup Complete                            ║"
echo "╚════════════════════════════════════════════════╝"
echo ""
echo "Accès:"
echo "  Application: http://localhost:81"
echo "  MariaDB: localhost:3306"
echo "  User: app_user"
echo "  Password: app_password"
echo ""
