<?php
require_once __DIR__ . '/../includes/functions.php';
startSession();

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $input['action'] ?? '';

switch ($action) {
    case 'add':
        $productId = (int)($input['product_id'] ?? 0);
        $qty       = max(1, (int)($input['quantity'] ?? 1));
        $variantId = !empty($input['variant_id']) ? (int)$input['variant_id'] : null;

        if (addToCart($productId, $qty, $variantId)) {
            echo json_encode([
                'success'    => true,
                'message'    => 'Produit ajouté au panier !',
                'cart_count' => getCartCount(),
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Produit indisponible ou stock insuffisant.']);
        }
        break;

    case 'remove':
        $cartId = (int)($input['cart_id'] ?? 0);
        if (removeFromCart($cartId)) {
            echo json_encode(['success' => true, 'cart_count' => getCartCount()]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Article introuvable.']);
        }
        break;

    case 'update':
        $cartId = (int)($input['cart_id'] ?? 0);
        $qty    = (int)($input['quantity'] ?? 1);
        if (updateCartQty($cartId, $qty)) {
            echo json_encode(['success' => true, 'cart_count' => getCartCount()]);
        } else {
            echo json_encode(['success' => false]);
        }
        break;

    case 'coupon':
        $code = strtoupper(trim($input['code'] ?? ''));
        $db = getDB();
        $stmt = $db->prepare("
            SELECT * FROM coupons 
            WHERE code = ? AND is_active = 1 
            AND (expires_at IS NULL OR expires_at > NOW())
            AND (max_uses IS NULL OR used_count < max_uses)
        ");
        $stmt->execute([$code]);
        $coupon = $stmt->fetch();

        if (!$coupon) {
            echo json_encode(['success' => false, 'message' => 'Code invalide ou expiré.']);
            break;
        }

        $total = getCartTotal();
        if ($total < $coupon['min_order']) {
            echo json_encode(['success' => false, 'message' => 'Commande minimum : ' . formatPrice($coupon['min_order'])]);
            break;
        }

        $discount = $coupon['type'] === 'percent'
            ? round($total * $coupon['value'] / 100, 2)
            : min($coupon['value'], $total);

        $_SESSION['coupon_code']     = $code;
        $_SESSION['coupon_discount'] = $discount;
        $_SESSION['coupon_id']       = $coupon['id'];

        echo json_encode(['success' => true, 'discount' => formatPrice($discount)]);
        break;

    case 'count':
        echo json_encode(['count' => getCartCount()]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action inconnue.']);
}
