<?php
require_once __DIR__ . '/config.php';

// ============================================================
//  SESSION
// ============================================================
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params(SESSION_LIFETIME);
        session_start();
    }
}

function isLoggedIn(): bool {
    startSession();
    return isset($_SESSION['user_id']);
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND is_active = 1");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function isAdmin(): bool {
    $user = getCurrentUser();
    return $user && $user['role'] === 'admin';
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/pages/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

// ============================================================
//  SÉCURITÉ
// ============================================================
function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function sanitize(string $str): string {
    return trim(strip_tags($str));
}

function generateToken(): string {
    return bin2hex(random_bytes(32));
}

function verifyToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrfToken(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateToken();
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

// ============================================================
//  PRODUITS
// ============================================================
function getProducts(array $opts = []): array {
    $db = getDB();
    $where = ['p.is_active = 1'];
    $params = [];

    if (!empty($opts['category_id'])) {
        $where[] = 'p.category_id = ?';
        $params[] = $opts['category_id'];
    }
    if (!empty($opts['search'])) {
        $where[] = '(p.name LIKE ? OR p.description LIKE ?)';
        $params[] = '%' . $opts['search'] . '%';
        $params[] = '%' . $opts['search'] . '%';
    }
    if (!empty($opts['featured'])) {
        $where[] = 'p.is_featured = 1';
    }
    if (!empty($opts['flash_sale'])) {
        $where[] = 'p.is_flash_sale = 1';
    }
    if (!empty($opts['min_price'])) {
        $where[] = 'p.price >= ?';
        $params[] = $opts['min_price'];
    }
    if (!empty($opts['max_price'])) {
        $where[] = 'p.price <= ?';
        $params[] = $opts['max_price'];
    }

    $orderBy = match($opts['sort'] ?? 'default') {
        'price_asc'   => 'p.price ASC',
        'price_desc'  => 'p.price DESC',
        'rating'      => 'p.rating DESC',
        'newest'      => 'p.created_at DESC',
        'popular'     => 'p.sold_count DESC',
        default       => 'p.is_featured DESC, p.sold_count DESC'
    };

    $limit  = (int)($opts['limit']  ?? 20);
    $offset = (int)($opts['offset'] ?? 0);

    $sql = "
        SELECT p.*, 
               c.name AS category_name,
               pi.image_url AS primary_image
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
        WHERE " . implode(' AND ', $where) . "
        ORDER BY $orderBy
        LIMIT $limit OFFSET $offset
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getProduct(int $id): ?array {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT p.*, c.name AS category_name, c.slug AS category_slug
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE p.id = ? AND p.is_active = 1
    ");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    if (!$product) return null;

    // Images
    $stmt = $db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC");
    $stmt->execute([$id]);
    $product['images'] = $stmt->fetchAll();

    // Variantes
    $stmt = $db->prepare("SELECT * FROM product_variants WHERE product_id = ?");
    $stmt->execute([$id]);
    $product['variants'] = $stmt->fetchAll();

    // Avis
    $stmt = $db->prepare("
        SELECT r.*, u.name AS user_name
        FROM reviews r
        LEFT JOIN users u ON u.id = r.user_id
        WHERE r.product_id = ? AND r.is_approved = 1
        ORDER BY r.created_at DESC LIMIT 10
    ");
    $stmt->execute([$id]);
    $product['reviews'] = $stmt->fetchAll();

    return $product;
}

function getCategories(): array {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM categories WHERE is_active = 1 AND parent_id IS NULL ORDER BY sort_order ASC");
    return $stmt->fetchAll();
}

// ============================================================
//  PANIER
// ============================================================
function getCartKey(): string {
    startSession();
    if (isLoggedIn()) return 'user_' . $_SESSION['user_id'];
    if (!isset($_SESSION['cart_session'])) $_SESSION['cart_session'] = uniqid('cart_', true);
    return $_SESSION['cart_session'];
}

function getCart(): array {
    $db = getDB();
    $key = getCartKey();

    if (isLoggedIn()) {
        $stmt = $db->prepare("
            SELECT c.*, p.name, p.price, p.stock, 
                   COALESCE(pi.image_url, '') AS image_url,
                   pv.variant_name, pv.variant_value, pv.extra_price
            FROM cart c
            JOIN products p ON p.id = c.product_id
            LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
            LEFT JOIN product_variants pv ON pv.id = c.variant_id
            WHERE c.user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
    } else {
        $stmt = $db->prepare("
            SELECT c.*, p.name, p.price, p.stock,
                   COALESCE(pi.image_url, '') AS image_url,
                   pv.variant_name, pv.variant_value, pv.extra_price
            FROM cart c
            JOIN products p ON p.id = c.product_id
            LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
            LEFT JOIN product_variants pv ON pv.id = c.variant_id
            WHERE c.session_id = ?
        ");
        $stmt->execute([$key]);
    }
    return $stmt->fetchAll();
}

function getCartCount(): int {
    $cart = getCart();
    return array_sum(array_column($cart, 'quantity'));
}

function getCartTotal(): float {
    $cart = getCart();
    $total = 0;
    foreach ($cart as $item) {
        $price = $item['price'] + ($item['extra_price'] ?? 0);
        $total += $price * $item['quantity'];
    }
    return $total;
}

function addToCart(int $productId, int $qty = 1, ?int $variantId = null): bool {
    $db = getDB();
    $key = getCartKey();

    // Vérif stock
    $stmt = $db->prepare("SELECT stock FROM products WHERE id = ? AND is_active = 1");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    if (!$product || $product['stock'] < 1) return false;

    if (isLoggedIn()) {
        $stmt = $db->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND (variant_id = ? OR (variant_id IS NULL AND ? IS NULL))");
        $stmt->execute([$_SESSION['user_id'], $productId, $variantId, $variantId]);
    } else {
        $stmt = $db->prepare("SELECT id, quantity FROM cart WHERE session_id = ? AND product_id = ? AND (variant_id = ? OR (variant_id IS NULL AND ? IS NULL))");
        $stmt->execute([$key, $productId, $variantId, $variantId]);
    }
    $existing = $stmt->fetch();

    if ($existing) {
        $newQty = min($existing['quantity'] + $qty, $product['stock']);
        $stmt = $db->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $stmt->execute([$newQty, $existing['id']]);
    } else {
        if (isLoggedIn()) {
            $stmt = $db->prepare("INSERT INTO cart (user_id, product_id, variant_id, quantity) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $productId, $variantId, $qty]);
        } else {
            $stmt = $db->prepare("INSERT INTO cart (session_id, product_id, variant_id, quantity) VALUES (?, ?, ?, ?)");
            $stmt->execute([$key, $productId, $variantId, $qty]);
        }
    }
    return true;
}

function removeFromCart(int $cartId): bool {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM cart WHERE id = ?");
    return $stmt->execute([$cartId]);
}

function updateCartQty(int $cartId, int $qty): bool {
    $db = getDB();
    if ($qty < 1) return removeFromCart($cartId);
    $stmt = $db->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
    return $stmt->execute([$qty, $cartId]);
}

// ============================================================
//  WISHLIST
// ============================================================
function isInWishlist(int $productId): bool {
    if (!isLoggedIn()) return false;
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$_SESSION['user_id'], $productId]);
    return (bool)$stmt->fetch();
}

function toggleWishlist(int $productId): bool {
    if (!isLoggedIn()) return false;
    $db = getDB();
    if (isInWishlist($productId)) {
        $stmt = $db->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$_SESSION['user_id'], $productId]);
        return false;
    } else {
        $stmt = $db->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $productId]);
        return true;
    }
}

// ============================================================
//  FORMATAGE
// ============================================================
function formatPrice(float $price): string {
    return number_format((int)$price, 0, ',', ' ') . ' ' . CURRENCY;
}

function discount(float $original, float $current): int {
    if ($original <= 0) return 0;
    return (int)round((1 - $current / $original) * 100);
}

function timeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'à l\'instant';
    if ($diff < 3600)   return (int)($diff/60) . ' min';
    if ($diff < 86400)  return (int)($diff/3600) . 'h';
    if ($diff < 604800) return (int)($diff/86400) . 'j';
    return date('d/m/Y', strtotime($datetime));
}

function stars(float $rating): string {
    $full  = floor($rating);
    $half  = ($rating - $full) >= 0.5 ? 1 : 0;
    $empty = 5 - $full - $half;
    return str_repeat('★', $full) . str_repeat('½', $half) . str_repeat('☆', $empty);
}

// ============================================================
//  MESSAGES FLASH
// ============================================================
function flash(string $key, string $msg = '', string $type = 'info'): ?array {
    startSession();
    if ($msg !== '') {
        $_SESSION['flash'][$key] = ['msg' => $msg, 'type' => $type];
        return null;
    }
    $data = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $data;
}

function flashSuccess(string $msg): void { flash('msg', $msg, 'success'); }
function flashError(string $msg): void   { flash('msg', $msg, 'error'); }
function flashInfo(string $msg): void    { flash('msg', $msg, 'info'); }
