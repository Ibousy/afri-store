<?php
require_once __DIR__ . '/../includes/functions.php';
startSession();
if (!isAdmin()) { header('Location: ' . SITE_URL . '/admin/login.php'); exit; }

$db = getDB();
$action = $_GET['action'] ?? 'list';
$errors = [];
$success = '';

if ($action === 'delete' && isset($_GET['id'])) {
    $db->prepare("DELETE FROM coupons WHERE id = ?")->execute([(int)$_GET['id']]);
    header('Location: ' . SITE_URL . '/admin/coupons.php'); exit;
}

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $code     = strtoupper(sanitize($_POST['code'] ?? ''));
    $type     = $_POST['type'] === 'fixed' ? 'fixed' : 'percent';
    $value    = (float)($_POST['value'] ?? 0);
    $minOrder = (float)($_POST['min_order'] ?? 0);
    $maxUses  = !empty($_POST['max_uses']) ? (int)$_POST['max_uses'] : null;
    $expires  = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;

    if (empty($code) || $value <= 0) {
        $errors[] = 'Code et valeur sont obligatoires.';
    } else {
        $db->prepare("INSERT INTO coupons (code, type, value, min_order, max_uses, expires_at) VALUES (?,?,?,?,?,?)")
           ->execute([$code, $type, $value, $minOrder, $maxUses, $expires]);
        $success = "Coupon $code créé !";
        $action = 'list';
    }
}

$coupons = $db->query("SELECT * FROM coupons ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><title>Coupons — Admin</title>
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
            <a href="<?= SITE_URL ?>/admin/users.php">👥 Utilisateurs</a>
            <a href="<?= SITE_URL ?>/admin/categories.php">🗂️ Catégories</a>
            <a href="<?= SITE_URL ?>/admin/coupons.php" style="color:white;background:rgba(255,255,255,.15)">🎟️ Coupons</a>
            <a href="<?= SITE_URL ?>">🏠 Voir le site</a>
            <a href="<?= SITE_URL ?>/api/auth.php?action=logout" style="color:#FF6B6B">🚪 Déconnexion</a>
        </nav>
    </aside>
    <main class="admin-main">
        <?php if ($success): ?><div class="flash-message flash-success">✅ <?= h($success) ?></div><?php endif; ?>
        <?php foreach ($errors as $e): ?><div class="flash-message flash-error">❌ <?= h($e) ?></div><?php endforeach; ?>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
            <h1 style="font-size:22px;font-weight:900">🎟️ Coupons de réduction</h1>
            <button onclick="document.getElementById('addForm').style.display=document.getElementById('addForm').style.display==='none'?'block':'none'"
                    class="btn-primary" style="padding:10px 20px;border-radius:20px">➕ Nouveau coupon</button>
        </div>

        <!-- Formulaire ajout -->
        <div id="addForm" style="display:<?= $action==='add'?'block':'none' ?>">
            <form method="POST" action="?action=add" class="bg-white rounded p-3" style="margin-bottom:24px">
                <h3 style="font-weight:800;margin-bottom:16px">Créer un coupon</h3>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px">
                    <div class="form-group">
                        <label>Code *</label>
                        <input type="text" name="code" class="form-control" placeholder="EX: PROMO20" style="text-transform:uppercase" required>
                    </div>
                    <div class="form-group">
                        <label>Type</label>
                        <select name="type" class="form-control">
                            <option value="percent">Pourcentage (%)</option>
                            <option value="fixed">Montant fixe (€)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Valeur *</label>
                        <input type="number" name="value" class="form-control" step="0.01" min="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Commande min (€)</label>
                        <input type="number" name="min_order" class="form-control" step="0.01" min="0" value="0">
                    </div>
                    <div class="form-group">
                        <label>Utilisations max</label>
                        <input type="number" name="max_uses" class="form-control" min="1" placeholder="Illimité">
                    </div>
                    <div class="form-group">
                        <label>Expire le</label>
                        <input type="datetime-local" name="expires_at" class="form-control">
                    </div>
                </div>
                <button type="submit" class="btn-primary" style="padding:12px 28px;border-radius:30px">Créer le coupon</button>
            </form>
        </div>

        <!-- Liste des coupons -->
        <div class="bg-white rounded" style="overflow:auto">
            <table class="data-table">
                <thead>
                    <tr><th>Code</th><th>Type</th><th>Valeur</th><th>Min commande</th><th>Utilisé / Max</th><th>Expire</th><th>Statut</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($coupons as $c): ?>
                    <tr>
                        <td><strong style="font-family:monospace;font-size:15px;color:var(--primary)"><?= h($c['code']) ?></strong></td>
                        <td><?= $c['type'] === 'percent' ? 'Pourcentage' : 'Fixe' ?></td>
                        <td style="font-weight:800"><?= $c['type'] === 'percent' ? $c['value'].'%' : formatPrice($c['value']) ?></td>
                        <td><?= formatPrice($c['min_order']) ?></td>
                        <td><?= $c['used_count'] ?> / <?= $c['max_uses'] ?? '∞' ?></td>
                        <td style="font-size:13px"><?= $c['expires_at'] ? date('d/m/Y', strtotime($c['expires_at'])) : '—' ?></td>
                        <td>
                            <span style="color:<?= $c['is_active'] ? 'var(--success)' : 'var(--danger)' ?>;font-weight:700;font-size:12px">
                                <?= $c['is_active'] ? '✅ Actif' : '❌ Inactif' ?>
                            </span>
                        </td>
                        <td>
                            <a href="?action=delete&id=<?= $c['id'] ?>" onclick="return confirm('Supprimer ce coupon ?')" style="color:var(--danger);font-size:13px">🗑️ Supprimer</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>
</html>
