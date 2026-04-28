<?php
require_once __DIR__ . '/../includes/functions.php';
startSession();
if (!isAdmin()) { header('Location: ' . SITE_URL . '/admin/login.php'); exit; }

$db = getDB();

// Filtres
$filter  = sanitize($_GET['status'] ?? '');
$search  = sanitize($_GET['q'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$limit   = 20;
$offset  = ($page - 1) * $limit;

$where  = ['1=1'];
$params = [];

if ($filter)  { $where[] = 'o.payment_status = ?'; $params[] = $filter; }
if ($search)  { $where[] = '(o.order_number LIKE ? OR u.name LIKE ? OR u.email LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }

$whereSQL = implode(' AND ', $where);

$total = $db->prepare("SELECT COUNT(*) FROM orders o JOIN users u ON u.id = o.user_id WHERE $whereSQL");
$total->execute($params);
$total = (int)$total->fetchColumn();
$totalPages = max(1, ceil($total / $limit));

$orders = $db->prepare("
    SELECT o.*, u.name AS user_name, u.email AS user_email
    FROM orders o
    JOIN users u ON u.id = o.user_id
    WHERE $whereSQL
    ORDER BY o.created_at DESC
    LIMIT $limit OFFSET $offset
");
$orders->execute($params);
$orders = $orders->fetchAll();

// Stats paiements
$stats = [
    'paid'    => $db->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'paid'")->fetchColumn(),
    'pending' => $db->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'pending'")->fetchColumn(),
    'failed'  => $db->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'failed'")->fetchColumn(),
    'total'   => $db->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_status = 'paid'")->fetchColumn(),
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Paiements — Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="admin-logo">🛍️ <?= SITE_NAME ?><br><small style="font-size:12px;opacity:.6">Administration</small></div>
        <nav class="admin-menu">
            <a href="<?= SITE_URL ?>/admin/index.php">📊 Dashboard</a>
            <a href="<?= SITE_URL ?>/admin/products.php">📦 Produits</a>
            <a href="<?= SITE_URL ?>/admin/orders.php">🛒 Commandes</a>
            <a href="<?= SITE_URL ?>/admin/payments.php" style="color:white;background:rgba(255,255,255,.15)">💳 Paiements</a>
            <a href="<?= SITE_URL ?>/admin/users.php">👥 Utilisateurs</a>
            <a href="<?= SITE_URL ?>/admin/categories.php">🗂️ Catégories</a>
            <a href="<?= SITE_URL ?>/admin/coupons.php">🎟️ Coupons</a>
            <a href="<?= SITE_URL ?>" style="margin-top:20px;border-top:1px solid rgba(255,255,255,.1);padding-top:16px">🏠 Voir le site</a>
            <a href="<?= SITE_URL ?>/api/auth.php?action=logout" style="color:#FF6B6B">🚪 Déconnexion</a>
        </nav>
    </aside>

    <main class="admin-main">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
            <h1 style="font-size:22px;font-weight:900">💳 Paiements</h1>
            <span style="color:var(--text-muted);font-size:14px"><?= date('d/m/Y H:i') ?></span>
        </div>

        <!-- Stats -->
        <div class="stats-grid" style="margin-bottom:24px">
            <div class="stat-card" style="border-left:4px solid #22c55e">
                <div class="stat-icon">✅</div>
                <div class="stat-val"><?= number_format($stats['paid']) ?></div>
                <div class="stat-label">Paiements confirmés</div>
            </div>
            <div class="stat-card" style="border-left:4px solid #f59e0b">
                <div class="stat-icon">⏳</div>
                <div class="stat-val"><?= number_format($stats['pending']) ?></div>
                <div class="stat-label">En attente</div>
            </div>
            <div class="stat-card" style="border-left:4px solid #ef4444">
                <div class="stat-icon">❌</div>
                <div class="stat-val"><?= number_format($stats['failed']) ?></div>
                <div class="stat-label">Échoués / Annulés</div>
            </div>
            <div class="stat-card" style="border-left:4px solid var(--primary)">
                <div class="stat-icon">💰</div>
                <div class="stat-val"><?= formatPrice((float)$stats['total']) ?></div>
                <div class="stat-label">Total encaissé</div>
            </div>
        </div>

        <!-- Filtres + Recherche -->
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px;align-items:center">
            <form method="GET" style="display:flex;gap:8px;flex:1;min-width:220px">
                <?php if ($filter): ?><input type="hidden" name="status" value="<?= h($filter) ?>"><?php endif; ?>
                <input type="text" name="q" value="<?= h($search) ?>" placeholder="Rechercher commande, client..."
                       class="form-control" style="flex:1;padding:8px 14px;font-size:14px">
                <button type="submit" style="background:var(--primary);color:#fff;padding:8px 16px;border-radius:8px;font-weight:700;font-size:13px">🔍</button>
                <?php if ($search): ?><a href="?<?= $filter?'status='.$filter:'' ?>" style="padding:8px 12px;background:#f1f1f1;border-radius:8px;font-size:13px;color:#666">✕</a><?php endif; ?>
            </form>
            <div style="display:flex;gap:6px;flex-wrap:wrap">
                <a href="?<?= $search?'q='.urlencode($search):'' ?>" class="sort-btn <?= !$filter?'active':'' ?>">Tous</a>
                <a href="?status=paid<?= $search?'&q='.urlencode($search):'' ?>" class="sort-btn <?= $filter==='paid'?'active':'' ?>">✅ Payés</a>
                <a href="?status=pending<?= $search?'&q='.urlencode($search):'' ?>" class="sort-btn <?= $filter==='pending'?'active':'' ?>">⏳ En attente</a>
                <a href="?status=failed<?= $search?'&q='.urlencode($search):'' ?>" class="sort-btn <?= $filter==='failed'?'active':'' ?>">❌ Échoués</a>
            </div>
        </div>

        <!-- Tableau -->
        <div class="bg-white rounded" style="overflow:auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>N° Commande</th>
                        <th>Client</th>
                        <th>Méthode</th>
                        <th>Montant</th>
                        <th>Statut paiement</th>
                        <th>Statut commande</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted)">Aucun paiement trouvé.</td></tr>
                <?php endif; ?>
                <?php foreach ($orders as $o): ?>
                    <?php
                    $pBg    = match($o['payment_status']) { 'paid' => '#dcfce7', 'failed' => '#fee2e2', default => '#fef9c3' };
                    $pColor = match($o['payment_status']) { 'paid' => '#16a34a', 'failed' => '#dc2626', default => '#a16207' };
                    $pLabel = match($o['payment_status']) { 'paid' => '✅ Payé', 'failed' => '❌ Échoué', default => '⏳ En attente' };
                    $oBg    = match($o['order_status'])   { 'delivered' => '#dcfce7', 'cancelled' => '#fee2e2', 'shipped' => '#ede9fe', default => '#f1f5f9' };
                    $oColor = match($o['order_status'])   { 'delivered' => '#16a34a', 'cancelled' => '#dc2626', 'shipped' => '#7c3aed', default => '#475569' };
                    $oLabels = ['pending'=>'⏳ En attente','confirmed'=>'✅ Confirmée','processing'=>'⚙️ Préparation','shipped'=>'🚚 Expédiée','delivered'=>'📦 Livrée','cancelled'=>'❌ Annulée','refunded'=>'💸 Remboursée'];
                    ?>
                    <tr>
                        <td><strong style="font-family:monospace"><?= h($o['order_number']) ?></strong></td>
                        <td>
                            <div style="font-weight:700;font-size:13px"><?= h($o['user_name']) ?></div>
                            <div style="font-size:11px;color:var(--text-muted)"><?= h($o['user_email']) ?></div>
                        </td>
                        <td>
                            <span style="background:#fff4e5;color:#e65100;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:700">
                                🔐 <?= strtoupper(h($o['payment_method'])) ?>
                            </span>
                        </td>
                        <td style="font-weight:900;color:var(--primary);font-size:15px"><?= formatPrice((float)$o['total']) ?></td>
                        <td>
                            <span style="background:<?= $pBg ?>;color:<?= $pColor ?>;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700">
                                <?= $pLabel ?>
                            </span>
                        </td>
                        <td>
                            <span style="background:<?= $oBg ?>;color:<?= $oColor ?>;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700">
                                <?= $oLabels[$o['order_status']] ?? $o['order_status'] ?>
                            </span>
                        </td>
                        <td style="font-size:12px;color:var(--text-muted);white-space:nowrap"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&status=<?= urlencode($filter) ?>&q=<?= urlencode($search) ?>"
                   class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
