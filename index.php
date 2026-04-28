<?php
$pageTitle = 'Accueil';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Bannières
$banners = $db->query("SELECT * FROM banners WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();

// Produits en flash sale
$flashProducts = getProducts(['flash_sale' => true, 'limit' => 10]);

// Produits vedettes
$featuredProducts = getProducts(['featured' => true, 'limit' => 10]);

// Nouveautés
$newProducts = getProducts(['sort' => 'newest', 'limit' => 10]);
?>

<div class="container">

<!-- =========================================================
     HERO SLIDER
========================================================= -->
<?php if (!empty($banners)): ?>
<div class="hero-slider">
    <div class="slider-track">
        <?php foreach ($banners as $banner): ?>
        <div class="slide">
            <img src="<?= h($banner['image_url']) ?>" alt="<?= h($banner['title']) ?>" loading="eager">
            <div class="slide-content">
                <h2><?= h($banner['title']) ?></h2>
                <p><?= h($banner['subtitle']) ?></p>
                <a href="<?= h($banner['link_url'] ?? '#') ?>" class="slide-btn">Découvrir →</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <button class="slider-arrow prev">‹</button>
    <button class="slider-arrow next">›</button>
    <div class="slider-dots">
        <?php foreach ($banners as $i => $b): ?>
            <button class="slider-dot <?= $i === 0 ? 'active' : '' ?>"></button>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- =========================================================
     CATÉGORIES
========================================================= -->
<div class="section">
    <div class="section-header">
        <h2 class="section-title">🛍️ Toutes les catégories</h2>
    </div>
    <div class="categories-grid">
        <?php foreach ($categories as $cat): ?>
        <a href="<?= SITE_URL ?>/pages/category.php?id=<?= $cat['id'] ?>" class="cat-card">
            <div class="cat-icon"><?= h($cat['icon']) ?></div>
            <div class="cat-name"><?= h($cat['name']) ?></div>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- =========================================================
     FLASH SALE
========================================================= -->
<?php if (!empty($flashProducts)): ?>
<div class="section">
    <div class="flash-header">
        <div class="flash-title">⚡ Vente Flash</div>
        <div id="countdownEnd" data-end="<?= date('Y-m-d') ?>T23:59:59" class="countdown">
            <div class="countdown-unit"><span class="countdown-num" id="cdH">00</span><span class="countdown-label">H</span></div>
            <div class="countdown-unit"><span class="countdown-num" id="cdM">00</span><span class="countdown-label">M</span></div>
            <div class="countdown-unit"><span class="countdown-num" id="cdS">00</span><span class="countdown-label">S</span></div>
        </div>
    </div>
    <div class="products-grid">
        <?php foreach ($flashProducts as $p): ?>
            <?php include __DIR__ . '/includes/product_card.php'; ?>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- =========================================================
     PRODUITS VEDETTES
========================================================= -->
<?php if (!empty($featuredProducts)): ?>
<div class="section">
    <div class="section-header">
        <h2 class="section-title">⭐ Sélection du jour</h2>
        <a href="<?= SITE_URL ?>/pages/search.php?featured=1" class="section-link">Voir tout →</a>
    </div>
    <div class="products-grid">
        <?php foreach ($featuredProducts as $p): ?>
            <?php include __DIR__ . '/includes/product_card.php'; ?>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- =========================================================
     PROMO BANNER
========================================================= -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin:16px 0">
    <div style="background:linear-gradient(135deg,#FF6000,#FF9900);border-radius:16px;padding:28px;color:white">
        <div style="font-size:13px;opacity:.8;font-weight:600">LIVRAISON</div>
        <div style="font-size:28px;font-weight:900;margin:4px 0">Gratuite</div>
        <div style="font-size:14px;opacity:.9">dès 19 000 FCFA d'achat</div>
        <a href="<?= SITE_URL ?>/pages/search.php" style="display:inline-block;margin-top:14px;background:white;color:#FF6000;padding:8px 20px;border-radius:20px;font-weight:800;font-size:13px">J'en profite</a>
    </div>
    <div style="background:linear-gradient(135deg,#FF2D55,#FF6B9D);border-radius:16px;padding:28px;color:white">
        <div style="font-size:13px;opacity:.8;font-weight:600">RETOURS</div>
        <div style="font-size:28px;font-weight:900;margin:4px 0">90 jours</div>
        <div style="font-size:14px;opacity:.9">Retour gratuit, remboursement garanti</div>
        <a href="#" style="display:inline-block;margin-top:14px;background:white;color:#FF2D55;padding:8px 20px;border-radius:20px;font-weight:800;font-size:13px">En savoir plus</a>
    </div>
</div>

<!-- =========================================================
     NOUVEAUTÉS
========================================================= -->
<?php if (!empty($newProducts)): ?>
<div class="section">
    <div class="section-header">
        <h2 class="section-title">🆕 Nouveautés</h2>
        <a href="<?= SITE_URL ?>/pages/search.php?sort=newest" class="section-link">Voir tout →</a>
    </div>
    <div class="products-grid">
        <?php foreach ($newProducts as $p): ?>
            <?php include __DIR__ . '/includes/product_card.php'; ?>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

</div><!-- /.container -->

<?php require_once __DIR__ . '/includes/footer.php'; ?>
