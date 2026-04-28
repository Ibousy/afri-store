<?php
/**
 * Page de connexion dédiée à l'administration
 * Accès : http://localhost/temu-clone/admin/login.php
 */
require_once __DIR__ . '/../includes/functions.php';
startSession();

// Déjà connecté en tant qu'admin → redirection directe
if (isAdmin()) {
    header('Location: ' . SITE_URL . '/admin/index.php');
    exit;
}

// Déjà connecté mais pas admin → déconnexion + message
if (isLoggedIn()) {
    session_destroy();
    session_start();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin' AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = 'admin';
            header('Location: ' . SITE_URL . '/admin/index.php');
            exit;
        } else {
            // Diagnostic : vérifier si l'email existe mais n'est pas admin
            $check = $db->prepare("SELECT role FROM users WHERE email = ?");
            $check->execute([$email]);
            $found = $check->fetch();
            if ($found && $found['role'] !== 'admin') {
                $error = 'Ce compte n\'a pas les droits administrateur.';
            } else {
                $error = 'Email ou mot de passe incorrect.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Admin — <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Nunito',sans-serif;background:#0F0F1E;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
        .wrap{width:100%;max-width:420px}
        .logo-wrap{text-align:center;margin-bottom:32px}
        .logo-icon{font-size:52px}
        .logo-text{font-size:28px;font-weight:900;color:#FF6000;display:block;margin-top:4px}
        .logo-sub{font-size:13px;color:rgba(255,255,255,.4);margin-top:2px}
        .card{background:#1A1A2E;border-radius:20px;padding:36px;border:1px solid rgba(255,255,255,.08)}
        h2{font-size:22px;font-weight:900;color:white;text-align:center;margin-bottom:6px}
        .sub{text-align:center;color:rgba(255,255,255,.45);font-size:13px;margin-bottom:28px}
        .error{background:rgba(231,76,60,.15);border:1px solid rgba(231,76,60,.4);color:#FF6B6B;border-radius:10px;padding:12px 16px;font-size:14px;font-weight:700;margin-bottom:20px;display:flex;align-items:center;gap:8px}
        .form-group{margin-bottom:18px}
        label{display:block;font-weight:700;font-size:13px;color:rgba(255,255,255,.6);margin-bottom:8px;text-transform:uppercase;letter-spacing:.5px}
        input{width:100%;padding:13px 16px;background:rgba(255,255,255,.06);border:1.5px solid rgba(255,255,255,.1);border-radius:12px;color:white;font-family:inherit;font-size:15px;transition:border .2s}
        input:focus{outline:none;border-color:#FF6000;background:rgba(255,96,0,.08)}
        input::placeholder{color:rgba(255,255,255,.25)}
        .btn{width:100%;background:linear-gradient(135deg,#FF6000,#FF9900);color:white;border:none;padding:15px;border-radius:30px;font-size:16px;font-weight:900;cursor:pointer;font-family:inherit;transition:transform .2s,box-shadow .2s;margin-top:8px}
        .btn:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(255,96,0,.35)}
        .btn:active{transform:translateY(0)}
        .back-link{text-align:center;margin-top:20px}
        .back-link a{color:rgba(255,255,255,.4);font-size:13px;text-decoration:none;transition:color .2s}
        .back-link a:hover{color:rgba(255,255,255,.8)}
        .hint{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);border-radius:10px;padding:12px 16px;margin-top:20px;font-size:12px;color:rgba(255,255,255,.35);text-align:center}
        .hint strong{color:rgba(255,255,255,.55)}
        .setup-link{text-align:center;margin-top:12px}
        .setup-link a{color:#FF6000;font-size:12px;font-weight:700}
    </style>
</head>
<body>
<div class="wrap">
    <div class="logo-wrap">
        <div class="logo-icon">🛍️</div>
        <span class="logo-text"><?= SITE_NAME ?></span>
        <div class="logo-sub">Panneau d'administration</div>
    </div>

    <div class="card">
        <h2>🔐 Connexion Admin</h2>
        <p class="sub">Accès réservé aux administrateurs</p>

        <?php if ($error): ?>
        <div class="error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Email administrateur</label>
                <input type="email" name="email" placeholder="admin@exemple.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
            </div>
            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn">Se connecter au dashboard →</button>
        </form>

        <div class="hint">
            <strong>Comptes de démo :</strong><br>
            admin@temu-clone.com / <strong>password</strong>
        </div>
        <div style="text-align:center;margin-top:16px">
            <a href="<?= SITE_URL ?>/admin/register.php"
               style="display:inline-block;background:linear-gradient(135deg,#FF6000,#FF9900);color:white;padding:11px 24px;border-radius:30px;font-weight:900;font-size:14px;text-decoration:none;">
                &#x1F680; Créer un compte Admin
            </a>
        </div>
    </div>

    <div class="back-link">
        <a href="<?= SITE_URL ?>">← Retour au site</a>
    </div>
    <div class="setup-link">
        <a href="<?= SITE_URL ?>/install.php">⚙️ Aller à l'installation</a>
    </div>
</div>
</body>
</html>
