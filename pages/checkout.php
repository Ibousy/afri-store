<?php
// ============================================================
//  TOUTE LA LOGIQUE PHP EN PREMIER — avant tout output HTML
// ============================================================
require_once __DIR__ . '/../includes/functions.php';
startSession();
requireLogin();

$db   = getDB();
$cart = getCart();

// Redirection si panier vide — AVANT le header HTML
if (empty($cart)) {
    header('Location: ' . SITE_URL . '/pages/cart.php');
    exit;
}

$total          = getCartTotal();
$couponDiscount = $_SESSION['coupon_discount'] ?? 0;
$shipping       = $total >= 19000 ? 0 : 3500;
$finalTotal     = max(0, $total - $couponDiscount) + $shipping;

// Adresses de l'utilisateur
$stmt = $db->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC");
$stmt->execute([$_SESSION['user_id']]);
$addresses = $stmt->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token invalide.';
    } else {
        $addressId = (int)($_POST['address_id'] ?? 0);
        $payment   = sanitize($_POST['payment_method'] ?? 'card');

        // Nouvelle adresse
        if ($addressId === 0) {
            $fullName = sanitize($_POST['full_name'] ?? '');
            $phone    = sanitize($_POST['phone'] ?? '');
            $addr1    = sanitize($_POST['address_line1'] ?? '');
            $city     = sanitize($_POST['city'] ?? '');
            $postal   = sanitize($_POST['postal_code'] ?? '');
            $country  = sanitize($_POST['country'] ?? 'France');

            if (empty($fullName) || empty($addr1) || empty($city)) {
                $errors[] = "Veuillez remplir l'adresse complète.";
            } else {
                $stmt = $db->prepare("INSERT INTO addresses (user_id, full_name, phone, address_line1, city, postal_code, country) VALUES (?,?,?,?,?,?,?)");
                $stmt->execute([$_SESSION['user_id'], $fullName, $phone, $addr1, $city, $postal, $country]);
                $addressId = (int)$db->lastInsertId();
            }
        }

        if (empty($errors)) {
            // Créer la commande
            $orderNumber = 'TC-' . strtoupper(substr(uniqid(), -8));
            // PayTech : statut pending jusqu'à confirmation IPN
            $paymentStatus = ($payment === 'paytech') ? 'pending' : 'pending';
            $stmt = $db->prepare("
                INSERT INTO orders (order_number, user_id, address_id, subtotal, shipping_cost, discount_amount, total, coupon_code, payment_method, payment_status)
                VALUES (?,?,?,?,?,?,?,?,?,?)
            ");
            $stmt->execute([
                $orderNumber,
                $_SESSION['user_id'],
                $addressId,
                $total,
                $shipping,
                $couponDiscount,
                $finalTotal,
                $_SESSION['coupon_code'] ?? null,
                $payment,
                $paymentStatus,
            ]);
            $orderId = (int)$db->lastInsertId();

            // Articles de commande
            foreach ($cart as $item) {
                $price = $item['price'] + ($item['extra_price'] ?? 0);
                $db->prepare("
                    INSERT INTO order_items (order_id, product_id, variant_id, product_name, product_image, price, quantity, subtotal)
                    VALUES (?,?,?,?,?,?,?,?)
                ")->execute([
                    $orderId,
                    $item['product_id'],
                    $item['variant_id'] ?? null,
                    $item['name'],
                    $item['image_url'],
                    $price,
                    $item['quantity'],
                    $price * $item['quantity'],
                ]);
                // Mettre à jour le stock
                $db->prepare("UPDATE products SET stock = stock - ?, sold_count = sold_count + ? WHERE id = ?")
                   ->execute([$item['quantity'], $item['quantity'], $item['product_id']]);
            }

            // Incrémenter usage coupon
            if (!empty($_SESSION['coupon_id'])) {
                $db->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?")
                   ->execute([$_SESSION['coupon_id']]);
            }

            // ── PAYTECH : initier le paiement en ligne ──────────────────
            if ($payment === 'paytech') {
                $paytechEnv = PAYTECH_SANDBOX ? 'test' : 'prod';
                // XOF (FCFA) = devise sans décimales, montant entier direct
                $amount = (int) round($finalTotal);

                // Données en form-encoded (format attendu par PayTech)
                $payload = http_build_query([
                    'item_name'    => 'Commande ' . $orderNumber,
                    'item_price'   => $amount,
                    'currency'     => CURRENCY_CODE,
                    'ref_command'  => $orderNumber,
                    'command_name' => 'Paiement ' . SITE_NAME . ' - ' . $orderNumber,
                    'env'          => $paytechEnv,
                    'ipn_url'      => PAYTECH_PUBLIC_URL . '/api/paytech_callback.php',
                    'success_url'  => PAYTECH_PUBLIC_URL . '/pages/payment_success.php',
                    'cancel_url'   => PAYTECH_PUBLIC_URL . '/pages/payment_cancel.php',
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
                $response = curl_exec($ch);
                $curlError = curl_error($ch);

                if ($curlError) {
                    $errors[] = 'Impossible de contacter PayTech : ' . $curlError;
                } else {
                    $result = json_decode($response, true);
                    // Log en mode sandbox pour déboguer
                    if (PAYTECH_SANDBOX) {
                        $logDir = __DIR__ . '/../logs';
                        if (!is_dir($logDir)) mkdir($logDir, 0755, true);
                        file_put_contents($logDir . '/paytech_request.log',
                            date('[Y-m-d H:i:s] ') . "PAYLOAD: $payload\nRESPONSE: $response\n\n",
                            FILE_APPEND);
                    }
                    if (!empty($result['success']) && $result['success'] == 1 && !empty($result['redirect_url'])) {
                        // Stocker le token PayTech dans la session pour vérification
                        $_SESSION['paytech_order'] = $orderNumber;
                        // Vider le panier AVANT la redirection
                        $db->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$_SESSION['user_id']]);
                        unset($_SESSION['coupon_discount'], $_SESSION['coupon_code'], $_SESSION['coupon_id']);
                        header('Location: ' . $result['redirect_url']);
                        exit;
                    } else {
                        $msg = $result['message'] ?? ($result['error'] ?? $response);
                        $errors[] = 'PayTech : ' . (is_string($msg) ? $msg : json_encode($msg));
                    }
                }

                // En cas d'erreur PayTech : annuler la commande créée
                if (!empty($errors)) {
                    $db->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$orderId]);
                    $db->prepare("DELETE FROM orders WHERE id = ?")->execute([$orderId]);
                    // Restaurer le stock
                    foreach ($cart as $item) {
                        $db->prepare("UPDATE products SET stock = stock + ?, sold_count = sold_count - ? WHERE id = ?")
                           ->execute([$item['quantity'], $item['quantity'], $item['product_id']]);
                    }
                }
            } else {
                // Autres méthodes de paiement (carte, PayPal, etc.)
                $db->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$_SESSION['user_id']]);
                unset($_SESSION['coupon_discount'], $_SESSION['coupon_code'], $_SESSION['coupon_id']);

                flashSuccess("Commande $orderNumber passée avec succès !");
                header('Location: ' . SITE_URL . '/pages/orders.php');
                exit;
            }
        }
    }
}

// ============================================================
//  Header HTML inclus ICI — seulement après toute la logique
// ============================================================
$pageTitle = 'Finaliser la commande';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="breadcrumb">
        <a href="<?= SITE_URL ?>">Accueil</a> <span>›</span>
        <a href="<?= SITE_URL ?>/pages/cart.php">Panier</a> <span>›</span>
        <span class="current">Commande</span>
    </div>

    <h1 style="font-size:26px;font-weight:900;margin-bottom:20px">✅ Finaliser la commande</h1>

    <?php foreach ($errors as $err): ?>
        <div class="flash-message flash-error"><?= h($err) ?></div>
    <?php endforeach; ?>

    <form method="POST">
        <?= csrfField() ?>
        <div style="display:grid;grid-template-columns:1fr 360px;gap:24px">
            <div>
                <!-- ADRESSE DE LIVRAISON -->
                <div class="bg-white rounded p-3" style="margin-bottom:20px">
                    <h3 style="font-weight:800;font-size:18px;margin-bottom:16px">📍 Adresse de livraison</h3>

                    <?php if (!empty($addresses)): ?>
                    <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:20px">
                        <?php foreach ($addresses as $addr): ?>
                        <label style="display:flex;gap:12px;align-items:flex-start;border:2px solid var(--border);border-radius:10px;padding:14px;cursor:pointer">
                            <input type="radio" name="address_id" value="<?= $addr['id'] ?>" <?= $addr['is_default'] ? 'checked' : '' ?> style="margin-top:3px">
                            <div>
                                <div style="font-weight:700"><?= h($addr['full_name']) ?> <?= $addr['phone'] ? '— '.h($addr['phone']) : '' ?></div>
                                <div style="font-size:14px;color:var(--text-muted)"><?= h($addr['address_line1']) ?>, <?= h($addr['city']) ?> <?= h($addr['postal_code']) ?>, <?= h($addr['country']) ?></div>
                            </div>
                        </label>
                        <?php endforeach; ?>
                        <label style="display:flex;gap:12px;align-items:center;border:2px dashed var(--border);border-radius:10px;padding:14px;cursor:pointer">
                            <input type="radio" name="address_id" value="0">
                            <span style="font-weight:700">+ Nouvelle adresse</span>
                        </label>
                    </div>
                    <?php endif; ?>

                    <div id="newAddressForm" style="<?= !empty($addresses) ? 'display:none' : '' ?>">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                            <div class="form-group">
                                <label>Nom complet *</label>
                                <input type="text" name="full_name" class="form-control" placeholder="Jean Dupont">
                            </div>
                            <div class="form-group">
                                <label>Téléphone</label>
                                <input type="tel" name="phone" class="form-control" placeholder="06 00 00 00 00">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Adresse *</label>
                            <input type="text" name="address_line1" class="form-control" placeholder="12 rue de la Paix">
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
                            <div class="form-group">
                                <label>Code postal</label>
                                <input type="text" name="postal_code" class="form-control" placeholder="75000">
                            </div>
                            <div class="form-group">
                                <label>Ville *</label>
                                <input type="text" name="city" class="form-control" placeholder="Paris">
                            </div>
                            <div class="form-group">
                                <label>Pays</label>
                                <input type="text" name="country" class="form-control" value="France">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MÉTHODE DE PAIEMENT -->
                <div class="bg-white rounded p-3">
                    <h3 style="font-weight:800;font-size:18px;margin-bottom:16px">💳 Mode de paiement</h3>
                    <div style="display:flex;flex-direction:column;gap:10px">
                            <input type="hidden" name="payment_method" value="paytech">
                        <label style="display:flex;gap:12px;align-items:center;border:2px solid var(--primary);border-radius:10px;padding:14px;background:#fff9f5">
                            <div style="width:18px;height:18px;border-radius:50%;background:var(--primary);flex-shrink:0"></div>
                            <div>
                                <div style="font-weight:700">🔐 PayTech</div>
                                <div style="font-size:12px;color:var(--text-muted)">Wave, Orange Money, Free Money, Carte bancaire</div>
                            </div>
                            <img src="https://paytech.sn/public/images/paytech-logo.png"
                                 alt="PayTech" style="height:24px;margin-left:auto"
                                 onerror="this.style.display='none'">
                        </label>
                    </div>
                </div>
            </div>

            <!-- RÉSUMÉ -->
            <div>
                <div class="order-summary">
                    <h3>Récapitulatif</h3>
                    <?php foreach ($cart as $item): ?>
                    <div style="display:flex;gap:10px;padding:10px 0;border-bottom:1px solid var(--border)">
                        <img src="<?= h($item['image_url'] ?: 'https://picsum.photos/seed/'.$item['product_id'].'/80/80') ?>"
                             style="width:56px;height:56px;border-radius:8px;object-fit:cover" alt="">
                        <div style="flex:1;font-size:13px">
                            <div style="font-weight:700"><?= h($item['name']) ?></div>
                            <div style="color:var(--text-muted)">Qté : <?= $item['quantity'] ?></div>
                            <div style="color:var(--primary);font-weight:800">
                                <?= formatPrice(($item['price'] + ($item['extra_price'] ?? 0)) * $item['quantity']) ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div class="summary-row"><span>Sous-total</span><span><?= formatPrice($total) ?></span></div>
                    <?php if ($couponDiscount > 0): ?>
                    <div class="summary-row" style="color:var(--success)">
                        <span>Réduction</span><span>-<?= formatPrice($couponDiscount) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="summary-row">
                        <span>Livraison</span>
                        <span><?= $shipping == 0 ? '<span style="color:var(--success)">Gratuite</span>' : formatPrice($shipping) ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>Total</span><span><?= formatPrice($finalTotal) ?></span>
                    </div>

                    <button type="submit" class="checkout-btn" style="margin-top:20px">
                        🔒 Confirmer la commande <?= formatPrice($finalTotal) ?>
                    </button>
                    <div style="text-align:center;font-size:11px;color:var(--text-muted);margin-top:8px">
                        Paiement 100% sécurisé
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.querySelectorAll('input[name="address_id"]').forEach(r => {
    r.addEventListener('change', function () {
        document.getElementById('newAddressForm').style.display =
            this.value === '0' ? 'block' : 'none';
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
