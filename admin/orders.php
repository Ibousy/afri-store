<?php
require_once __DIR__ . '/../includes/functions.php';
startSession();
if (!isAdmin()) { header('Location: ' . SITE_URL . '/admin/login.php'); exit; }

$db = getDB();
$success = '';

// Mise à jour statut
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $orderId = (int)$_POST['order_id'];
    $status  = sanitize($_POST['order_status'] ?? '');
    $tracking = sanitize($_POST['tracking_number'] ?? '');
    $db->prepare("UPDATE orders SET order_status = ?, tracking_number = ? WHERE id = ?")->execute([$status, $tracking, $orderId]);
    $success = "Commande #$orderId mise à jour.";
}

$page   = max(1,(int)($_GET['page'] ?? 1));
$limit  = 20;
$offset = ($page-1)*$limit;
$filter = sanitize($_GET['status'] ?? '');

$where  = ['1=1']; $params = [];
if ($filter) { $where[] = 'o.order_status = ?'; $params[] = $filter; }

$total = $db->prepare("SELECT COUNT(*) FROM orders o WHERE ".implode(' AND ',$where));
$total->execute($params); $total = $total->fetchColumn();
$totalPages = ceil($total/$limit);

$orders = $db->prepare("
    SELECT o.*, u.name AS user_name, u.email AS user_email, a.city, a.country
    FROM orders o
    JOIN users u ON u.id = o.user_id
    LEFT JOIN addresses a ON a.id = o.address_id
    WHERE ".implode(' AND ',$where)."
    ORDER BY o.created_at DESC LIMIT $limit OFFSET $offset
");
$orders->execute($params);
$orders = $orders->fetchAll();

$allStatuses = ['pending','confirmed','processing','shipped','delivered','cancelled','refunded'];
$statusLabels = ['pending'=>'⏳ En attente','confirmed'=>'✅ Confirmée','processing'=>'⚙️ Préparation','shipped'=>'🚚 Expédiée','delivered'=>'📦 Livrée','cancelled'=>'❌ Annulée','refunded'=>'💸 Remboursée'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commandes — Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="admin-logo">🛍️ <?= SITE_NAME ?></div>
        <nav class="admin-menu">
            <a href="<?= SITE_URL ?>/admin/index.php">📊 Dashboard</a>
            <a href="<?= SITE_URL ?>/admin/products.php">📦 Produits</a>
            <a href="<?= SITE_URL ?>/admin/orders.php" style="color:white;background:rgba(255,255,255,.15)">🛒 Commandes</a>
            <a href="<?= SITE_URL ?>/admin/users.php">👥 Utilisateurs</a>
            <a href="<?= SITE_URL ?>/admin/categories.php">🗂️ Catégories</a>
            <a href="<?= SITE_URL ?>/admin/coupons.php">🎟️ Coupons</a>
            <a href="<?= SITE_URL ?>">🏠 Voir le site</a>
            <a href="<?= SITE_URL ?>/api/auth.php?action=logout" style="color:#FF6B6B">🚪 Déconnexion</a>
        </nav>
    </aside>
    <main class="admin-main">
        <?php if ($success): ?><div class="flash-message flash-success">✅ <?= h($success) ?></div><?php endif; ?>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px">
            <h1 style="font-size:22px;font-weight:900">🛒 Commandes (<?= $total ?>)</h1>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <a href="?" class="sort-btn <?= !$filter?'active':'' ?>">Toutes</a>
                <?php foreach ($allStatuses as $s): ?>
                    <a href="?status=<?= $s ?>" class="sort-btn <?= $filter===$s?'active':'' ?>"><?= $statusLabels[$s] ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="bg-white rounded" style="overflow:auto">
            <table class="data-table">
                <thead>
                    <tr><th>N°</th><th>Client</th><th>Livraison</th><th>Total</th><th>Statut</th><th>Date</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                    <tr>
                        <td><strong><?= h($o['order_number']) ?></strong></td>
                        <td>
                            <div style="font-weight:700"><?= h($o['user_name']) ?></div>
                            <div style="font-size:12px;color:var(--text-muted)"><?= h($o['user_email']) ?></div>
                        </td>
                        <td style="font-size:13px"><?= h($o['city'] ?? '—') ?>, <?= h($o['country'] ?? '') ?></td>
                        <td style="font-weight:800;color:var(--primary)"><?= formatPrice($o['total']) ?></td>
                        <td>
                            <form method="POST" style="display:flex;gap:6px;align-items:center">
                                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                <select name="order_status" class="form-control" style="padding:4px 8px;font-size:12px;width:auto">
                                    <?php foreach ($allStatuses as $s): ?>
                                        <option value="<?= $s ?>" <?= $o['order_status']===$s?'selected':'' ?>><?= $statusLabels[$s] ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="text" name="tracking_number" placeholder="Tracking" value="<?= h($o['tracking_number'] ?? '') ?>"
                                       class="form-control" style="padding:4px 8px;font-size:12px;width:120px">
                                <button type="submit" style="background:var(--primary);color:white;padding:5px 10px;border-radius:6px;font-size:12px;white-space:nowrap">💾</button>
                            </form>
                        </td>
                        <td style="font-size:12px;color:var(--text-muted)"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                        <td>
                            <a href="#" style="font-size:13px;color:var(--primary)">👁️ Détail</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPages>1): ?>
        <div class="pagination">
            <?php for($i=1;$i<=$totalPages;$i++): ?>
                <a href="?page=<?=$i?>&status=<?=urlencode($filter)?>" class="page-btn <?=$i===$page?'active':''?>"><?=$i?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
