<?php

namespace App\Config;

use PDO;
use PDOException;

class Database
{
    private static ?self $instance = null;
    private ?PDO $pdo = null;

    private function __construct()
    {
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function connect(): PDO
    {
        if ($this->pdo === null) {
            try {
                $host = getenv('MYSQL_HOST') ?: 'mariadb';
                $port = getenv('MYSQL_PORT') ?: '3306';
                $db = getenv('MYSQL_DATABASE') ?: 'app_db';
                $user = getenv('MYSQL_USER') ?: 'app_user';
                $password = getenv('MYSQL_PASSWORD') ?: 'app_password';

                $dsn = "mysql:host=$host:$port;dbname=$db;charset=utf8mb4";
                $this->pdo = new PDO($dsn, $user, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                die("Connection failed: " . $e->getMessage());
            }
        }

        return $this->pdo;
    }

    public function getConnection(): PDO
    {
        return $this->connect();
    }

    public function disconnect(): void
    {
        $this->pdo = null;
    }
}
