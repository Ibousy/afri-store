<?php
require_once __DIR__ . '/../includes/functions.php';
startSession();

if (isLoggedIn()) { header('Location: ' . SITE_URL); exit; }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token invalide.';
    } else {
        $name     = sanitize($_POST['name'] ?? '');
        $email    = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm'] ?? '';

        if (empty($name) || empty($email) || empty($password)) {
            $errors[] = 'Tous les champs sont obligatoires.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email invalide.';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Le mot de passe doit faire au moins 6 caractères.';
        } elseif ($password !== $confirm) {
            $errors[] = 'Les mots de passe ne correspondent pas.';
        } else {
            $db = getDB();
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'Cet email est déjà utilisé.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$name, $email, $hash]);
                $userId = $db->lastInsertId();
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_name'] = $name;
                flashSuccess('Bienvenue ' . $name . ' ! Votre compte a été créé.');
                header('Location: ' . SITE_URL);
                exit;
            }
        }
    }
}

$pageTitle = "Créer un compte";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-page">
    <div class="auth-card">
        <div style="text-align:center;font-size:48px;margin-bottom:8px">🎉</div>
        <h2>Créer un compte</h2>
        <p class="subtitle">Rejoignez des millions d'acheteurs sur <?= SITE_NAME ?></p>

        <?php foreach ($errors as $err): ?>
            <div class="flash-message flash-error" style="margin-bottom:16px"><?= h($err) ?></div>
        <?php endforeach; ?>

        <form method="POST">
            <?= csrfField() ?>
            <div class="form-group">
                <label>Prénom & Nom</label>
                <input type="text" name="name" class="form-control" placeholder="Jean Dupont"
                       value="<?= h($_POST['name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" placeholder="votre@email.com"
                       value="<?= h($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" name="password" class="form-control" placeholder="Min. 6 caractères" required>
            </div>
            <div class="form-group">
                <label>Confirmer le mot de passe</label>
                <input type="password" name="confirm" class="form-control" placeholder="••••••••" required>
            </div>
            <div style="font-size:12px;color:var(--text-muted);margin-bottom:16px">
                En créant un compte, vous acceptez nos <a href="#" style="color:var(--primary)">CGU</a> et notre <a href="#" style="color:var(--primary)">politique de confidentialité</a>.
            </div>
            <button type="submit" class="btn-block">Créer mon compte</button>
        </form>

        <div class="auth-switch">
            Déjà un compte ? <a href="<?= SITE_URL ?>/pages/login.php">Se connecter</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
