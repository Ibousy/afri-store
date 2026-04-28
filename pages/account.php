<?php
require_once __DIR__ . '/../includes/functions.php';
startSession();
requireLogin(); // Redirection si non connecté — avant tout HTML

$db   = getDB();
$user = getCurrentUser();

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name  = sanitize($_POST['name'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        if (empty($name)) {
            $errors[] = 'Le nom est obligatoire.';
        } else {
            $db->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?")->execute([$name, $phone, $user['id']]);
            $success = 'Profil mis à jour !';
            $user['name'] = $name;
        }
    }

    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if (!password_verify($current, $user['password'])) {
            $errors[] = 'Mot de passe actuel incorrect.';
        } elseif (strlen($new) < 6) {
            $errors[] = 'Nouveau mot de passe trop court (6 caractères min).';
        } elseif ($new !== $confirm) {
            $errors[] = 'Les mots de passe ne correspondent pas.';
        } else {
            $db->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            $success = 'Mot de passe modifié !';
        }
    }
}

// Statistiques
$orderCount  = $db->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
$orderCount->execute([$user['id']]); $orderCount = $orderCount->fetchColumn();

$wishCount = $db->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
$wishCount->execute([$user['id']]); $wishCount = $wishCount->fetchColumn();

$totalSpent = $db->prepare("SELECT COALESCE(SUM(total),0) FROM orders WHERE user_id = ? AND order_status != 'cancelled'");
$totalSpent->execute([$user['id']]); $totalSpent = $totalSpent->fetchColumn();

// Header HTML — après toute la logique PHP
$pageTitle = 'Mon Compte';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="breadcrumb">
        <a href="<?= SITE_URL ?>">Accueil</a> <span>›</span>
        <span class="current">Mon Compte</span>
    </div>

    <div class="account-layout">
        <!-- Sidebar -->
        <aside class="account-sidebar">
            <div class="account-avatar">👤</div>
            <div class="account-name"><?= h($user['name']) ?></div>
            <div class="account-email"><?= h($user['email']) ?></div>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-bottom:20px;text-align:center">
                <div style="background:var(--primary-light);border-radius:8px;padding:10px">
                    <div style="font-size:20px;font-weight:900;color:var(--primary)"><?= $orderCount ?></div>
                    <div style="font-size:11px;color:var(--text-muted)">Commandes</div>
                </div>
                <div style="background:#FFF0F3;border-radius:8px;padding:10px">
                    <div style="font-size:20px;font-weight:900;color:var(--accent)"><?= $wishCount ?></div>
                    <div style="font-size:11px;color:var(--text-muted)">Favoris</div>
                </div>
                <div style="background:#F0FFF4;border-radius:8px;padding:10px">
                    <div style="font-size:16px;font-weight:900;color:var(--success)"><?= formatPrice($totalSpent) ?></div>
                    <div style="font-size:11px;color:var(--text-muted)">Dépensé</div>
                </div>
            </div>

            <nav class="account-menu">
                <a href="<?= SITE_URL ?>/pages/account.php" class="active">👤 Mon profil</a>
                <a href="<?= SITE_URL ?>/pages/orders.php">📦 Mes commandes</a>
                <a href="<?= SITE_URL ?>/pages/wishlist.php">❤️ Mes favoris</a>
                <a href="<?= SITE_URL ?>/api/auth.php?action=logout" style="color:var(--danger)">🚪 Déconnexion</a>
            </nav>
        </aside>

        <!-- Contenu -->
        <div class="account-content">
            <?php foreach ($errors as $err): ?>
                <div class="flash-message flash-error" style="margin-bottom:16px"><?= h($err) ?></div>
            <?php endforeach; ?>
            <?php if ($success): ?>
                <div class="flash-message flash-success" style="margin-bottom:16px">✅ <?= h($success) ?></div>
            <?php endif; ?>

            <!-- Modifier profil -->
            <h3 style="font-size:18px;font-weight:800;margin-bottom:16px">✏️ Modifier mon profil</h3>
            <form method="POST" style="max-width:500px;margin-bottom:32px">
                <input type="hidden" name="action" value="update_profile">
                <?= csrfField() ?>
                <div class="form-group">
                    <label>Nom complet</label>
                    <input type="text" name="name" class="form-control" value="<?= h($user['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" class="form-control" value="<?= h($user['email']) ?>" disabled style="background:var(--bg)">
                </div>
                <div class="form-group">
                    <label>Téléphone</label>
                    <input type="tel" name="phone" class="form-control" value="<?= h($user['phone'] ?? '') ?>" placeholder="06 00 00 00 00">
                </div>
                <button type="submit" class="btn-primary" style="padding:12px 28px;border-radius:30px">Sauvegarder</button>
            </form>

            <hr style="border:none;border-top:1px solid var(--border);margin:24px 0">

            <!-- Changer mot de passe -->
            <h3 style="font-size:18px;font-weight:800;margin-bottom:16px">🔒 Changer le mot de passe</h3>
            <form method="POST" style="max-width:500px">
                <input type="hidden" name="action" value="change_password">
                <?= csrfField() ?>
                <div class="form-group">
                    <label>Mot de passe actuel</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Nouveau mot de passe</label>
                    <input type="password" name="new_password" class="form-control" required minlength="6">
                </div>
                <div class="form-group">
                    <label>Confirmer le nouveau mot de passe</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <button type="submit" class="btn-primary" style="padding:12px 28px;border-radius:30px">Changer le mot de passe</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
