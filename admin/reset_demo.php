<?php
/**
 * Reset Admin — Réinitialise le mot de passe du compte admin de démo
 * Accès : http://localhost/temu-clone/admin/reset_demo.php
 * ⚠️ SUPPRIMER après utilisation !
 */
require_once __DIR__ . '/../includes/config.php';

$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDB();
        // Recréer un hash bcrypt PHP natif correct pour "password"
        $hash = password_hash('password', PASSWORD_BCRYPT);
        $pdo->prepare("UPDATE users SET password = ? WHERE email = 'admin@temu-clone.com'")->execute([$hash]);
        $affected = $pdo->rowCount();
        if ($affected > 0) {
            $message = "✅ Mot de passe réinitialisé ! Connectez-vous avec admin@temu-clone.com / password";
        } else {
            $error = "❌ Compte admin@temu-clone.com introuvable. Utilisez admin/register.php.";
        }
    } catch (Exception $e) {
        $error = "❌ Erreur : " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reset Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Nunito',sans-serif;background:#0A0A18;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
        .card{background:#14142A;border-radius:20px;padding:40px;width:100%;max-width:460px;border:1px solid rgba(255,255,255,.08);text-align:center}
        h1{color:white;font-size:22px;font-weight:900;margin-bottom:8px}
        p{color:rgba(255,255,255,.5);font-size:14px;margin-bottom:24px;line-height:1.6}
        .info{background:rgba(255,153,0,.1);border:1px solid rgba(255,153,0,.25);border-radius:12px;padding:16px;margin-bottom:24px;text-align:left}
        .info strong{color:#FF9900;display:block;margin-bottom:6px}
        .info span{color:rgba(255,255,255,.6);font-size:13px;line-height:1.7}
        .btn{width:100%;background:linear-gradient(135deg,#FF6000,#FF9900);color:white;border:none;padding:14px;border-radius:30px;font-family:'Nunito',sans-serif;font-size:15px;font-weight:900;cursor:pointer}
        .alert{border-radius:12px;padding:14px 16px;margin-bottom:20px;font-size:14px;font-weight:700}
        .success{background:rgba(39,174,96,.15);border:1px solid rgba(39,174,96,.3);color:#5CFF96}
        .danger{background:rgba(231,76,60,.15);border:1px solid rgba(231,76,60,.3);color:#FF6B6B}
        .links{display:flex;gap:10px;justify-content:center;margin-top:20px;flex-wrap:wrap}
        .link{padding:10px 20px;border-radius:20px;font-size:13px;font-weight:700;text-decoration:none}
        .link-orange{background:#FF6000;color:white}
        .link-ghost{border:1px solid rgba(255,255,255,.15);color:rgba(255,255,255,.6)}
        .warn-delete{background:rgba(231,76,60,.1);border:1px solid rgba(231,76,60,.2);border-radius:10px;padding:12px;margin-top:20px;font-size:12px;color:rgba(231,76,60,.8)}
    </style>
</head>
<body>
<div class="card">
    <div style="font-size:48px;margin-bottom:12px">🔑</div>
    <h1>Réinitialisation Admin</h1>
    <p>Recrée un hash bcrypt PHP natif correct pour le compte de démo.</p>

    <?php if ($message): ?>
        <div class="alert success"><?= htmlspecialchars($message) ?></div>
        <div class="links">
            <a href="<?= SITE_URL ?>/admin/login.php" class="link link-orange">🔐 Se connecter</a>
            <a href="<?= SITE_URL ?>/admin/register.php" class="link link-ghost">📝 Créer un admin</a>
        </div>
    <?php elseif ($error): ?>
        <div class="alert danger"><?= htmlspecialchars($error) ?></div>
        <a href="<?= SITE_URL ?>/admin/register.php" class="link link-orange" style="display:inline-block;margin-top:4px">📝 Créer un nouveau compte admin</a>
    <?php else: ?>
        <div class="info">
            <strong>⚙️ Ce script va :</strong>
            <span>
                • Trouver le compte <code>admin@temu-clone.com</code><br>
                • Remplacer son mot de passe par un hash bcrypt PHP valide<br>
                • Mot de passe résultant : <strong>password</strong>
            </span>
        </div>
        <form method="POST">
            <button type="submit" class="btn">🔄 Réinitialiser le mot de passe admin</button>
        </form>
    <?php endif; ?>

    <div class="warn-delete">
        ⚠️ Supprimez ce fichier <code>reset_demo.php</code> après utilisation !
    </div>
</div>
</body>
</html>
