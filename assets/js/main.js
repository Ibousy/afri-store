// ============================================================
//  TEMU CLONE — JavaScript principal
// ============================================================

const SITE_URL = document.querySelector('meta[name="site-url"]')?.content || '';

// ---- SLIDER ----
(function initSlider() {
    const track  = document.querySelector('.slider-track');
    const dots   = document.querySelectorAll('.slider-dot');
    if (!track) return;

    let current = 0;
    const total = track.children.length;

    function goTo(idx) {
        current = (idx + total) % total;
        track.style.transform = `translateX(-${current * 100}%)`;
        dots.forEach((d, i) => d.classList.toggle('active', i === current));
    }

    dots.forEach((d, i) => d.addEventListener('click', () => goTo(i)));
    document.querySelector('.slider-arrow.prev')?.addEventListener('click', () => goTo(current - 1));
    document.querySelector('.slider-arrow.next')?.addEventListener('click', () => goTo(current + 1));

    const auto = setInterval(() => goTo(current + 1), 4500);
    track.closest('.hero-slider')?.addEventListener('mouseenter', () => clearInterval(auto));
})();

// ---- COUNTDOWN ----
(function initCountdown() {
    const endEl = document.getElementById('countdownEnd');
    if (!endEl) return;
    const end = new Date(endEl.dataset.end);

    function update() {
        const diff = Math.max(0, end - Date.now());
        const h = Math.floor(diff / 3600000);
        const m = Math.floor((diff % 3600000) / 60000);
        const s = Math.floor((diff % 60000) / 1000);
        document.getElementById('cdH').textContent = String(h).padStart(2, '0');
        document.getElementById('cdM').textContent = String(m).padStart(2, '0');
        document.getElementById('cdS').textContent = String(s).padStart(2, '0');
    }
    update();
    setInterval(update, 1000);
})();

// ---- ADD TO CART ----
document.addEventListener('click', async function (e) {
    const btn = e.target.closest('.add-to-cart-btn, [data-add-cart]');
    if (!btn) return;

    const productId = btn.dataset.id || btn.closest('[data-product-id]')?.dataset.productId;
    if (!productId) return;

    const qty = parseInt(document.getElementById('qty')?.value || 1);
    const variantId = document.querySelector('.variant-option.selected')?.dataset.variantId || '';

    btn.disabled = true;
    btn.textContent = '⏳ Ajout...';

    try {
        const res = await fetch('/temu-clone/api/cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add', product_id: productId, quantity: qty, variant_id: variantId })
        });
        const data = await res.json();

        if (data.success) {
            showNotif('✅ ' + (data.message || 'Ajouté au panier !'));
            const badge = document.querySelector('.cart-badge');
            if (badge) {
                badge.textContent = data.cart_count;
            } else {
                const cartBtn = document.querySelector('.cart-btn');
                if (cartBtn) {
                    const b = document.createElement('span');
                    b.className = 'cart-badge';
                    b.textContent = data.cart_count;
                    cartBtn.appendChild(b);
                }
            }
        } else {
            showNotif('❌ ' + (data.message || 'Erreur'), 'error');
        }
    } catch {
        showNotif('❌ Erreur réseau', 'error');
    }

    btn.disabled = false;
    btn.textContent = '🛒 Ajouter au panier';
});

// ---- WISHLIST ----
document.addEventListener('click', async function (e) {
    const btn = e.target.closest('.wishlist-btn');
    if (!btn) return;

    const productId = btn.dataset.id;
    try {
        const res = await fetch('/temu-clone/api/wishlist.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId })
        });
        const data = await res.json();
        if (data.success) {
            btn.textContent = data.in_wishlist ? '❤️' : '🤍';
            btn.classList.toggle('active', data.in_wishlist);
            showNotif(data.in_wishlist ? '❤️ Ajouté aux favoris' : '🤍 Retiré des favoris');
        } else if (data.redirect) {
            window.location.href = data.redirect;
        }
    } catch {}
});

// ---- QUANTITY SELECTOR ----
document.addEventListener('click', function (e) {
    if (e.target.classList.contains('qty-dec')) {
        const input = e.target.nextElementSibling;
        input.value = Math.max(1, parseInt(input.value) - 1);
    }
    if (e.target.classList.contains('qty-inc')) {
        const input = e.target.previousElementSibling;
        const max = parseInt(input.dataset.max || 99);
        input.value = Math.min(max, parseInt(input.value) + 1);
    }
});

// ---- VARIANT SELECTION ----
document.addEventListener('click', function (e) {
    const opt = e.target.closest('.variant-option');
    if (!opt) return;
    const group = opt.closest('.variant-options');
    group?.querySelectorAll('.variant-option').forEach(o => o.classList.remove('selected'));
    opt.classList.add('selected');
});

// ---- CART UPDATE ----
document.addEventListener('click', async function (e) {
    const btn = e.target.closest('[data-cart-remove]');
    if (!btn) return;
    const cartId = btn.dataset.cartRemove;
    if (!confirm('Retirer cet article ?')) return;
    const res = await fetch('/temu-clone/api/cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'remove', cart_id: cartId })
    });
    const data = await res.json();
    if (data.success) location.reload();
});

document.addEventListener('change', async function (e) {
    const input = e.target.closest('[data-cart-qty]');
    if (!input) return;
    const cartId = input.dataset.cartQty;
    const qty = parseInt(input.value);
    await fetch('/temu-clone/api/cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'update', cart_id: cartId, quantity: qty })
    });
    location.reload();
});

// ---- THUMBNAIL SWITCHER ----
document.addEventListener('click', function (e) {
    const thumb = e.target.closest('.thumb');
    if (!thumb) return;
    const src = thumb.querySelector('img')?.src;
    const mainImg = document.querySelector('.main-image img');
    if (src && mainImg) mainImg.src = src;
    document.querySelectorAll('.thumb').forEach(t => t.classList.remove('active'));
    thumb.classList.add('active');
});

// ---- NOTIFICATION ----
function showNotif(msg, type = 'success') {
    const el = document.getElementById('cartNotif');
    if (!el) return;
    el.textContent = msg;
    el.className = 'cart-notif show';
    el.style.background = type === 'error' ? '#E74C3C' : '#27AE60';
    setTimeout(() => el.classList.remove('show'), 3000);
}

// ---- BACK TO TOP ----
const backBtn = document.getElementById('backToTop');
if (backBtn) {
    window.addEventListener('scroll', () => {
        backBtn.classList.toggle('visible', window.scrollY > 400);
    });
}

// ---- COUPON ----
const couponBtn = document.getElementById('applyCoupon');
if (couponBtn) {
    couponBtn.addEventListener('click', async () => {
        const code = document.getElementById('couponCode')?.value.trim();
        if (!code) return;
        const res = await fetch('/temu-clone/api/cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'coupon', code })
        });
        const data = await res.json();
        if (data.success) {
            showNotif('✅ Coupon appliqué : -' + data.discount);
            location.reload();
        } else {
            showNotif('❌ ' + (data.message || 'Coupon invalide'), 'error');
        }
    });
}

// ---- SEARCH SUGGESTIONS ----
(function initSearchSuggest() {
    const input = document.querySelector('.search-bar input');
    if (!input) return;
    let timer;
    input.addEventListener('input', function () {
        clearTimeout(timer);
        const q = this.value.trim();
        if (q.length < 2) return;
        timer = setTimeout(async () => {
            // Could be extended with an API endpoint
        }, 300);
    });
})();

// ---- SORT ----
document.querySelectorAll('.sort-btn[data-sort]').forEach(btn => {
    btn.addEventListener('click', function () {
        const url = new URL(window.location.href);
        url.searchParams.set('sort', this.dataset.sort);
        window.location.href = url.toString();
    });
});
