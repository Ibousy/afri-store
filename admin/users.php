<?php
require_once __DIR__ . '/../includes/functions.php';
startSession();
if (!isAdmin()) { header('Location: ' . SITE_URL . '/admin/login.php'); exit; }

$db = getDB();

$page   = max(1,(int)($_GET['page'] ?? 1));
$limit  = 20;
$offset = ($page-1)*$limit;
$search = sanitize($_GET['search'] ?? '');

$where  = ['1=1']; $params = [];
if ($search) { $where[] = '(u.name LIKE ? OR u.email LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }

$total = $db->prepare("SELECT COUNT(*) FROM users u WHERE ".implode(' AND ',$where));
$total->execute($params); $total = $total->fetchColumn();
$totalPages = ceil($total/$limit);

$users = $db->prepare("
    SELECT u.*,
           COUNT(DISTINCT o.id) AS order_count,
           COALESCE(SUM(o.total),0) AS total_spent
    FROM users u
    LEFT JOIN orders o ON o.user_id = u.id AND o.order_status != 'cancelled'
    WHERE ".implode(' AND ',$where)."
    GROUP BY u.id
    ORDER BY u.created_at DESC LIMIT $limit OFFSET $offset
");
$users->execute($params);
$users = $users->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><title>Utilisateurs — Admin</title>
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
            <a href="<?= SITE_URL ?>/admin/orders.php">🛒 Commandes</a>
            <a href="<?= SITE_URL ?>/admin/payments.php">💳 Paiements</a>
            <a href="<?= SITE_URL ?>/admin/users.php" style="color:white;background:rgba(255,255,255,.15)">👥 Utilisateurs</a>
            <a href="<?= SITE_URL ?>/admin/categories.php">🗂️ Catégories</a>
            <a href="<?= SITE_URL ?>/admin/coupons.php">🎟️ Coupons</a>
            <a href="<?= SITE_URL ?>">🏠 Voir le site</a>
            <a href="<?= SITE_URL ?>/api/auth.php?action=logout" style="color:#FF6B6B">🚪 Déconnexion</a>
        </nav>
    </aside>
    <main class="admin-main">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;gap:12px;flex-wrap:wrap">
            <h1 style="font-size:22px;font-weight:900">👥 Utilisateurs (<?= $total ?>)</h1>
            <form method="GET">
                <input type="text" name="search" value="<?= h($search) ?>" placeholder="Rechercher nom ou email..." class="form-control" style="width:250px;padding:8px 12px">
            </form>
        </div>
        <div class="bg-white rounded" style="overflow:auto">
            <table class="data-table">
                <thead>
                    <tr><th>ID</th><th>Nom</th><th>Email</th><th>Rôle</th><th>Commandes</th><th>Total dépensé</th><th>Inscrit le</th><th>Statut</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td style="color:var(--text-muted)">#<?= $u['id'] ?></td>
                        <td style="font-weight:700"><?= h($u['name']) ?></td>
                        <td style="font-size:13px"><?= h($u['email']) ?></td>
                        <td>
                            <?php if ($u['role'] === 'admin'): ?>
                                <span style="background:var(--primary);color:white;padding:2px 8px;border-radius:6px;font-size:11px;font-weight:800">Admin</span>
                            <?php else: ?>
                                <span style="background:var(--bg);color:var(--text-muted);padding:2px 8px;border-radius:6px;font-size:11px">Client</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight:700"><?= $u['order_count'] ?></td>
                        <td style="color:var(--primary);font-weight:700"><?= formatPrice($u['total_spent']) ?></td>
                        <td style="font-size:12px;color:var(--text-muted)"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                        <td>
                            <span style="color:<?= $u['is_active'] ? 'var(--success)' : 'var(--danger)' ?>;font-weight:700;font-size:12px">
                                <?= $u['is_active'] ? '✅ Actif' : '❌ Bloqué' ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPages>1): ?>
        <div class="pagination">
            <?php for($i=1;$i<=$totalPages;$i++): ?>
                <a href="?page=<?=$i?>&search=<?=urlencode($search)?>" class="page-btn <?=$i===$page?'active':''?>"><?=$i?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
