<?php
require_once __DIR__ . '/../includes/functions.php';

$db = getDB();
$catId = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM categories WHERE id = ? AND is_active = 1");
$stmt->execute([$catId]);
$category = $stmt->fetch();

if (!$category) { header('Location: ' . SITE_URL); exit; }

$sort   = $_GET['sort'] ?? 'default';
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 20;
$offset = ($page - 1) * $limit;

$minPrice = (float)($_GET['min_price'] ?? 0);
$maxPrice = (float)($_GET['max_price'] ?? 0);

$opts = [
    'category_id' => $catId,
    'sort'        => $sort,
    'limit'       => $limit,
    'offset'      => $offset,
    'min_price'   => $minPrice ?: null,
    'max_price'   => $maxPrice ?: null,
];
$products = getProducts($opts);

// Total
$where = ['p.is_active = 1', 'p.category_id = ?'];
$params = [$catId];
$total = $db->prepare("SELECT COUNT(*) FROM products p WHERE " . implode(' AND ', $where));
$total->execute($params);
$totalCount = $total->fetchColumn();
$totalPages = ceil($totalCount / $limit);

$pageTitle = $category['name'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="breadcrumb">
        <a href="<?= SITE_URL ?>">Accueil</a> <span>›</span>
        <span class="current"><?= h($category['name']) ?></span>
    </div>

    <h1 style="font-size:26px;font-weight:900;margin-bottom:20px">
        <?= h($category['icon']) ?> <?= h($category['name']) ?>
        <span style="font-size:15px;font-weight:400;color:var(--text-muted)">(<?= $totalCount ?> produits)</span>
    </h1>

    <div class="search-layout">
        <!-- Filtres -->
        <aside class="filters-sidebar">
            <h3 style="font-weight:900;margin-bottom:16px">🎛️ Filtres</h3>
            <form method="GET">
                <input type="hidden" name="id" value="<?= $catId ?>">
                <div class="filter-group">
                    <div class="filter-title">Prix</div>
                    <div style="display:flex;gap:8px;align-items:center">
                        <input type="number" name="min_price" value="<?= $minPrice ?: '' ?>" placeholder="Min €" class="form-control" style="padding:8px">
                        <span>—</span>
                        <input type="number" name="max_price" value="<?= $maxPrice ?: '' ?>" placeholder="Max €" class="form-control" style="padding:8px">
                    </div>
                </div>
                <div class="filter-group">
                    <div class="filter-title">Note minimale</div>
                    <?php foreach ([4, 3, 2] as $r): ?>
                    <label class="filter-option">
                        <input type="radio" name="min_rating" value="<?= $r ?>" <?= ($_GET['min_rating'] ?? '') == $r ? 'checked' : '' ?>>
                        <span><?= str_repeat('★', $r) ?><?= str_repeat('☆', 5-$r) ?> et plus</span>
                    </label>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="btn-primary" style="width:100%;padding:10px">Appliquer</button>
                <a href="?id=<?= $catId ?>" style="display:block;text-align:center;margin-top:10px;font-size:13px;color:var(--text-muted)">Réinitialiser</a>
            </form>
        </aside>

        <!-- Produits -->
        <div>
            <!-- Tri -->
            <div class="sort-bar">
                <span style="font-size:13px;color:var(--text-muted)"><?= $totalCount ?> résultats</span>
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
                    <div class="empty-icon">📦</div>
                    <h3>Aucun produit trouvé</h3>
                    <p>Essayez d'autres filtres</p>
                </div>
            <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($products as $p): ?>
                        <?php include __DIR__ . '/../includes/product_card.php'; ?>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?id=<?= $catId ?>&page=<?= $page-1 ?>&sort=<?= $sort ?>" class="page-btn">‹ Précédent</a>
                    <?php endif; ?>
                    <?php for ($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++): ?>
                        <a href="?id=<?= $catId ?>&page=<?= $i ?>&sort=<?= $sort ?>" class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="?id=<?= $catId ?>&page=<?= $page+1 ?>&sort=<?= $sort ?>" class="page-btn">Suivant ›</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
