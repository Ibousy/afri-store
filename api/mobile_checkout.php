<?php
// ============================================================
//  API MOBILE — Initier un paiement Paytech
//  POST /api/mobile_checkout.php
//  Body JSON : { items, address, subtotal, shipping, total }
// ============================================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Body JSON invalide']);
    exit;
}

// ── Champs requis ────────────────────────────────────────────
$items    = $data['items']    ?? [];
$address  = $data['address']  ?? [];
$subtotal = (float)($data['subtotal'] ?? 0);
$shipping = (float)($data['shipping'] ?? 0);
$total    = (float)($data['total']    ?? 0);

if (empty($items) || empty($address) || $total <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Données manquantes (items, address, total)']);
    exit;
}

try {
    $db = getDB();

    // ── Insérer ou retrouver l'adresse ───────────────────────
    $stmt = $db->prepare("
        INSERT INTO addresses (user_id, full_name, phone, address_line1, city, postal_code, country)
        VALUES (0, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $address['full_name']    ?? 'Client Mobile',
        $address['phone']        ?? '',
        $address['address_line1'] ?? '',
        $address['city']         ?? '',
        $address['postal_code']  ?? '',
        $address['country']      ?? 'Sénégal',
    ]);
    $addressId = (int)$db->lastInsertId();

    // ── Créer la commande ────────────────────────────────────
    $orderNumber = 'MOB-' . strtoupper(substr(uniqid(), -8));

    $stmt = $db->prepare("
        INSERT INTO orders
            (order_number, user_id, address_id, subtotal, shipping_cost, discount_amount, total, payment_method, payment_status)
        VALUES (?, 0, ?, ?, ?, 0, ?, 'paytech', 'pending')
    ");
    $stmt->execute([$orderNumber, $addressId, $subtotal, $shipping, $total]);
    $orderId = (int)$db->lastInsertId();

    // ── Insérer les articles ─────────────────────────────────
    foreach ($items as $item) {
        $price = (float)($item['price'] ?? 0);
        $qty   = (int)($item['quantity'] ?? 1);
        $db->prepare("
            INSERT INTO order_items
                (order_id, product_id, product_name, product_image, price, quantity, subtotal)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $orderId,
            $item['product_id'] ?? 0,
            $item['name']       ?? '',
            $item['image']      ?? '',
            $price,
            $qty,
            $price * $qty,
        ]);
    }

    // ── Appel API Paytech ────────────────────────────────────
    $amount     = (int) round($total);
    $paytechEnv = PAYTECH_SANDBOX ? 'test' : 'prod';

    $payload = http_build_query([
        'item_name'    => 'Commande ' . $orderNumber,
        'item_price'   => $amount,
        'currency'     => CURRENCY_CODE,
        'ref_command'  => $orderNumber,
        'command_name' => 'Paiement ' . SITE_NAME . ' - ' . $orderNumber,
        'env'          => $paytechEnv,
        'ipn_url'      => PAYTECH_PUBLIC_URL . '/api/paytech_callback.php',
        'success_url'  => PAYTECH_PUBLIC_URL . '/api/mobile_payment_result.php?status=success&ref=' . $orderNumber,
        'cancel_url'   => PAYTECH_PUBLIC_URL . '/api/mobile_payment_result.php?status=cancel&ref='  . $orderNumber,
    ]);

    $ch = curl_init(PAYTECH_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/x-www-form-urlencoded',
            'API_KEY: '    . PAYTECH_API_KEY,
            'API_SECRET: ' . PAYTECH_API_SECRET,
        ],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => !PAYTECH_SANDBOX,
    ]);

    $response  = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    // Log sandbox
    if (PAYTECH_SANDBOX) {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) mkdir($logDir, 0755, true);
        file_put_contents(
            $logDir . '/mobile_checkout.log',
            date('[Y-m-d H:i:s] ') . "PAYLOAD: $payload\nRESPONSE: $response\n\n",
            FILE_APPEND
        );
    }

    if ($curlError) {
        // Annuler la commande
        $db->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$orderId]);
        $db->prepare("DELETE FROM orders WHERE id = ?")->execute([$orderId]);
        http_response_code(502);
        echo json_encode(['error' => 'Impossible de contacter PayTech : ' . $curlError]);
        exit;
    }

    $result = json_decode($response, true);

    if (!empty($result['success']) && $result['success'] == 1 && !empty($result['redirect_url'])) {
        echo json_encode([
            'success'      => true,
            'redirect_url' => $result['redirect_url'],
            'order_number' => $orderNumber,
        ]);
    } else {
        // Annuler la commande
        $db->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$orderId]);
        $db->prepare("DELETE FROM orders WHERE id = ?")->execute([$orderId]);
        $msg = $result['message'] ?? ($result['error'] ?? $response);
        http_response_code(400);
        echo json_encode(['error' => 'PayTech : ' . (is_string($msg) ? $msg : json_encode($msg))]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
