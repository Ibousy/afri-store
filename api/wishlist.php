<?php
require_once __DIR__ . '/../includes/functions.php';
startSession();

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'redirect' => SITE_URL . '/pages/login.php']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$productId = (int)($input['product_id'] ?? 0);

if (!$productId) {
    echo json_encode(['success' => false, 'message' => 'Produit invalide.']);
    exit;
}

$inWishlist = toggleWishlist($productId);
echo json_encode(['success' => true, 'in_wishlist' => $inWishlist]);
