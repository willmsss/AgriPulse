<?php

class Database {
    private static $instance = null;

    public static function connect() {
        if (self::$instance !== null) {
            return self::$instance;
        }

        // 1. Load .env file from the root directory if it exists
        $envPath = __DIR__ . '/../.env';
        if (file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || strpos($line, '#') === 0) {
                    continue;
                }
                list($name, $value) = explode('=', $line, 2) + [null, null];
                if ($name !== null && $value !== null) {
                    $name = trim($name);
                    $value = trim($value, " \t\n\r\0\x0B\"'");
                    $_ENV[$name] = $value;
                    putenv("{$name}={$value}");
                }
            }
        }

        // 2. Read MySQL credentials from environment variables with fallbacks
        $dbHost = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';
        $dbName = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'agripulse';
        $dbUser = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root';
        $dbPass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '';
        $dbPort = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306';

        try {
            // MySQL DSN connection string
            $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
            
            self::$instance = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);

        } catch (PDOException $e) {
            // Graceful error display if connection fails
            die("<div style='font-family: sans-serif; padding: 20px; color: #721c24; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px;'>
                    <h3>AgriPulse Database Connection Error</h3>
                    <p>Could not connect to your MySQL database. Please check your environment variables or .env credentials.</p>
                    <small><strong>Details:</strong> " . htmlspecialchars($e->getMessage()) . "</small>
                 </div>");
        }

        return self::$instance;
    }
}
