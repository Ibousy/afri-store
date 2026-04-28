<?php
require_once __DIR__ . '/../includes/functions.php';
startSession();

if (isLoggedIn()) { header('Location: ' . SITE_URL); exit; }

$errors = [];
$redirect = sanitize($_GET['redirect'] ?? SITE_URL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token invalide, réessayez.';
    } else {
        $email    = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $errors[] = 'Veuillez remplir tous les champs.';
        } else {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                flashSuccess('Bienvenue, ' . $user['name'] . ' !');
                // Redirection admin → dashboard, sinon redirect param ou accueil
                if ($user['role'] === 'admin') {
                    header('Location: ' . SITE_URL . '/admin/index.php');
                } else {
                    header('Location: ' . $redirect);
                }
                exit;
            } else {
                $errors[] = 'Email ou mot de passe incorrect.';
            }
        }
    }
}

$pageTitle = 'Connexion';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-page">
    <div class="auth-card">
        <div style="text-align:center;font-size:48px;margin-bottom:8px">🛍️</div>
        <h2>Connexion</h2>
        <p class="subtitle">Connectez-vous à votre compte <?= SITE_NAME ?></p>

        <?php foreach ($errors as $err): ?>
            <div class="flash-message flash-error" style="margin-bottom:16px"><?= h($err) ?></div>
        <?php endforeach; ?>

        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="redirect" value="<?= h($redirect) ?>">

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" placeholder="votre@email.com" 
                       value="<?= h($_POST['email'] ?? '') ?>" required autofocus>
            </div>
            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <div style="text-align:right;font-size:13px;margin-bottom:16px">
                <a href="#" style="color:var(--primary)">Mot de passe oublié ?</a>
            </div>
            <button type="submit" class="btn-block">Se connecter</button>
        </form>

        <div class="auth-switch">
            Pas encore de compte ? <a href="<?= SITE_URL ?>/pages/register.php">Créer un compte</a>
        </div>

        <div style="text-align:center;margin-top:16px;font-size:12px;color:var(--text-muted)">
            Démo : jean@example.com / password
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
