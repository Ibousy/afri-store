<?php
require_once __DIR__ . '/../includes/functions.php';
startSession();

$pageTitle = 'Mon Panier';
require_once __DIR__ . '/../includes/header.php';

$cart  = getCart();
$total = getCartTotal();
$couponDiscount = $_SESSION['coupon_discount'] ?? 0;
$couponCode     = $_SESSION['coupon_code'] ?? '';
$shipping = $total >= 19000 ? 0 : 3500;
$finalTotal = max(0, $total - $couponDiscount) + $shipping;
?>

<div class="container">
    <div class="breadcrumb">
        <a href="<?= SITE_URL ?>">Accueil</a> <span>›</span>
        <span class="current">Mon Panier</span>
    </div>

    <h1 style="font-size:26px;font-weight:900;margin-bottom:20px">🛒 Mon Panier (<?= array_sum(array_column($cart,'quantity')) ?> articles)</h1>

    <?php if (empty($cart)): ?>
    <div class="empty-state">
        <div class="empty-icon">🛒</div>
        <h3>Votre panier est vide</h3>
        <p>Découvrez nos milliers de produits à prix imbattables !</p>
        <a href="<?= SITE_URL ?>" class="btn-primary" style="display:inline-block;padding:14px 36px;border-radius:30px">Commencer à shopper</a>
    </div>
    <?php else: ?>
    <div class="cart-layout">
        <!-- Articles -->
        <div class="cart-items">
            <h3 style="font-weight:800;margin-bottom:16px">Articles</h3>
            <?php foreach ($cart as $item): 
                $itemPrice = $item['price'] + ($item['extra_price'] ?? 0);
            ?>
            <div class="cart-item">
                <a href="<?= SITE_URL ?>/pages/product.php?id=<?= $item['product_id'] ?>">
                    <img src="<?= h($item['image_url'] ?: 'https://picsum.photos/seed/'.$item['product_id'].'/200/200') ?>" 
                         class="cart-item-img" alt="<?= h($item['name']) ?>">
                </a>
                <div>
                    <div class="cart-item-name">
                        <a href="<?= SITE_URL ?>/pages/product.php?id=<?= $item['product_id'] ?>"><?= h($item['name']) ?></a>
                    </div>
                    <?php if ($item['variant_value']): ?>
                        <div class="cart-item-variant"><?= h($item['variant_name']) ?> : <?= h($item['variant_value']) ?></div>
                    <?php endif; ?>
                    <div class="cart-item-price"><?= formatPrice($itemPrice) ?></div>
                    <div style="font-size:12px;color:var(--text-muted)">Sous-total : <?= formatPrice($itemPrice * $item['quantity']) ?></div>
                </div>
                <div class="cart-item-actions">
                    <div class="quantity-selector" style="flex-direction:row">
                        <button class="qty-btn" onclick="updateQty(<?= $item['id'] ?>, -1, this)">−</button>
                        <input type="number" class="qty-input" value="<?= $item['quantity'] ?>" min="1" 
                               data-cart-qty="<?= $item['id'] ?>" style="width:50px">
                        <button class="qty-btn" onclick="updateQty(<?= $item['id'] ?>, 1, this)">+</button>
                    </div>
                    <button class="remove-btn" data-cart-remove="<?= $item['id'] ?>">🗑️ Retirer</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Résumé commande -->
        <div class="order-summary">
            <h3>Résumé de la commande</h3>

            <div class="summary-row">
                <span>Sous-total</span>
                <span><?= formatPrice($total) ?></span>
            </div>
            <?php if ($couponDiscount > 0): ?>
            <div class="summary-row" style="color:var(--success)">
                <span>Coupon (<?= h($couponCode) ?>)</span>
                <span>-<?= formatPrice($couponDiscount) ?></span>
            </div>
            <?php endif; ?>
            <div class="summary-row">
                <span>Livraison</span>
                <span><?= $shipping == 0 ? '<span style="color:var(--success)">Gratuite</span>' : formatPrice($shipping) ?></span>
            </div>
            <?php if ($total < 29): ?>
            <div style="font-size:12px;color:var(--text-muted);background:var(--primary-light);padding:8px 10px;border-radius:8px">
                Plus que <?= formatPrice(29 - $total) ?> pour la livraison gratuite !
            </div>
            <?php endif; ?>
            <div class="summary-row total">
                <span>Total</span>
                <span><?= formatPrice($finalTotal) ?></span>
            </div>

            <!-- Coupon -->
            <div style="font-weight:700;margin-top:16px;font-size:14px">Code promo</div>
            <div class="coupon-form">
                <input type="text" id="couponCode" class="coupon-input" placeholder="Ex: BIENVENUE10" value="<?= h($couponCode) ?>">
                <button id="applyCoupon" class="coupon-btn">OK</button>
            </div>

            <?php if (isLoggedIn()): ?>
                <a href="<?= SITE_URL ?>/pages/checkout.php" class="checkout-btn">Passer la commande →</a>
            <?php else: ?>
                <a href="<?= SITE_URL ?>/pages/login.php?redirect=<?= urlencode('/temu-clone/pages/checkout.php') ?>" class="checkout-btn">Se connecter pour commander</a>
            <?php endif; ?>

            <div style="display:flex;justify-content:center;gap:16px;margin-top:16px;font-size:24px">
                <span title="Visa">💳</span><span title="Mastercard">💳</span><span title="PayPal">🅿️</span>
            </div>
            <div style="text-align:center;font-size:12px;color:var(--text-muted);margin-top:6px">Paiement 100% sécurisé</div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function updateQty(cartId, delta, btn) {
    const input = btn.parentElement.querySelector('input');
    let val = parseInt(input.value) + delta;
    if (val < 1) { if (!confirm('Retirer cet article ?')) return; }
    input.value = Math.max(1, val);
    input.dispatchEvent(new Event('change'));
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
