<?php
require_once __DIR__ . '/../includes/functions.php';
startSession();
requireLogin();

$db = getDB();
$orderNumber = $_SESSION['paytech_order'] ?? null;
$order = null;
$items = [];
$address = null;

if ($orderNumber) {
    $stmt = $db->prepare("SELECT o.*, a.full_name, a.phone, a.address_line1, a.city, a.postal_code, a.country
                          FROM orders o
                          LEFT JOIN addresses a ON a.id = o.address_id
                          WHERE o.order_number = ? AND o.user_id = ?");
    $stmt->execute([$orderNumber, $_SESSION['user_id']]);
    $order = $stmt->fetch();
    unset($_SESSION['paytech_order']);
}

if ($order) {
    $db->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$_SESSION['user_id']]);
    $stmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmt->execute([$order['id']]);
    $items = $stmt->fetchAll();
}

$pageTitle = 'Paiement confirmé';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width:720px;margin:40px auto">

    <!-- En-tête succès -->
    <div style="background:#fff;border-radius:16px;padding:36px 32px 24px;box-shadow:0 4px 24px rgba(0,0,0,.07);text-align:center;margin-bottom:20px">
        <div style="font-size:64px;margin-bottom:12px">✅</div>
        <h1 style="font-size:26px;font-weight:900;color:#22c55e;margin-bottom:6px">Paiement réussi !</h1>
        <?php if ($order): ?>
        <p style="color:var(--text-muted);font-size:15px;margin:0">
            Commande <strong><?= h($order['order_number']) ?></strong> —
            <?= $order['payment_status'] === 'paid' ? '<span style="color:#22c55e;font-weight:700">Payé</span>' : '<span style="color:#f59e0b;font-weight:700">Confirmation en cours</span>' ?>
        </p>
        <?php endif; ?>
    </div>

    <?php if ($order): ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px">

        <!-- Récapitulatif financier -->
        <div style="background:#fff;border-radius:14px;padding:22px;box-shadow:0 2px 12px rgba(0,0,0,.06)">
            <h3 style="font-size:15px;font-weight:800;margin:0 0 14px">💰 Récapitulatif</h3>
            <div style="display:flex;justify-content:space-between;font-size:14px;margin-bottom:8px">
                <span style="color:var(--text-muted)">Sous-total</span>
                <span><?= formatPrice((float)$order['subtotal']) ?></span>
            </div>
            <?php if ((float)$order['discount_amount'] > 0): ?>
            <div style="display:flex;justify-content:space-between;font-size:14px;margin-bottom:8px;color:#22c55e">
                <span>Réduction <?= $order['coupon_code'] ? '('.$order['coupon_code'].')' : '' ?></span>
                <span>-<?= formatPrice((float)$order['discount_amount']) ?></span>
            </div>
            <?php endif; ?>
            <div style="display:flex;justify-content:space-between;font-size:14px;margin-bottom:12px">
                <span style="color:var(--text-muted)">Livraison</span>
                <span><?= (float)$order['shipping_cost'] == 0 ? '<span style="color:#22c55e">Gratuite</span>' : formatPrice((float)$order['shipping_cost']) ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:16px;font-weight:900;border-top:2px solid var(--border);padding-top:10px">
                <span>Total</span>
                <span style="color:var(--primary)"><?= formatPrice((float)$order['total']) ?></span>
            </div>
        </div>

        <!-- Adresse de livraison -->
        <div style="background:#fff;border-radius:14px;padding:22px;box-shadow:0 2px 12px rgba(0,0,0,.06)">
            <h3 style="font-size:15px;font-weight:800;margin:0 0 14px">📍 Livraison</h3>
            <div style="font-size:14px;line-height:1.8">
                <div style="font-weight:700"><?= h($order['full_name']) ?></div>
                <?php if ($order['phone']): ?>
                <div style="color:var(--text-muted)"><?= h($order['phone']) ?></div>
                <?php endif; ?>
                <div><?= h($order['address_line1']) ?></div>
                <div><?= h($order['postal_code']) ?> <?= h($order['city']) ?></div>
                <div><?= h($order['country']) ?></div>
            </div>
        </div>
    </div>

    <!-- Articles commandés -->
    <div style="background:#fff;border-radius:14px;padding:22px;box-shadow:0 2px 12px rgba(0,0,0,.06);margin-bottom:20px">
        <h3 style="font-size:15px;font-weight:800;margin:0 0 16px">🛍️ Articles commandés (<?= count($items) ?>)</h3>
        <?php foreach ($items as $item): ?>
        <div style="display:flex;gap:14px;padding:12px 0;border-bottom:1px solid var(--border)">
            <img src="<?= h($item['product_image'] ?: 'https://picsum.photos/seed/'.$item['product_id'].'/80/80') ?>"
                 style="width:64px;height:64px;border-radius:10px;object-fit:cover;flex-shrink:0" alt="">
            <div style="flex:1">
                <div style="font-weight:700;font-size:14px;margin-bottom:4px"><?= h($item['product_name']) ?></div>
                <div style="font-size:13px;color:var(--text-muted)">Quantité : <?= $item['quantity'] ?></div>
                <div style="font-size:13px;color:var(--text-muted)">Prix unitaire : <?= formatPrice((float)$item['price']) ?></div>
            </div>
            <div style="font-weight:800;color:var(--primary);font-size:15px;white-space:nowrap">
                <?= formatPrice((float)$item['subtotal']) ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php endif; ?>

    <!-- Actions -->
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
        <a href="<?= SITE_URL ?>/pages/orders.php"
           style="background:var(--primary);color:#fff;padding:13px 30px;border-radius:8px;font-weight:700;text-decoration:none;font-size:15px">
            Voir mes commandes
        </a>
        <a href="<?= SITE_URL ?>"
           style="background:#f1f1f1;color:#333;padding:13px 30px;border-radius:8px;font-weight:700;text-decoration:none;font-size:15px">
            Continuer mes achats
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
