<?php
require_once __DIR__ . '/../includes/functions.php';
startSession();
requireLogin();

$db  = getDB();
$id  = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("
    SELECT o.*,
           a.full_name, a.phone, a.address_line1, a.address_line2,
           a.city, a.postal_code, a.state, a.country
    FROM orders o
    LEFT JOIN addresses a ON a.id = o.address_id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: ' . SITE_URL . '/pages/orders.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt->execute([$order['id']]);
$items = $stmt->fetchAll();

$statusLabels = [
    'pending'    => ['⏳ En attente',     '#f59e0b', '#fef9c3'],
    'confirmed'  => ['✅ Confirmée',      '#16a34a', '#dcfce7'],
    'processing' => ['⚙️ En préparation', '#2563eb', '#dbeafe'],
    'shipped'    => ['🚚 Expédiée',       '#7c3aed', '#ede9fe'],
    'delivered'  => ['📦 Livrée',         '#16a34a', '#dcfce7'],
    'cancelled'  => ['❌ Annulée',        '#dc2626', '#fee2e2'],
    'refunded'   => ['💸 Remboursée',     '#6b7280', '#f3f4f6'],
];

$timeline = ['pending', 'confirmed', 'processing', 'shipped', 'delivered'];
$currentStep = array_search($order['order_status'], $timeline);

[$statusLabel, $statusColor, $statusBg] = $statusLabels[$order['order_status']] ?? ['Inconnu', '#6b7280', '#f3f4f6'];

$pageTitle = 'Commande ' . $order['order_number'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width:800px;margin:30px auto">

    <div class="breadcrumb">
        <a href="<?= SITE_URL ?>">Accueil</a> <span>›</span>
        <a href="<?= SITE_URL ?>/pages/orders.php">Mes commandes</a> <span>›</span>
        <span class="current"><?= h($order['order_number']) ?></span>
    </div>

    <!-- En-tête commande -->
    <div style="background:#fff;border-radius:14px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,.06);margin-bottom:18px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
        <div>
            <h1 style="font-size:22px;font-weight:900;margin:0 0 4px">Commande # <?= h($order['order_number']) ?></h1>
            <div style="font-size:13px;color:var(--text-muted)">Passée le <?= date('d/m/Y à H:i', strtotime($order['created_at'])) ?></div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
            <span style="background:<?= $statusBg ?>;color:<?= $statusColor ?>;padding:6px 14px;border-radius:20px;font-weight:700;font-size:13px">
                <?= $statusLabel ?>
            </span>
            <?php if ($order['payment_status'] === 'paid'): ?>
                <span style="background:#dcfce7;color:#16a34a;padding:6px 14px;border-radius:20px;font-weight:700;font-size:13px">✅ Payé via PayTech</span>
            <?php elseif ($order['payment_status'] === 'failed'): ?>
                <span style="background:#fee2e2;color:#dc2626;padding:6px 14px;border-radius:20px;font-weight:700;font-size:13px">❌ Paiement échoué</span>
            <?php else: ?>
                <span style="background:#fef9c3;color:#a16207;padding:6px 14px;border-radius:20px;font-weight:700;font-size:13px">⏳ Paiement en attente</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Timeline de suivi -->
    <?php if (!in_array($order['order_status'], ['cancelled', 'refunded'])): ?>
    <div style="background:#fff;border-radius:14px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,.06);margin-bottom:18px">
        <h3 style="font-size:15px;font-weight:800;margin:0 0 20px">🗺️ Suivi de commande</h3>
        <div style="display:flex;align-items:center;justify-content:space-between;position:relative">
            <!-- Barre de progression -->
            <div style="position:absolute;top:18px;left:0;right:0;height:3px;background:#e5e7eb;z-index:0"></div>
            <?php if ($currentStep !== false): ?>
            <div style="position:absolute;top:18px;left:0;height:3px;background:var(--primary);z-index:1;width:<?= $currentStep === 0 ? '0' : ($currentStep / (count($timeline) - 1) * 100) ?>%"></div>
            <?php endif; ?>

            <?php
            $stepLabels = ['📋 Reçue', '✅ Confirmée', '⚙️ Préparation', '🚚 Expédiée', '📦 Livrée'];
            foreach ($timeline as $i => $step):
                $done    = $currentStep !== false && $i <= $currentStep;
                $current = $currentStep !== false && $i === $currentStep;
            ?>
            <div style="display:flex;flex-direction:column;align-items:center;gap:8px;z-index:2;flex:1">
                <div style="width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;
                            background:<?= $done ? 'var(--primary)' : '#e5e7eb' ?>;
                            color:<?= $done ? '#fff' : '#9ca3af' ?>;
                            border:<?= $current ? '3px solid var(--primary)' : '3px solid transparent' ?>;
                            box-shadow:<?= $current ? '0 0 0 4px rgba(255,77,0,.15)' : 'none' ?>">
                    <?= $done ? '✓' : ($i + 1) ?>
                </div>
                <div style="font-size:11px;font-weight:<?= $current ? '800' : '600' ?>;color:<?= $done ? 'var(--primary)' : '#9ca3af' ?>;text-align:center;white-space:nowrap">
                    <?= $stepLabels[$i] ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if ($order['tracking_number']): ?>
        <div style="margin-top:16px;padding:10px 14px;background:#f8f9fa;border-radius:8px;font-size:13px">
            📦 Numéro de suivi : <strong><?= h($order['tracking_number']) ?></strong>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px">

        <!-- Adresse de livraison -->
        <div style="background:#fff;border-radius:14px;padding:22px;box-shadow:0 2px 12px rgba(0,0,0,.06)">
            <h3 style="font-size:15px;font-weight:800;margin:0 0 14px">📍 Adresse de livraison</h3>
            <div style="font-size:14px;line-height:1.9;color:#374151">
                <div style="font-weight:700"><?= h($order['full_name']) ?></div>
                <?php if ($order['phone']): ?>
                <div>📞 <?= h($order['phone']) ?></div>
                <?php endif; ?>
                <div><?= h($order['address_line1']) ?></div>
                <?php if ($order['address_line2']): ?>
                <div><?= h($order['address_line2']) ?></div>
                <?php endif; ?>
                <div><?= h($order['postal_code']) ?> <?= h($order['city']) ?><?= $order['state'] ? ', '.h($order['state']) : '' ?></div>
                <div><?= h($order['country']) ?></div>
            </div>
        </div>

        <!-- Récapitulatif financier -->
        <div style="background:#fff;border-radius:14px;padding:22px;box-shadow:0 2px 12px rgba(0,0,0,.06)">
            <h3 style="font-size:15px;font-weight:800;margin:0 0 14px">💰 Récapitulatif</h3>
            <div style="font-size:14px">
                <div style="display:flex;justify-content:space-between;margin-bottom:9px">
                    <span style="color:var(--text-muted)">Sous-total</span>
                    <span><?= formatPrice((float)$order['subtotal']) ?></span>
                </div>
                <?php if ((float)$order['discount_amount'] > 0): ?>
                <div style="display:flex;justify-content:space-between;margin-bottom:9px;color:#16a34a">
                    <span>Réduction <?= $order['coupon_code'] ? '<span style="background:#dcfce7;padding:1px 6px;border-radius:4px;font-size:12px">'.h($order['coupon_code']).'</span>' : '' ?></span>
                    <span>-<?= formatPrice((float)$order['discount_amount']) ?></span>
                </div>
                <?php endif; ?>
                <div style="display:flex;justify-content:space-between;margin-bottom:9px">
                    <span style="color:var(--text-muted)">Livraison</span>
                    <span><?= (float)$order['shipping_cost'] == 0 ? '<span style="color:#16a34a">Gratuite</span>' : formatPrice((float)$order['shipping_cost']) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:16px;font-weight:900;border-top:2px solid var(--border);padding-top:10px;margin-top:4px">
                    <span>Total payé</span>
                    <span style="color:var(--primary)"><?= formatPrice((float)$order['total']) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Articles -->
    <div style="background:#fff;border-radius:14px;padding:22px;box-shadow:0 2px 12px rgba(0,0,0,.06);margin-bottom:24px">
        <h3 style="font-size:15px;font-weight:800;margin:0 0 16px">🛍️ Articles (<?= count($items) ?>)</h3>
        <?php foreach ($items as $index => $item): ?>
        <div style="display:flex;gap:16px;padding:14px 0;<?= $index < count($items) - 1 ? 'border-bottom:1px solid var(--border)' : '' ?>">
            <a href="<?= SITE_URL ?>/pages/product.php?id=<?= (int)$item['product_id'] ?>">
                <img src="<?= h($item['product_image'] ?: 'https://picsum.photos/seed/'.$item['product_id'].'/80/80') ?>"
                     style="width:72px;height:72px;border-radius:10px;object-fit:cover;flex-shrink:0;border:1px solid var(--border)" alt="">
            </a>
            <div style="flex:1;min-width:0">
                <a href="<?= SITE_URL ?>/pages/product.php?id=<?= (int)$item['product_id'] ?>"
                   style="font-weight:700;font-size:15px;color:#111;text-decoration:none;display:block;margin-bottom:4px">
                    <?= h($item['product_name']) ?>
                </a>
                <div style="font-size:13px;color:var(--text-muted)">Prix unitaire : <?= formatPrice((float)$item['price']) ?></div>
                <div style="font-size:13px;color:var(--text-muted)">Quantité : <?= $item['quantity'] ?></div>
            </div>
            <div style="font-weight:900;font-size:16px;color:var(--primary);white-space:nowrap;align-self:center">
                <?= formatPrice((float)$item['subtotal']) ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Actions -->
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
        <a href="<?= SITE_URL ?>/pages/orders.php"
           style="background:#f1f1f1;color:#333;padding:12px 28px;border-radius:8px;font-weight:700;text-decoration:none">
            ← Mes commandes
        </a>
        <a href="<?= SITE_URL ?>"
           style="background:var(--primary);color:#fff;padding:12px 28px;border-radius:8px;font-weight:700;text-decoration:none">
            Continuer mes achats
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
