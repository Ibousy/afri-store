<?php
require_once __DIR__ . '/../includes/functions.php';
startSession();
requireLogin(); // avant tout HTML

$pageTitle = 'Mes Favoris';
require_once __DIR__ . '/../includes/header.php';

$db = getDB();
$stmt = $db->prepare("
    SELECT p.*, pi.image_url AS primary_image, c.name AS category_name
    FROM wishlist w
    JOIN products p ON p.id = w.product_id
    LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE w.user_id = ?
    ORDER BY w.added_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$products = $stmt->fetchAll();
?>

<div class="container">
    <div class="breadcrumb">
        <a href="<?= SITE_URL ?>">Accueil</a> <span>›</span>
        <span class="current">Mes Favoris</span>
    </div>

    <h1 style="font-size:26px;font-weight:900;margin-bottom:20px">❤️ Mes Favoris (<?= count($products) ?>)</h1>

    <?php if (empty($products)): ?>
    <div class="empty-state">
        <div class="empty-icon">❤️</div>
        <h3>Aucun favori pour l'instant</h3>
        <p>Ajoutez des produits à vos favoris en cliquant sur le cœur ❤️</p>
        <a href="<?= SITE_URL ?>" class="btn-primary" style="display:inline-block;padding:14px 36px;border-radius:30px">Découvrir des produits</a>
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
