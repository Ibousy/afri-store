<?php
require_once __DIR__ . '/../includes/functions.php';
startSession();
requireLogin();

$pageTitle = 'Mes Commandes';
require_once __DIR__ . '/../includes/header.php';

$db = getDB();
$stmt = $db->prepare("
    SELECT o.*, a.city, a.country
    FROM orders o
    LEFT JOIN addresses a ON a.id = o.address_id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

$statusLabels = [
    'pending'    => ['⏳ En attente',     'status-pending'],
    'confirmed'  => ['✅ Confirmée',      'status-confirmed'],
    'processing' => ['⚙️ En préparation', 'status-processing'],
    'shipped'    => ['🚚 Expédiée',       'status-shipped'],
    'delivered'  => ['📦 Livrée',         'status-delivered'],
    'cancelled'  => ['❌ Annulée',        'status-cancelled'],
    'refunded'   => ['💸 Remboursée',     'status-cancelled'],
];
?>

<div class="container">
    <div class="breadcrumb">
        <a href="<?= SITE_URL ?>">Accueil</a> <span>›</span>
        <a href="<?= SITE_URL ?>/pages/account.php">Mon compte</a> <span>›</span>
        <span class="current">Mes commandes</span>
    </div>

    <h1 style="font-size:26px;font-weight:900;margin-bottom:20px">📦 Mes Commandes (<?= count($orders) ?>)</h1>

    <?php if (empty($orders)): ?>
    <div class="empty-state">
        <div class="empty-icon">📦</div>
        <h3>Aucune commande</h3>
        <p>Vous n'avez pas encore passé de commande.</p>
        <a href="<?= SITE_URL ?>" class="btn-primary" style="display:inline-block;padding:14px 36px;border-radius:30px">Commencer à shopper</a>
    </div>
    <?php else: ?>
    <?php foreach ($orders as $order):
        $stmt2 = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmt2->execute([$order['id']]);
        $items = $stmt2->fetchAll();
        [$statusLabel, $statusClass] = $statusLabels[$order['order_status']] ?? ['Inconnu', 'status-pending'];
    ?>
    <div class="order-card">
        <div class="order-header">
            <div>
                <div class="order-number"># <?= h($order['order_number']) ?></div>
                <div style="font-size:13px;color:var(--text-muted)">
                    <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                    · <?= h($order['city']) ?>, <?= h($order['country']) ?>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                <?php if ($order['tracking_number']): ?>
                    <span style="font-size:13px">🚚 <?= h($order['tracking_number']) ?></span>
                <?php endif; ?>
                <span class="order-status <?= $statusClass ?>"><?= $statusLabel ?></span>
                <!-- Statut paiement -->
                <?php if ($order['payment_status'] === 'paid'): ?>
                    <span style="background:#dcfce7;color:#16a34a;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:700">✅ Payé</span>
                <?php elseif ($order['payment_status'] === 'failed'): ?>
                    <span style="background:#fee2e2;color:#dc2626;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:700">❌ Échoué</span>
                <?php else: ?>
                    <span style="background:#fef9c3;color:#a16207;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:700">⏳ En attente</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Aperçu des articles -->
        <?php foreach ($items as $item): ?>
        <div class="order-item-mini">
            <img src="<?= h($item['product_image'] ?: 'https://picsum.photos/seed/'.$item['product_id'].'/80/80') ?>" alt="">
            <div class="item-info">
                <div style="font-weight:700"><?= h($item['product_name']) ?></div>
                <div style="color:var(--text-muted)">Qté : <?= $item['quantity'] ?> × <?= formatPrice((float)$item['price']) ?></div>
            </div>
            <div style="font-weight:800;color:var(--primary)"><?= formatPrice((float)$item['subtotal']) ?></div>
        </div>
        <?php endforeach; ?>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:12px;padding-top:12px;border-top:1px solid var(--border);flex-wrap:wrap;gap:8px">
            <div style="font-size:13px;color:var(--text-muted)">
                <?= count($items) ?> article<?= count($items) > 1 ? 's' : '' ?>
                <?php if ((float)$order['shipping_cost'] == 0): ?>
                    · <span style="color:#22c55e">Livraison gratuite</span>
                <?php else: ?>
                    · Livraison : <?= formatPrice((float)$order['shipping_cost']) ?>
                <?php endif; ?>
            </div>
            <div style="display:flex;align-items:center;gap:14px">
                <div class="order-total">Total : <?= formatPrice((float)$order['total']) ?></div>
                <a href="<?= SITE_URL ?>/pages/order_detail.php?id=<?= $order['id'] ?>"
                   style="background:var(--primary);color:#fff;padding:7px 18px;border-radius:8px;font-size:13px;font-weight:700;text-decoration:none">
                    Voir détails →
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
