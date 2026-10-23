<?php
// core/Database.php - Quản lý kết nối Singleton PDO cho Transaction & Lock

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../config/database.php';
            $dsn = sprintf(
                "mysql:host=%s;port=%d;dbname=%s;charset=%s",
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset']
            );

            try {
                self::$instance = new PDO($dsn, $config['username'], $config['password'], $config['options']);
            } catch (PDOException $e) {
                die(json_encode([
                    'success' => false,
                    'error'   => 'Lỗi kết nối CSDL: ' . $e->getMessage()
                ]));
            }
        }

        return self::$instance;
    }

    /**
     * Tạo connection riêng biệt phục vụ mô phỏng nhiều transaction đồng thời
     */
    public static function createIsolatedConnection(): PDO {
        $config = require __DIR__ . '/../config/database.php';
        $dsn = sprintf(
            "mysql:host=%s;port=%d;dbname=%s;charset=%s",
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );
        return new PDO($dsn, $config['username'], $config['password'], $config['options']);
    }
}
