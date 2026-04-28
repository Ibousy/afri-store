<?php
require_once __DIR__ . '/../includes/functions.php';
startSession();
if (!isAdmin()) { header('Location: ' . SITE_URL . '/admin/login.php'); exit; }

$db = getDB();
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['form_action'] ?? '';

    if ($action === 'add') {
        $name = sanitize($_POST['name'] ?? '');
        $slug = sanitize($_POST['slug'] ?? '');
        $icon = sanitize($_POST['icon'] ?? '🛒');

        if (empty($name) || empty($slug)) {
            $errors[] = 'Nom et slug obligatoires.';
        } else {
            $db->prepare("INSERT INTO categories (name, slug, icon) VALUES (?,?,?)")->execute([$name, $slug, $icon]);
            $success = "Catégorie '$name' créée !";
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['delete_id'] ?? 0);
        $db->prepare("UPDATE categories SET is_active = 0 WHERE id = ?")->execute([$id]);
        $success = "Catégorie supprimée.";
    }
}

$cats = $db->query("
    SELECT c.*, COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id AND p.is_active = 1
    WHERE c.is_active = 1
    GROUP BY c.id
    ORDER BY c.sort_order ASC, c.name ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><title>Catégories — Admin</title>
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
            <a href="<?= SITE_URL ?>/admin/categories.php" style="color:white;background:rgba(255,255,255,.15)">🗂️ Catégories</a>
            <a href="<?= SITE_URL ?>/admin/coupons.php">🎟️ Coupons</a>
            <a href="<?= SITE_URL ?>">🏠 Voir le site</a>
            <a href="<?= SITE_URL ?>/api/auth.php?action=logout" style="color:#FF6B6B">🚪 Déconnexion</a>
        </nav>
    </aside>
    <main class="admin-main">
        <?php if ($success): ?><div class="flash-message flash-success">✅ <?= h($success) ?></div><?php endif; ?>
        <?php foreach ($errors as $e): ?><div class="flash-message flash-error">❌ <?= h($e) ?></div><?php endforeach; ?>

        <h1 style="font-size:22px;font-weight:900;margin-bottom:20px">🗂️ Catégories</h1>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
            <!-- Formulaire -->
            <div class="bg-white rounded p-3">
                <h3 style="font-weight:800;margin-bottom:16px">➕ Nouvelle catégorie</h3>
                <form method="POST">
                    <input type="hidden" name="form_action" value="add">
                    <div class="form-group">
                        <label>Nom *</label>
                        <input type="text" name="name" class="form-control" required
                               oninput="this.form.slug.value=this.value.toLowerCase().replace(/[^a-z0-9]+/g,'-')">
                    </div>
                    <div class="form-group">
                        <label>Slug *</label>
                        <input type="text" name="slug" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Icône (emoji)</label>
                        <input type="text" name="icon" class="form-control" value="🛒" maxlength="5">
                    </div>
                    <button type="submit" class="btn-primary" style="padding:12px 24px;border-radius:30px">Créer</button>
                </form>
            </div>

            <!-- Liste -->
            <div class="bg-white rounded" style="overflow:auto;max-height:500px">
                <table class="data-table">
                    <thead><tr><th>Icône</th><th>Nom</th><th>Slug</th><th>Produits</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($cats as $c): ?>
                        <tr>
                            <td style="font-size:22px"><?= h($c['icon']) ?></td>
                            <td style="font-weight:700"><?= h($c['name']) ?></td>
                            <td style="font-size:12px;font-family:monospace;color:var(--text-muted)"><?= h($c['slug']) ?></td>
                            <td><?= $c['product_count'] ?></td>
                            <td>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="form_action" value="delete">
                                    <input type="hidden" name="delete_id" value="<?= $c['id'] ?>">
                                    <button type="submit" onclick="return confirm('Supprimer ?')" style="color:var(--danger);background:none;font-size:13px">🗑️</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
</body>
</html>
