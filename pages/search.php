<?php
require_once __DIR__ . '/../includes/functions.php';

$q    = sanitize($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'default';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;
$flash = !empty($_GET['flash']);
$featured = !empty($_GET['featured']);

$opts = [
    'search'     => $q,
    'sort'       => $sort,
    'limit'      => $limit,
    'offset'     => $offset,
    'flash_sale' => $flash ?: null,
    'featured'   => $featured ?: null,
];
$products = getProducts($opts);

$pageTitle = $q ? "Recherche: $q" : ($flash ? 'Flash Sale' : 'Tous les produits');
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="breadcrumb">
        <a href="<?= SITE_URL ?>">Accueil</a> <span>›</span>
        <span class="current"><?= $q ? 'Résultats pour "'.h($q).'"' : h($pageTitle) ?></span>
    </div>

    <?php if ($flash): ?>
    <div class="flash-header" style="margin-bottom:20px">
        <div class="flash-title">⚡ Vente Flash — Offres limitées !</div>
        <div id="countdownEnd" data-end="<?= date('Y-m-d') ?>T23:59:59" class="countdown">
            <div class="countdown-unit"><span class="countdown-num" id="cdH">00</span><span class="countdown-label">H</span></div>
            <div class="countdown-unit"><span class="countdown-num" id="cdM">00</span><span class="countdown-label">M</span></div>
            <div class="countdown-unit"><span class="countdown-num" id="cdS">00</span><span class="countdown-label">S</span></div>
        </div>
    </div>
    <?php else: ?>
    <h1 style="font-size:24px;font-weight:900;margin:16px 0">
        <?= $q ? '🔍 Résultats pour "'.h($q).'"' : h($pageTitle) ?>
        <span style="font-size:14px;font-weight:400;color:var(--text-muted)">(<?= count($products) ?> produits)</span>
    </h1>
    <?php endif; ?>

    <!-- Tri -->
    <div class="sort-bar">
        <span style="font-size:13px;color:var(--text-muted)"><?= count($products) ?> produits</span>
        <div class="sort-options">
            <?php
            $sorts = ['default' => '⭐ Populaires', 'newest' => '🆕 Nouveautés', 'price_asc' => '💰 Prix ↑', 'price_desc' => '💰 Prix ↓', 'rating' => '⭐ Mieux notés'];
            foreach ($sorts as $key => $label):
            ?>
            <button class="sort-btn <?= $sort === $key ? 'active' : '' ?>" data-sort="<?= $key ?>"><?= $label ?></button>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (empty($products)): ?>
        <div class="empty-state">
            <div class="empty-icon">🔍</div>
            <h3>Aucun résultat</h3>
            <p>Aucun produit ne correspond à votre recherche "<?= h($q) ?>"</p>
            <a href="<?= SITE_URL ?>" class="btn-primary" style="display:inline-block;padding:12px 28px;border-radius:30px">Retour à l'accueil</a>
        </div>
    <?php else: ?>
        <div class="products-grid">
            <?php foreach ($products as $p): ?>
                <?php include __DIR__ . '/../includes/product_card.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
