<?php
/**
 * TEMU CLONE — Page d'installation / Configuration
 * Accès : http://localhost/temu-clone/install.php
 * ⚠️ SUPPRIMER CE FICHIER après l'installation !
 */
require_once __DIR__ . '/includes/config.php';

$message = '';
$error   = '';
$step    = (int)($_GET['step'] ?? 1);

// ---- ÉTAPE 1 : Test connexion BDD ----
if ($step === 1 && isset($_POST['test_db'])) {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";charset=utf8mb4",
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $message = "✅ Connexion MySQL réussie ! Serveur : " . DB_HOST;
        $step = 2;
    } catch (PDOException $e) {
        $error = "❌ Connexion échouée : " . $e->getMessage();
    }
}

// ---- ÉTAPE 2 : Import BDD ----
if ($step === 2 && isset($_POST['import_db'])) {
    try {
        $pdo = getDB();
        $sql = file_get_contents(__DIR__ . '/database.sql');
        // Exécuter les statements un par un
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            fn($s) => !empty($s) && !preg_match('/^\s*--/', $s)
        );
        $count = 0;
        foreach ($statements as $stmt) {
            if (!empty(trim($stmt))) {
                try { $pdo->exec($stmt); $count++; } catch (PDOException $e) { /* ignore duplicates */ }
            }
        }
        $message = "✅ Base de données importée ($count requêtes exécutées) !";
        $step = 3;
    } catch (Exception $e) {
        $error = "❌ Erreur import : " . $e->getMessage();
        $step = 2;
    }
}

// ---- ÉTAPE 3 : Créer compte admin ----
if ($step === 3 && isset($_POST['create_admin'])) {
    $name     = trim($_POST['admin_name'] ?? 'Admin');
    $email    = trim($_POST['admin_email'] ?? '');
    $password = $_POST['admin_password'] ?? '';

    if (empty($email) || empty($password) || strlen($password) < 6) {
        $error = "❌ Email et mot de passe (min. 6 caractères) obligatoires.";
        $step = 3;
    } else {
        try {
            $pdo = getDB();
            $hash = password_hash($password, PASSWORD_BCRYPT);

            // Vérifier si l'email existe déjà
            $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $check->execute([$email]);
            $existing = $check->fetch();

            if ($existing) {
                // Mettre à jour le compte existant
                $pdo->prepare("UPDATE users SET name=?, password=?, role='admin', is_active=1 WHERE email=?")
                    ->execute([$name, $hash, $email]);
                $message = "✅ Compte admin mis à jour pour $email !";
            } else {
                // Créer un nouveau compte admin
                $pdo->prepare("INSERT INTO users (name, email, password, role, is_active) VALUES (?,?,?,'admin',1)")
                    ->execute([$name, $email, $hash]);
                $message = "✅ Compte admin créé : $email !";
            }
            $step = 4;
        } catch (Exception $e) {
            $error = "❌ Erreur : " . $e->getMessage();
            $step = 3;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation — <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Nunito',sans-serif;background:#F5F5F5;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
        .card{background:white;border-radius:16px;padding:40px;width:100%;max-width:560px;box-shadow:0 8px 32px rgba(0,0,0,.12)}
        .logo{text-align:center;font-size:48px;margin-bottom:8px}
        h1{text-align:center;font-size:26px;font-weight:900;color:#FF6000;margin-bottom:4px}
        .subtitle{text-align:center;color:#888;font-size:14px;margin-bottom:28px}
        .steps{display:flex;justify-content:center;gap:0;margin-bottom:32px}
        .step-item{display:flex;align-items:center;gap:0}
        .step-num{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:14px;flex-shrink:0}
        .step-num.done{background:#27AE60;color:white}
        .step-num.active{background:#FF6000;color:white}
        .step-num.pending{background:#E8E8E8;color:#aaa}
        .step-line{width:40px;height:2px;background:#E8E8E8}
        .step-line.done{background:#27AE60}
        .alert{padding:14px 18px;border-radius:10px;margin-bottom:20px;font-weight:700;font-size:14px}
        .alert-success{background:#D4EDDA;color:#155724;border-left:4px solid #27AE60}
        .alert-error{background:#F8D7DA;color:#721C24;border-left:4px solid #E74C3C}
        .alert-info{background:#D1ECF1;color:#0C5460;border-left:4px solid #17A2B8}
        .form-group{margin-bottom:16px}
        label{display:block;font-weight:700;margin-bottom:6px;font-size:14px}
        input{width:100%;padding:12px 16px;border:2px solid #E8E8E8;border-radius:10px;font-family:inherit;font-size:15px;transition:border .2s}
        input:focus{outline:none;border-color:#FF6000}
        .btn{width:100%;background:#FF6000;color:white;border:none;padding:14px;border-radius:30px;font-size:16px;font-weight:900;cursor:pointer;transition:background .2s;font-family:inherit}
        .btn:hover{background:#E04F00}
        .btn-green{background:#27AE60} .btn-green:hover{background:#1E8449}
        .info-box{background:#FFF3ED;border:1px solid #FFD5B8;border-radius:10px;padding:14px 18px;margin-bottom:20px;font-size:13px;color:#8B4513}
        .info-box strong{display:block;margin-bottom:4px;color:#FF6000}
        .success-box{background:#D4EDDA;border:1px solid #C3E6CB;border-radius:10px;padding:20px;text-align:center}
        .success-box .big{font-size:48px;margin-bottom:8px}
        .success-box h3{font-size:20px;font-weight:900;color:#155724;margin-bottom:8px}
        .success-box p{color:#155724;font-size:14px;margin-bottom:16px}
        .links{display:flex;gap:12px;justify-content:center;flex-wrap:wrap}
        .link-btn{padding:10px 20px;border-radius:20px;font-weight:800;font-size:14px;text-decoration:none;display:inline-block}
        .link-btn-primary{background:#FF6000;color:white}
        .link-btn-secondary{background:white;border:2px solid #FF6000;color:#FF6000}
        .warning-delete{background:#FFF3CD;border:1px solid #FFEEBA;border-radius:10px;padding:12px 16px;font-size:13px;color:#856404;margin-top:20px;text-align:center}
    </style>
</head>
<body>
<div class="card">
    <div class="logo">🛍️</div>
    <h1>Installation <?= SITE_NAME ?></h1>
    <p class="subtitle">Assistant de configuration guidé</p>

    <!-- Indicateur d'étapes -->
    <div class="steps">
        <?php
        $stepLabels = ['Test BDD', 'Import', 'Admin', 'Terminé'];
        foreach ($stepLabels as $i => $label):
            $num = $i + 1;
            $class = $num < $step ? 'done' : ($num === $step ? 'active' : 'pending');
        ?>
        <?php if ($i > 0): ?>
            <div class="step-line <?= $num <= $step ? 'done' : '' ?>"></div>
        <?php endif; ?>
        <div class="step-item">
            <div class="step-num <?= $class ?>"><?= $num < $step ? '✓' : $num ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Messages -->
    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($step === 1): ?>
    <!-- ÉTAPE 1 : Vérification connexion -->
    <div class="info-box">
        <strong>📋 Configuration actuelle (includes/config.php)</strong>
        Hôte : <strong><?= DB_HOST ?></strong> &nbsp;|&nbsp;
        BDD : <strong><?= DB_NAME ?></strong> &nbsp;|&nbsp;
        User : <strong><?= DB_USER ?></strong>
    </div>
    <p style="font-size:14px;color:#666;margin-bottom:20px">
        Vérifiez que les paramètres ci-dessus correspondent à votre configuration XAMPP, puis cliquez sur "Tester".
    </p>
    <form method="POST" action="?step=1">
        <button type="submit" name="test_db" class="btn">🔌 Tester la connexion MySQL</button>
    </form>

    <?php elseif ($step === 2): ?>
    <!-- ÉTAPE 2 : Import BDD -->
    <div class="info-box">
        <strong>📦 Import de database.sql</strong>
        Ceci va créer toutes les tables et insérer les données de démonstration dans la base <strong><?= DB_NAME ?></strong>.
    </div>
    <div class="alert alert-info" style="font-size:13px">
        ⚠️ Si la base de données existe déjà, les données existantes seront conservées (les doublons sont ignorés).
    </div>
    <form method="POST" action="?step=2">
        <button type="submit" name="import_db" class="btn">📥 Importer la base de données</button>
    </form>

    <?php elseif ($step === 3): ?>
    <!-- ÉTAPE 3 : Création compte admin -->
    <div class="info-box">
        <strong>👤 Créer votre compte administrateur</strong>
        Ce compte aura accès au dashboard admin. Si l'email existe déjà, le mot de passe sera mis à jour.
    </div>
    <form method="POST" action="?step=3">
        <div class="form-group">
            <label>Nom de l'administrateur</label>
            <input type="text" name="admin_name" value="Admin" required>
        </div>
        <div class="form-group">
            <label>Email admin *</label>
            <input type="email" name="admin_email" placeholder="admin@monsite.com" value="admin@temu-clone.com" required>
        </div>
        <div class="form-group">
            <label>Mot de passe * (min. 6 caractères)</label>
            <input type="password" name="admin_password" placeholder="Choisissez un mot de passe sécurisé" required minlength="6">
        </div>
        <button type="submit" name="create_admin" class="btn btn-green">✅ Créer le compte admin</button>
    </form>

    <?php elseif ($step === 4): ?>
    <!-- ÉTAPE 4 : Succès -->
    <div class="success-box">
        <div class="big">🎉</div>
        <h3>Installation terminée !</h3>
        <p>Votre site <?= SITE_NAME ?> est prêt. Connectez-vous avec les identifiants que vous venez de créer.</p>
        <div class="links">
            <a href="<?= SITE_URL ?>/pages/login.php" class="link-btn link-btn-primary">🔑 Se connecter</a>
            <a href="<?= SITE_URL ?>/admin/index.php" class="link-btn link-btn-secondary">⚙️ Dashboard Admin</a>
            <a href="<?= SITE_URL ?>" class="link-btn link-btn-secondary">🏠 Voir le site</a>
        </div>
    </div>
    <div class="warning-delete">
        ⚠️ <strong>Important :</strong> Supprimez le fichier <code>install.php</code> après l'installation pour des raisons de sécurité !
    </div>
    <?php endif; ?>
</div>
</body>
</html>
