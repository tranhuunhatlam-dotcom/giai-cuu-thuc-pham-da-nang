<?php
// core/Auth.php - Quản lý Phiên Đăng Nhập & Phân Quyền 3 Vai Trò (Student, Store Owner, Admin)

class Auth {
    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function check(): bool {
        self::start();
        return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
    }

    public static function user(): ?array {
        self::start();
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int {
        $u = self::user();
        return $u ? (int)$u['id'] : null;
    }

    public static function role(): ?string {
        $u = self::user();
        return $u['role'] ?? null;
    }

    public static function login(array $userData): void {
        self::start();
        $_SESSION['user'] = $userData;
    }

    public static function logout(): void {
        self::start();
        unset($_SESSION['user']);
        session_destroy();
    }

    public static function requireLogin(): void {
        if (!self::check()) {
            header('Location: /login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
    }

    public static function requireRole(string $role): void {
        self::requireLogin();
        if (self::role() !== $role && self::role() !== 'admin') {
            http_response_code(403);
            die("Bạn không có quyền truy cập trang này. Vui lòng đăng nhập với tài khoản " . htmlspecialchars($role));
        }
    }

    /**
     * Tự động làm mới số dư ví trong session nếu cần
     */
    public static function refreshWallet(): ?array {
        if (!self::check()) return null;
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM wallets WHERE user_id = ?");
        $stmt->execute([self::id()]);
        $wallet = $stmt->fetch();
        if ($wallet) {
            $_SESSION['user']['wallet'] = $wallet;
        }
        return $wallet;
    }
}
