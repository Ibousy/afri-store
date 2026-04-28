<?php
/**
 * Inscription Administrateur
 * Accès : http://localhost/temu-clone/admin/register.php
 * ⚠️ Sécurisez ou supprimez cette page après création du premier admin !
 */
require_once __DIR__ . '/../includes/functions.php';
startSession();

// Si déjà connecté en tant qu'admin → dashboard directement
if (isAdmin()) {
    header('Location: ' . SITE_URL . '/admin/index.php');
    exit;
}

$db = getDB();
$errors  = [];
$success = '';

// Vérifier si un admin existe déjà (sécurité)
$adminCount = $db->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';
    $secret   = $_POST['secret_key'] ?? '';

    // Clé secrète d'installation (changez-la dans config ou ici)
    $validSecret = defined('ADMIN_SECRET') ? ADMIN_SECRET : 'temu-admin-2024';

    // Validations
    if (empty($name))                          $errors[] = 'Le nom est obligatoire.';
    if (empty($email))                         $errors[] = "L'email est obligatoire.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalide.';
    if (strlen($password) < 6)                 $errors[] = 'Mot de passe : minimum 6 caractères.';
    if ($password !== $confirm)                $errors[] = 'Les mots de passe ne correspondent pas.';
    if ($secret !== $validSecret)              $errors[] = 'Clé secrète incorrecte.';

    if (empty($errors)) {
        // Email déjà utilisé ?
        $check = $db->prepare("SELECT id, role FROM users WHERE email = ?");
        $check->execute([$email]);
        $existing = $check->fetch();

        if ($existing) {
            // Mettre à jour en admin si compte existant
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $db->prepare("UPDATE users SET name=?, password=?, role='admin', is_active=1 WHERE email=?")
               ->execute([$name, $hash, $email]);
            $userId = $existing['id'];
        } else {
            // Créer nouveau compte admin
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $db->prepare("INSERT INTO users (name, email, password, role, is_active) VALUES (?,?,?,'admin',1)")
               ->execute([$name, $email, $hash]);
            $userId = (int)$db->lastInsertId();
        }

        // Connexion automatique → session
        $_SESSION['user_id']   = $userId;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_role'] = 'admin';

        // Redirection directe vers le dashboard
        header('Location: ' . SITE_URL . '/admin/index.php?welcome=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un compte Admin — <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Nunito', sans-serif;
            background: #0A0A18;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        /* Fond animé */
        body::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 600px 400px at 20% 50%, rgba(255,96,0,.12) 0%, transparent 70%),
                radial-gradient(ellipse 400px 600px at 80% 20%, rgba(255,153,0,.08) 0%, transparent 70%);
            pointer-events: none;
        }

        .page-wrap {
            display: grid;
            grid-template-columns: 1fr 480px;
            gap: 60px;
            width: 100%;
            max-width: 960px;
            align-items: center;
        }

        /* Colonne gauche — branding */
        .branding { color: white; }
        .brand-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 32px;
        }
        .brand-logo-icon { font-size: 42px; }
        .brand-logo-name { font-size: 28px; font-weight: 900; color: #FF6000; }
        .brand-logo-sub  { font-size: 13px; color: rgba(255,255,255,.4); display: block; }

        .brand-title { font-size: 36px; font-weight: 900; line-height: 1.25; margin-bottom: 16px; }
        .brand-title span { color: #FF6000; }
        .brand-desc { font-size: 15px; color: rgba(255,255,255,.5); line-height: 1.8; margin-bottom: 32px; }

        .feature-list { display: flex; flex-direction: column; gap: 14px; }
        .feature-item { display: flex; align-items: center; gap: 12px; }
        .feature-icon {
            width: 36px; height: 36px; border-radius: 10px;
            background: rgba(255,96,0,.15);
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; flex-shrink: 0;
        }
        .feature-text { font-size: 14px; color: rgba(255,255,255,.65); }
        .feature-text strong { color: white; display: block; font-size: 14px; }

        /* Formulaire */
        .form-card {
            background: #14142A;
            border-radius: 24px;
            padding: 40px;
            border: 1px solid rgba(255,255,255,.07);
            position: relative;
            overflow: hidden;
        }
        .form-card::before {
            content: '';
            position: absolute;
            top: -1px; left: 0; right: 0; height: 3px;
            background: linear-gradient(90deg, #FF6000, #FF9900);
            border-radius: 24px 24px 0 0;
        }

        .form-header { margin-bottom: 28px; }
        .form-header h2 { font-size: 22px; font-weight: 900; color: white; margin-bottom: 4px; }
        .form-header p  { font-size: 13px; color: rgba(255,255,255,.4); }

        /* Warning si admin existe déjà */
        .admin-warning {
            background: rgba(231,76,60,.12);
            border: 1px solid rgba(231,76,60,.3);
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #FF8080;
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }
        .admin-warning .icon { font-size: 18px; flex-shrink: 0; }

        /* Erreurs */
        .errors {
            background: rgba(231,76,60,.1);
            border: 1px solid rgba(231,76,60,.25);
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 20px;
        }
        .errors p { font-size: 13px; color: #FF6B6B; display: flex; gap: 8px; align-items: center; }
        .errors p + p { margin-top: 6px; }

        /* Champs */
        .form-group { margin-bottom: 16px; }
        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: rgba(255,255,255,.5);
            text-transform: uppercase;
            letter-spacing: .6px;
            margin-bottom: 8px;
        }
        .form-group label span { color: #FF6000; }

        .form-input {
            width: 100%;
            padding: 13px 16px;
            background: rgba(255,255,255,.05);
            border: 1.5px solid rgba(255,255,255,.08);
            border-radius: 12px;
            color: white;
            font-family: 'Nunito', sans-serif;
            font-size: 14px;
            transition: border-color .2s, background .2s;
        }
        .form-input:focus {
            outline: none;
            border-color: #FF6000;
            background: rgba(255,96,0,.06);
        }
        .form-input::placeholder { color: rgba(255,255,255,.2); }

        /* Champ clé secrète */
        .secret-field {
            background: rgba(255,153,0,.06);
            border-color: rgba(255,153,0,.2);
        }
        .secret-field:focus { border-color: #FF9900; background: rgba(255,153,0,.1); }

        /* Indicateur force mot de passe */
        .password-strength { margin-top: 8px; }
        .strength-bar {
            height: 4px;
            border-radius: 4px;
            background: rgba(255,255,255,.08);
            overflow: hidden;
            margin-bottom: 4px;
        }
        .strength-fill {
            height: 100%;
            border-radius: 4px;
            transition: width .3s, background .3s;
            width: 0%;
        }
        .strength-label { font-size: 11px; color: rgba(255,255,255,.3); }

        /* Bouton */
        .submit-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #FF6000 0%, #FF9900 100%);
            color: white;
            border: none;
            border-radius: 30px;
            font-family: 'Nunito', sans-serif;
            font-size: 16px;
            font-weight: 900;
            cursor: pointer;
            margin-top: 8px;
            transition: transform .2s, box-shadow .2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(255,96,0,.35);
        }
        .submit-btn:active { transform: translateY(0); }

        /* Footer du form */
        .form-footer { text-align: center; margin-top: 20px; }
        .form-footer a {
            color: rgba(255,255,255,.35);
            font-size: 13px;
            text-decoration: none;
            transition: color .2s;
        }
        .form-footer a:hover { color: rgba(255,255,255,.75); }
        .form-footer a + a { margin-left: 16px; }

        /* Séparateur */
        .separator {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 20px 0;
        }
        .separator::before, .separator::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255,255,255,.07);
        }
        .separator span { font-size: 11px; color: rgba(255,255,255,.2); white-space: nowrap; }

        /* Already admin note */
        .already-link {
            text-align: center;
            font-size: 13px;
            color: rgba(255,255,255,.35);
        }
        .already-link a { color: #FF6000; font-weight: 700; }

        /* Responsive */
        @media(max-width: 800px) {
            .page-wrap { grid-template-columns: 1fr; }
            .branding { display: none; }
        }
    </style>
</head>
<body>
<div class="page-wrap">

    <!-- BRANDING GAUCHE -->
    <div class="branding">
        <div class="brand-logo">
            <span class="brand-logo-icon">🛍️</span>
            <div>
                <div class="brand-logo-name"><?= SITE_NAME ?></div>
                <span class="brand-logo-sub">Panneau d'administration</span>
            </div>
        </div>

        <h1 class="brand-title">
            Gérez votre boutique<br>
            <span>comme un pro</span>
        </h1>
        <p class="brand-desc">
            Créez votre compte administrateur et accédez immédiatement à votre tableau de bord pour gérer produits, commandes et clients.
        </p>

        <div class="feature-list">
            <div class="feature-item">
                <div class="feature-icon">📊</div>
                <div class="feature-text">
                    <strong>Dashboard en temps réel</strong>
                    Statistiques, ventes, chiffre d'affaires
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-icon">📦</div>
                <div class="feature-text">
                    <strong>Gestion des produits</strong>
                    Ajout, modification, stock, images
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-icon">🛒</div>
                <div class="feature-text">
                    <strong>Suivi des commandes</strong>
                    Statuts, livraisons, remboursements
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-icon">👥</div>
                <div class="feature-text">
                    <strong>Gestion des clients</strong>
                    Comptes, historiques, support
                </div>
            </div>
        </div>
    </div>

    <!-- FORMULAIRE DROITE -->
    <div class="form-card">
        <div class="form-header">
            <h2>🔐 Créer un compte Admin</h2>
            <p>Accès immédiat au dashboard après inscription</p>
        </div>

        <?php if ($adminCount > 0): ?>
        <div class="admin-warning">
            <span class="icon">⚠️</span>
            <div>
                <strong>Des comptes admin existent déjà (<?= $adminCount ?>).</strong><br>
                Remplissez le formulaire pour <strong>créer un nouvel admin</strong> ou <strong>écraser un compte existant</strong> avec la même adresse email.
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        <div class="errors">
            <?php foreach ($errors as $err): ?>
                <p>⚠️ <?= htmlspecialchars($err) ?></p>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">

            <!-- Nom -->
            <div class="form-group">
                <label>Nom complet <span>*</span></label>
                <input type="text" name="name" class="form-input"
                       placeholder="Ex : Jean Admin"
                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                       required autofocus>
            </div>

            <!-- Email -->
            <div class="form-group">
                <label>Adresse email <span>*</span></label>
                <input type="email" name="email" class="form-input"
                       placeholder="admin@monsite.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       required>
            </div>

            <!-- Mot de passe -->
            <div class="form-group">
                <label>Mot de passe <span>*</span></label>
                <input type="password" name="password" class="form-input"
                       placeholder="Minimum 6 caractères"
                       id="passwordInput"
                       required minlength="6">
                <div class="password-strength">
                    <div class="strength-bar">
                        <div class="strength-fill" id="strengthFill"></div>
                    </div>
                    <div class="strength-label" id="strengthLabel">Entrez un mot de passe</div>
                </div>
            </div>

            <!-- Confirmation -->
            <div class="form-group">
                <label>Confirmer le mot de passe <span>*</span></label>
                <input type="password" name="confirm" class="form-input"
                       placeholder="Répétez le mot de passe"
                       id="confirmInput"
                       required>
            </div>

            <!-- Clé secrète -->
            <div class="form-group">
                <label>🔑 Clé secrète d'installation <span>*</span></label>
                <input type="password" name="secret_key" class="form-input secret-field"
                       placeholder="Clé fournie par le développeur"
                       required>
                <div style="font-size:11px;color:rgba(255,153,0,.6);margin-top:6px">
                    💡 Clé par défaut : <code style="background:rgba(255,255,255,.07);padding:1px 6px;border-radius:4px;color:rgba(255,153,0,.9)">temu-admin-2024</code>
                </div>
            </div>

            <button type="submit" class="submit-btn">
                <span>🚀</span>
                Créer mon compte et accéder au dashboard
            </button>
        </form>

        <div class="separator"><span>Déjà inscrit ?</span></div>
        <div class="already-link">
            <a href="<?= SITE_URL ?>/admin/login.php">← Se connecter à l'administration</a>
        </div>

        <div class="form-footer" style="margin-top:16px">
            <a href="<?= SITE_URL ?>">🏠 Retour au site</a>
            <a href="<?= SITE_URL ?>/install.php">⚙️ Installation guidée</a>
        </div>
    </div>

</div>

<script>
const pwInput  = document.getElementById('passwordInput');
const cfInput  = document.getElementById('confirmInput');
const fill     = document.getElementById('strengthFill');
const label    = document.getElementById('strengthLabel');

pwInput.addEventListener('input', function () {
    const val = this.value;
    let score = 0;
    if (val.length >= 6)  score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const levels = [
        { pct: '0%',   color: 'transparent', text: 'Entrez un mot de passe' },
        { pct: '20%',  color: '#E74C3C',      text: '😟 Très faible' },
        { pct: '40%',  color: '#E67E22',      text: '😐 Faible' },
        { pct: '60%',  color: '#F39C12',      text: '🙂 Moyen' },
        { pct: '80%',  color: '#27AE60',      text: '😊 Fort' },
        { pct: '100%', color: '#1ABC9C',      text: '💪 Très fort' },
    ];

    const lvl = levels[Math.min(score, 5)];
    fill.style.width     = lvl.pct;
    fill.style.background = lvl.color;
    label.textContent    = lvl.text;
    label.style.color    = lvl.color === 'transparent' ? 'rgba(255,255,255,.3)' : lvl.color;
});

cfInput.addEventListener('input', function () {
    if (this.value && this.value !== pwInput.value) {
        this.style.borderColor = '#E74C3C';
    } else if (this.value && this.value === pwInput.value) {
        this.style.borderColor = '#27AE60';
    } else {
        this.style.borderColor = '';
    }
});
</script>

</body>
</html>
