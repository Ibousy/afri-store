<?php
// Variables attendues: $p (product array)
$inWishlist = isInWishlist((int)$p['id']);
?>
<div class="product-card">
    <a href="<?= SITE_URL ?>/pages/product.php?id=<?= $p['id'] ?>" class="product-img-wrap">
        <img src="<?= h($p['primary_image'] ?? 'https://picsum.photos/seed/'.$p['id'].'/400/400') ?>" 
             alt="<?= h($p['name']) ?>" loading="lazy">
        <?php if ($p['discount_percent'] > 0): ?>
            <span class="product-badge <?= $p['is_flash_sale'] ? 'badge-flash' : '' ?>">
                -<?= $p['discount_percent'] ?>%
            </span>
        <?php endif; ?>
        <button class="wishlist-btn <?= $inWishlist ? 'active' : '' ?>" 
                data-id="<?= $p['id'] ?>" 
                onclick="event.preventDefault()"
                title="Ajouter aux favoris">
            <?= $inWishlist ? '❤️' : '🤍' ?>
        </button>
    </a>
    <div class="product-info">
        <div class="product-name"><?= h($p['name']) ?></div>
        <div class="product-rating">
            <span class="stars"><?= str_repeat('★', round($p['rating'])) ?><?= str_repeat('☆', 5 - round($p['rating'])) ?></span>
            <span class="review-count">(<?= number_format($p['review_count']) ?>)</span>
        </div>
        <div class="product-price">
            <span class="price-current"><?= formatPrice($p['price']) ?></span>
            <?php if ($p['original_price'] && $p['original_price'] > $p['price']): ?>
                <span class="price-original"><?= formatPrice($p['original_price']) ?></span>
                <span class="price-discount">-<?= $p['discount_percent'] ?>%</span>
            <?php endif; ?>
        </div>
        <?php if ($p['sold_count'] > 0): ?>
            <div class="sold-count"><?= number_format($p['sold_count']) ?> vendus</div>
        <?php endif; ?>
        <button class="add-to-cart-btn" data-id="<?= $p['id'] ?>">🛒 Ajouter au panier</button>
    </div>
</div>
