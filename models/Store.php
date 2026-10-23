<?php
// models/Store.php

require_once __DIR__ . '/../core/Database.php';

class Store {
    public static function findById(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT s.*, u.name as owner_name, u.email as owner_email FROM stores s JOIN users u ON s.user_id = u.id WHERE s.id = ?");
        $stmt->execute([$id]);
        $store = $stmt->fetch();
        return $store ?: null;
    }

    public static function findByUserId(int $userId): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM stores WHERE user_id = ?");
        $stmt->execute([$userId]);
        $store = $stmt->fetch();
        return $store ?: null;
    }

    public static function allActive(?string $district = null, ?string $category = null): array {
        $db = Database::getConnection();
        $sql = "SELECT s.*, 
                (SELECT COUNT(*) FROM surprise_bags b WHERE b.store_id = s.id AND b.status = 'available' AND b.quantity_available > 0) as available_bags_count,
                (SELECT MIN(b.rescue_price) FROM surprise_bags b WHERE b.store_id = s.id AND b.status = 'available' AND b.quantity_available > 0) as min_rescue_price
                FROM stores s WHERE s.status = 'approved'";
        $params = [];

        if ($district && $district !== 'all') {
            $sql .= " AND s.district = ?";
            $params[] = $district;
        }

        if ($category && $category !== 'all') {
            $sql .= " AND s.category = ?";
            $params[] = $category;
        }

        $sql .= " ORDER BY available_bags_count DESC, s.rating DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
