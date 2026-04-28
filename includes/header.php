<?php
require_once __DIR__ . '/../includes/functions.php';
startSession();
$cartCount = getCartCount();
$user = getCurrentUser();
$categories = getCategories();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle ?? SITE_NAME) ?> | <?= SITE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🛍️</text></svg>">
</head>
<body>

<!-- TOP BAR -->
<div class="topbar">
    <div class="container topbar-inner">
        <span>🚚 Livraison gratuite dès 19 000 FCFA | 🎁 Retours gratuits 90 jours</span>
        <div class="topbar-links">
            <?php if ($user): ?>
                <a href="<?= SITE_URL ?>/pages/account.php">👤 <?= h($user['name']) ?></a>
                <a href="<?= SITE_URL ?>/pages/orders.php">📦 Commandes</a>
                <a href="<?= SITE_URL ?>/api/auth.php?action=logout">Déconnexion</a>
            <?php else: ?>
                <a href="<?= SITE_URL ?>/pages/login.php">Se connecter</a>
                <a href="<?= SITE_URL ?>/pages/register.php">S'inscrire</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- HEADER -->
<header class="header">
    <div class="container header-inner">
        <!-- Logo -->
        <a href="<?= SITE_URL ?>" class="logo">
            <span class="logo-icon">🛍️</span>
            <span class="logo-text"><?= SITE_NAME ?></span>
        </a>

        <!-- Barre de recherche -->
        <form class="search-bar" action="<?= SITE_URL ?>/pages/search.php" method="GET">
            <input type="text" name="q" placeholder="Rechercher des produits..." 
                   value="<?= h($_GET['q'] ?? '') ?>" autocomplete="off">
            <button type="submit" class="search-btn">🔍</button>
        </form>

        <!-- Actions -->
        <div class="header-actions">
            <a href="<?= SITE_URL ?>/pages/wishlist.php" class="action-btn" title="Favoris">
                <span class="action-icon">❤️</span>
                <span class="action-label">Favoris</span>
            </a>
            <a href="<?= SITE_URL ?>/pages/cart.php" class="action-btn cart-btn" title="Panier">
                <span class="action-icon">🛒</span>
                <?php if ($cartCount > 0): ?>
                    <span class="cart-badge"><?= $cartCount ?></span>
                <?php endif; ?>
                <span class="action-label">Panier</span>
            </a>
            <?php if ($user): ?>
                <a href="<?= SITE_URL ?>/pages/account.php" class="action-btn" title="Mon compte">
                    <span class="action-icon">👤</span>
                    <span class="action-label">Compte</span>
                </a>
            <?php else: ?>
                <a href="<?= SITE_URL ?>/pages/login.php" class="action-btn" title="Connexion">
                    <span class="action-icon">👤</span>
                    <span class="action-label">Connexion</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Navigation catégories -->
    <nav class="category-nav">
        <div class="container">
            <ul class="cat-list">
                <li><a href="<?= SITE_URL ?>" class="<?= empty($_GET) && basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">🏠 Accueil</a></li>
                <?php foreach ($categories as $cat): ?>
                    <li>
                        <a href="<?= SITE_URL ?>/pages/category.php?id=<?= $cat['id'] ?>"
                           class="<?= (($_GET['id'] ?? '') == $cat['id']) ? 'active' : '' ?>">
                            <?= h($cat['icon']) ?> <?= h($cat['name']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
                <li><a href="<?= SITE_URL ?>/pages/search.php?flash=1" class="flash-link">⚡ Flash Sale</a></li>
            </ul>
        </div>
    </nav>
</header>

<!-- FLASH MESSAGE -->
<?php $flash = flash('msg'); if ($flash): ?>
    <div class="flash-message flash-<?= $flash['type'] ?>">
        <?= h($flash['msg']) ?>
        <button onclick="this.parentElement.remove()">✕</button>
    </div>
<?php endif; ?>

<main class="main-content">
