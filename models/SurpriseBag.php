<?php
// models/SurpriseBag.php

require_once __DIR__ . '/../core/Database.php';

class SurpriseBag {
    public static function findById(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT b.*, s.store_name, s.address, s.district, s.city, s.phone as store_phone, 
                   s.rating as store_rating, s.image_url as store_image, s.user_id as store_owner_id
            FROM surprise_bags b 
            JOIN stores s ON b.store_id = s.id 
            WHERE b.id = ?
        ");
        $stmt->execute([$id]);
        $bag = $stmt->fetch();
        return $bag ?: null;
    }

    public static function getActiveBags(?string $district = null, ?string $category = null): array {
        $db = Database::getConnection();
        $sql = "
            SELECT b.*, s.store_name, s.address, s.district, s.rating as store_rating, s.image_url as store_image
            FROM surprise_bags b
            JOIN stores s ON b.store_id = s.id
            WHERE b.status = 'available' AND b.quantity_available > 0 AND s.status = 'approved'
        ";
        $params = [];

        if ($district && $district !== 'all') {
            $sql .= " AND s.district = ?";
            $params[] = $district;
        }

        if ($category && $category !== 'all') {
            $sql .= " AND (b.category LIKE ? OR s.category = ?)";
            $params[] = "%$category%";
            $params[] = $category;
        }

        $sql .= " ORDER BY b.id DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getByStoreId(int $storeId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM surprise_bags WHERE store_id = ? ORDER BY id DESC");
        $stmt->execute([$storeId]);
        return $stmt->fetchAll();
    }
}
