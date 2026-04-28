<?php
require_once __DIR__ . '/../includes/functions.php';
startSession();

if (!isAdmin()) {
    header('Location: ' . SITE_URL . '/admin/login.php');
    exit;
}

$db = getDB();

// Stats globales
$stats = [
    'orders'   => $db->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'revenue'  => $db->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE order_status != 'cancelled'")->fetchColumn(),
    'products' => $db->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn(),
    'users'    => $db->query("SELECT COUNT(*) FROM users WHERE role = 'client'")->fetchColumn(),
];

// Dernières commandes
$recentOrders = $db->query("
    SELECT o.*, u.name AS user_name
    FROM orders o JOIN users u ON u.id = o.user_id
    ORDER BY o.created_at DESC LIMIT 10
")->fetchAll();

// Top produits
$topProducts = $db->query("
    SELECT p.name, p.sold_count, p.price, pi.image_url
    FROM products p
    LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
    ORDER BY p.sold_count DESC LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — <?= SITE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="admin-layout">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="admin-logo">🛍️ <?= SITE_NAME ?><br><small style="font-size:12px;opacity:.6">Administration</small></div>
        <nav class="admin-menu">
            <a href="<?= SITE_URL ?>/admin/index.php" style="color:white;background:rgba(255,255,255,.15)">📊 Dashboard</a>
            <a href="<?= SITE_URL ?>/admin/products.php">📦 Produits</a>
            <a href="<?= SITE_URL ?>/admin/orders.php">🛒 Commandes</a>
            <a href="<?= SITE_URL ?>/admin/payments.php">💳 Paiements</a>
            <a href="<?= SITE_URL ?>/admin/users.php">👥 Utilisateurs</a>
            <a href="<?= SITE_URL ?>/admin/categories.php">🗂️ Catégories</a>
            <a href="<?= SITE_URL ?>/admin/coupons.php">🎟️ Coupons</a>
            <a href="<?= SITE_URL ?>/admin/banners.php">🖼️ Bannières</a>
            <a href="<?= SITE_URL ?>" style="margin-top:20px;border-top:1px solid rgba(255,255,255,.1);padding-top:16px">🏠 Voir le site</a>
            <a href="<?= SITE_URL ?>/api/auth.php?action=logout" style="color:#FF6B6B">🚪 Déconnexion</a>
        </nav>
    </aside>

    <!-- Contenu principal -->
    <main class="admin-main">
        <?php if (isset($_GET['welcome'])): ?>
        <div style="background:linear-gradient(135deg,#FF6000,#FF9900);border-radius:16px;padding:20px 28px;margin-bottom:24px;color:white;display:flex;align-items:center;gap:16px">
            <span style="font-size:40px">🎉</span>
            <div>
                <div style="font-size:20px;font-weight:900">Bienvenue, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?> !</div>
                <div style="font-size:14px;opacity:.88">Votre compte administrateur a été créé avec succès. Vous êtes maintenant connecté.</div>
            </div>
            <a href="?" style="margin-left:auto;background:rgba(255,255,255,.2);color:white;padding:8px 16px;border-radius:20px;font-weight:700;font-size:13px;text-decoration:none">✕ Fermer</a>
        </div>
        <?php endif; ?>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
            <h1 style="font-size:24px;font-weight:900">📊 Tableau de bord</h1>
            <span style="color:var(--text-muted);font-size:14px"><?= date('d/m/Y H:i') ?></span>
        </div>

        <!-- Stats cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🛒</div>
                <div class="stat-val"><?= number_format($stats['orders']) ?></div>
                <div class="stat-label">Commandes totales</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-val"><?= formatPrice($stats['revenue']) ?></div>
                <div class="stat-label">Chiffre d'affaires</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-val"><?= number_format($stats['products']) ?></div>
                <div class="stat-label">Produits actifs</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-val"><?= number_format($stats['users']) ?></div>
                <div class="stat-label">Clients inscrits</div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 340px;gap:24px">
            <!-- Dernières commandes -->
            <div class="bg-white rounded p-3">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
                    <h3 style="font-weight:800">🛒 Dernières commandes</h3>
                    <a href="<?= SITE_URL ?>/admin/orders.php" style="color:var(--primary);font-size:13px;font-weight:700">Voir tout →</a>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>N°</th><th>Client</th><th>Total</th><th>Statut</th><th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $o):
                            $statusColors = ['pending'=>'#856404','confirmed'=>'#0C5460','shipped'=>'#155724','delivered'=>'#155724','cancelled'=>'#721C24'];
                            $color = $statusColors[$o['order_status']] ?? '#333';
                        ?>
                        <tr>
                            <td><strong><?= h($o['order_number']) ?></strong></td>
                            <td><?= h($o['user_name']) ?></td>
                            <td style="color:var(--primary);font-weight:700"><?= formatPrice($o['total']) ?></td>
                            <td><span style="color:<?= $color ?>;font-weight:700;font-size:12px"><?= h($o['order_status']) ?></span></td>
                            <td style="color:var(--text-muted);font-size:12px"><?= date('d/m/Y', strtotime($o['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Top produits -->
            <div class="bg-white rounded p-3">
                <h3 style="font-weight:800;margin-bottom:16px">⭐ Top Produits</h3>
                <?php foreach ($topProducts as $p): ?>
                <div style="display:flex;gap:10px;align-items:center;padding:10px 0;border-bottom:1px solid var(--border)">
                    <img src="<?= h($p['image_url'] ?: 'https://picsum.photos/seed/'.rand(1,99).'/60/60') ?>"
                         style="width:48px;height:48px;border-radius:8px;object-fit:cover" alt="">
                    <div style="flex:1;min-width:0">
                        <div style="font-weight:700;font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= h($p['name']) ?></div>
                        <div style="font-size:12px;color:var(--text-muted)"><?= number_format($p['sold_count']) ?> vendus</div>
                    </div>
                    <div style="color:var(--primary);font-weight:800;font-size:14px;white-space:nowrap"><?= formatPrice($p['price']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Liens rapides -->
        <div style="margin-top:24px;display:grid;grid-template-columns:repeat(4,1fr);gap:12px">
            <a href="<?= SITE_URL ?>/admin/products.php?action=add" style="background:var(--primary);color:white;border-radius:12px;padding:16px;text-align:center;font-weight:800;font-size:14px">
                ➕ Ajouter un produit
            </a>
            <a href="<?= SITE_URL ?>/admin/orders.php" style="background:var(--secondary);color:white;border-radius:12px;padding:16px;text-align:center;font-weight:800;font-size:14px">
                📋 Gérer commandes
            </a>
            <a href="<?= SITE_URL ?>/admin/coupons.php?action=add" style="background:var(--success);color:white;border-radius:12px;padding:16px;text-align:center;font-weight:800;font-size:14px">
                🎟️ Créer coupon
            </a>
            <a href="<?= SITE_URL ?>/admin/banners.php" style="background:var(--accent);color:white;border-radius:12px;padding:16px;text-align:center;font-weight:800;font-size:14px">
                🖼️ Gérer bannières
            </a>
        </div>
    </main>
</div>
</body>
</html>
