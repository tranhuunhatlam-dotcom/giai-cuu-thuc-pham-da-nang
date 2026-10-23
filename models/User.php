<?php
// models/User.php

require_once __DIR__ . '/../core/Database.php';

class User {
    public static function findById(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findByEmail(string $email): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function all(): array {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT u.*, w.balance, w.pending_escrow FROM users u LEFT JOIN wallets w ON u.id = w.user_id ORDER BY u.id ASC");
        return $stmt->fetchAll();
    }
}
