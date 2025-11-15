<?php

namespace App\Lib;

class Logger
{
    private static string $logFile = '/var/www/html/logs/app.log';

    public static function init(): void
    {
        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, $context);
    }

    public static function success(string $message, array $context = []): void
    {
        self::log('SUCCESS', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('WARNING', $message, $context);
    }

    private static function log(string $level, string $message, array $context): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | ' . json_encode($context) : '';
        $logMessage = "[$timestamp] [$level] $message$contextStr\n";

        echo $logMessage;
        file_put_contents(self::$logFile, $logMessage, FILE_APPEND);
    }
}
