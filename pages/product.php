<?php
require_once __DIR__ . '/../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$product = getProduct($id);

if (!$product) {
    header('Location: ' . SITE_URL);
    exit;
}

$db = getDB();
$reviewError   = '';
$reviewSuccess = '';

// ── Soumission d'un avis ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isLoggedIn()) {
        $reviewError = 'Connectez-vous pour laisser un avis.';
    } elseif (!verifyToken($_POST['csrf_token'] ?? '')) {
        $reviewError = 'Token invalide.';
    } else {
        $rating  = (int)($_POST['rating'] ?? 0);
        $title   = sanitize($_POST['review_title'] ?? '');
        $comment = sanitize($_POST['comment'] ?? '');
        if ($rating < 1 || $rating > 5) {
            $reviewError = 'Veuillez sélectionner une note entre 1 et 5 étoiles.';
        } elseif (empty($comment)) {
            $reviewError = 'Le commentaire est obligatoire.';
        } else {
            // Vérifier si l'utilisateur a déjà noté ce produit
            $already = $db->prepare("SELECT id FROM reviews WHERE product_id = ? AND user_id = ?");
            $already->execute([$id, $_SESSION['user_id']]);
            if ($already->fetch()) {
                $reviewError = 'Vous avez déjà laissé un avis sur ce produit.';
            } else {
                $db->prepare("INSERT INTO reviews (product_id, user_id, rating, title, comment, is_approved) VALUES (?,?,?,?,?,1)")
                   ->execute([$id, $_SESSION['user_id'], $rating, $title, $comment]);
                // Recalculer la note moyenne
                $avg = $db->prepare("SELECT AVG(rating), COUNT(*) FROM reviews WHERE product_id = ? AND is_approved = 1");
                $avg->execute([$id]);
                [$avgRating, $cnt] = $avg->fetch(\PDO::FETCH_NUM);
                $db->prepare("UPDATE products SET rating = ?, review_count = ? WHERE id = ?")
                   ->execute([round((float)$avgRating, 2), (int)$cnt, $id]);
                $reviewSuccess = 'Votre avis a été publié !';
                // Recharger le produit pour afficher la nouvelle note
                $product = getProduct($id);
            }
        }
    }
}

// Calcul réel des barres par étoile
$starCounts = [5=>0,4=>0,3=>0,2=>0,1=>0];
foreach ($product['reviews'] as $r) {
    $starCounts[(int)$r['rating']] = ($starCounts[(int)$r['rating']] ?? 0) + 1;
}
$totalReviews = array_sum($starCounts);

$pageTitle = $product['name'];
require_once __DIR__ . '/../includes/header.php';

// Produits similaires
$related = getProducts(['category_id' => $product['category_id'], 'limit' => 5]);
$related = array_filter($related, fn($r) => $r['id'] != $product['id']);
?>

<div class="container">
    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="<?= SITE_URL ?>">Accueil</a>
        <span>›</span>
        <a href="<?= SITE_URL ?>/pages/category.php?id=<?= $product['category_id'] ?>"><?= h($product['category_name']) ?></a>
        <span>›</span>
        <span class="current"><?= h($product['name']) ?></span>
    </div>

    <!-- Détail produit -->
    <div class="product-detail">
        <div class="product-detail-grid">
            <!-- ══ GALERIE 4 IMAGES ══════════════════════════════ -->
            <?php
            $allImgs = $product['images'];
            // Compléter jusqu'à 4 images avec des placeholders si nécessaire
            $seed = $product['id'];
            $placeholders = [
                ['image_url' => 'https://picsum.photos/seed/'.$seed.'/600/600',   'alt_text' => ''],
                ['image_url' => 'https://picsum.photos/seed/'.($seed+1).'/600/600','alt_text' => ''],
                ['image_url' => 'https://picsum.photos/seed/'.($seed+2).'/600/600','alt_text' => ''],
                ['image_url' => 'https://picsum.photos/seed/'.($seed+3).'/600/600','alt_text' => ''],
            ];
            while (count($allImgs) < 4) {
                $allImgs[] = $placeholders[count($allImgs)];
            }
            $totalImgs = count($allImgs);
            ?>
            <div class="product-images" style="position:sticky;top:80px">

                <!-- Grille principale : 1 grande + 3 petites -->
                <div style="display:grid;grid-template-columns:1fr 1fr;grid-template-rows:1fr 1fr;gap:8px;border-radius:16px;overflow:hidden;aspect-ratio:1">

                    <!-- Image principale (occupe 2 lignes) -->
                    <div style="grid-row:span 2;position:relative;overflow:hidden;cursor:zoom-in;background:#f0f0f0"
                         onclick="openLightbox(0)" id="mainImgWrap">
                        <img src="<?= h($allImgs[0]['image_url']) ?>"
                             id="mainImg"
                             alt="<?= h($product['name']) ?>"
                             style="width:100%;height:100%;object-fit:cover;transition:transform .4s"
                             onmousemove="zoomImg(event,this)"
                             onmouseleave="this.style.transform='scale(1)'">
                        <?php if ($product['discount_percent'] > 0): ?>
                        <div style="position:absolute;top:10px;left:10px;background:var(--primary);color:#fff;font-weight:900;font-size:13px;padding:3px 10px;border-radius:20px">
                            -<?= $product['discount_percent'] ?>%
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Image 2 -->
                    <div style="position:relative;overflow:hidden;cursor:zoom-in;background:#f0f0f0"
                         onclick="openLightbox(1)">
                        <img src="<?= h($allImgs[1]['image_url']) ?>"
                             style="width:100%;height:100%;object-fit:cover;transition:transform .3s"
                             onmouseover="this.style.transform='scale(1.06)'"
                             onmouseout="this.style.transform='scale(1)'" alt="">
                    </div>

                    <!-- Image 3 + bouton "Voir toutes" si > 4 -->
                    <div style="position:relative;overflow:hidden;cursor:zoom-in;background:#f0f0f0"
                         onclick="openLightbox(2)">
                        <img src="<?= h($allImgs[2]['image_url']) ?>"
                             style="width:100%;height:100%;object-fit:cover;transition:transform .3s"
                             onmouseover="this.style.transform='scale(1.06)'"
                             onmouseout="this.style.transform='scale(1)'" alt="">
                        <?php if ($totalImgs > 4): ?>
                        <!-- Overlay "Voir tout" sur la 3e image -->
                        <div onclick="event.stopPropagation();openLightbox(3)"
                             style="position:absolute;inset:0;background:rgba(0,0,0,.55);display:flex;flex-direction:column;align-items:center;justify-content:center;cursor:pointer">
                            <span style="font-size:22px;color:#fff;font-weight:900">+<?= $totalImgs - 3 ?></span>
                            <span style="font-size:11px;color:rgba(255,255,255,.85);margin-top:2px">Voir tout</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Miniatures scrollables (toutes les images) -->
                <?php if ($totalImgs > 1): ?>
                <div style="display:flex;gap:8px;overflow-x:auto;padding:8px 0;margin-top:10px;scrollbar-width:thin" id="thumbsRow">
                    <?php foreach ($allImgs as $i => $img): ?>
                    <div id="thumb-<?= $i ?>"
                         onclick="setImg(<?= $i ?>)"
                         style="flex-shrink:0;width:68px;height:68px;border-radius:10px;overflow:hidden;cursor:pointer;
                                border:2px solid <?= $i === 0 ? 'var(--primary)' : 'var(--border)' ?>;
                                opacity:<?= $i === 0 ? '1' : '.65' ?>;transition:all .2s">
                        <img src="<?= h($img['image_url']) ?>" style="width:100%;height:100%;object-fit:cover" alt="">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- ══ LIGHTBOX ══════════════════════════════════════ -->
            <div id="lightbox" onclick="closeLightbox()"
                 style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.92);z-index:9999;align-items:center;justify-content:center;flex-direction:column">
                <button onclick="closeLightbox()" style="position:fixed;top:16px;right:20px;background:none;border:none;color:#fff;font-size:32px;cursor:pointer">✕</button>
                <button onclick="event.stopPropagation();changeImg(-1)" style="position:fixed;left:16px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,.15);border:none;color:#fff;font-size:36px;width:50px;height:50px;border-radius:50%;cursor:pointer">‹</button>
                <img id="lightboxImg" src="" style="max-width:90vw;max-height:88vh;border-radius:12px;object-fit:contain" onclick="event.stopPropagation()">
                <div id="lightboxCounter" style="color:rgba(255,255,255,.6);font-size:13px;margin-top:12px"></div>
                <button onclick="event.stopPropagation();changeImg(1)" style="position:fixed;right:16px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,.15);border:none;color:#fff;font-size:36px;width:50px;height:50px;border-radius:50%;cursor:pointer">›</button>
                <div style="display:flex;gap:8px;margin-top:14px;overflow-x:auto;max-width:90vw;padding:4px" onclick="event.stopPropagation()">
                    <?php foreach ($allImgs as $i => $img): ?>
                    <img src="<?= h($img['image_url']) ?>" onclick="setImg(<?= $i ?>)"
                         id="lbThumb-<?= $i ?>"
                         style="width:56px;height:56px;object-fit:cover;border-radius:8px;cursor:pointer;opacity:.5;border:2px solid transparent;transition:.2s;flex-shrink:0">
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Infos -->
            <div class="product-detail-info">
                <h1 class="product-detail-title"><?= h($product['name']) ?></h1>

                <div class="product-detail-rating">
                    <span class="stars" style="font-size:20px"><?= str_repeat('★', round($product['rating'])) ?><?= str_repeat('☆', 5 - round($product['rating'])) ?></span>
                    <span><?= number_format($product['rating'], 1) ?></span>
                    <span class="text-muted">(<?= number_format($product['review_count']) ?> avis)</span>
                    <span class="text-muted">• <?= number_format($product['sold_count']) ?> vendus</span>
                </div>

                <div class="product-detail-price product-price">
                    <span class="price-current"><?= formatPrice($product['price']) ?></span>
                    <?php if ($product['original_price'] && $product['original_price'] > $product['price']): ?>
                        <span class="price-original"><?= formatPrice($product['original_price']) ?></span>
                        <span class="price-discount">-<?= $product['discount_percent'] ?>%</span>
                    <?php endif; ?>
                </div>

                <?php if ($product['stock'] < 10 && $product['stock'] > 0): ?>
                    <div style="color:var(--danger);font-weight:700;font-size:13px">⚠️ Plus que <?= $product['stock'] ?> en stock !</div>
                <?php endif; ?>

                <!-- Variantes -->
                <?php
                $variantGroups = [];
                foreach ($product['variants'] as $v) {
                    $variantGroups[$v['variant_name']][] = $v;
                }
                ?>
                <?php foreach ($variantGroups as $groupName => $variants): ?>
                <div class="variants-section">
                    <h4><?= h($groupName) ?></h4>
                    <div class="variant-options">
                        <?php foreach ($variants as $v): ?>
                        <div class="variant-option" data-variant-id="<?= $v['id'] ?>">
                            <?= h($v['variant_value']) ?>
                            <?php if ($v['extra_price'] > 0): ?>
                                <span style="font-size:11px;opacity:.7">(+<?= formatPrice($v['extra_price']) ?>)</span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Quantité -->
                <div>
                    <h4 style="margin-bottom:10px;font-weight:700">Quantité</h4>
                    <div class="quantity-selector">
                        <button class="qty-btn qty-dec">−</button>
                        <input type="number" id="qty" class="qty-input" value="1" min="1" data-max="<?= $product['stock'] ?>">
                        <button class="qty-btn qty-inc">+</button>
                        <span class="text-muted" style="font-size:13px">Stock: <?= $product['stock'] ?></span>
                    </div>
                </div>

                <!-- Boutons action -->
                <div class="detail-actions" data-product-id="<?= $product['id'] ?>">
                    <button class="btn-primary add-to-cart-btn" data-id="<?= $product['id'] ?>">🛒 Ajouter au panier</button>
                    <button class="btn-secondary wishlist-btn <?= isInWishlist($id) ? 'active' : '' ?>" data-id="<?= $product['id'] ?>">
                        <?= isInWishlist($id) ? '❤️' : '🤍' ?>
                    </button>
                </div>

                <!-- Features -->
                <div class="product-features">
                    <ul>
                        <li>🚚 Livraison gratuite dès 19 000 FCFA</li>
                        <li>↩️ Retours gratuits sous 90 jours</li>
                        <li>🔒 Paiement 100% sécurisé</li>
                        <li>⭐ Garanti authentique</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Vidéo du produit -->
        <?php if (!empty($product['video_url'])): ?>
        <div style="margin-top:32px;border-top:1px solid var(--border);padding-top:24px">
            <h3 style="font-size:18px;font-weight:800;margin-bottom:14px">🎬 Vidéo du produit</h3>
            <?php
            $isYoutube = strpos($product['video_url'], 'youtube') !== false || strpos($product['video_url'], 'youtu.be') !== false;
            if ($isYoutube):
                preg_match('/(?:v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $product['video_url'], $m);
                $ytId = $m[1] ?? '';
            ?>
                <?php if ($ytId): ?>
                <div style="position:relative;padding-bottom:56.25%;height:0;border-radius:14px;overflow:hidden">
                    <iframe src="https://www.youtube.com/embed/<?= h($ytId) ?>"
                            style="position:absolute;top:0;left:0;width:100%;height:100%;border:none"
                            allowfullscreen></iframe>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <video src="<?= h($product['video_url']) ?>" controls
                       style="width:100%;max-height:480px;border-radius:14px;background:#000"></video>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Description -->
        <div style="margin-top:32px;border-top:1px solid var(--border);padding-top:24px">
            <h3 style="font-size:18px;font-weight:800;margin-bottom:12px">📋 Description</h3>
            <p style="color:var(--text-muted);line-height:1.8"><?= nl2br(h($product['description'])) ?></p>
        </div>

        <!-- ══ AVIS CLIENTS ═════════════════════════════════════ -->
        <div class="reviews-section" style="margin-top:36px;border-top:1px solid var(--border);padding-top:28px">
            <h3 style="font-size:20px;font-weight:900;margin-bottom:20px">💬 Avis clients</h3>

            <!-- Résumé note -->
            <div style="display:flex;gap:32px;align-items:center;background:#fafafa;border-radius:16px;padding:24px;margin-bottom:24px;flex-wrap:wrap">
                <!-- Note globale -->
                <div style="text-align:center;min-width:110px">
                    <div style="font-size:56px;font-weight:900;line-height:1;color:#111"><?= number_format((float)$product['rating'], 1) ?></div>
                    <div style="font-size:22px;color:#f59e0b;letter-spacing:2px;margin:4px 0">
                        <?php for ($s = 1; $s <= 5; $s++): ?>
                            <?= $s <= round($product['rating']) ? '★' : '☆' ?>
                        <?php endfor; ?>
                    </div>
                    <div style="font-size:13px;color:var(--text-muted)"><?= number_format($product['review_count']) ?> avis</div>
                </div>

                <!-- Barres par étoile -->
                <div style="flex:1;min-width:200px">
                    <?php for ($star = 5; $star >= 1; $star--):
                        $cnt  = $starCounts[$star] ?? 0;
                        $pct  = $totalReviews > 0 ? round($cnt / $totalReviews * 100) : 0;
                    ?>
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:7px">
                        <span style="font-size:13px;font-weight:700;color:#f59e0b;white-space:nowrap;width:22px;text-align:right"><?= $star ?>★</span>
                        <div style="flex:1;height:10px;background:#e5e7eb;border-radius:10px;overflow:hidden">
                            <div style="width:<?= $pct ?>%;height:100%;background:#f59e0b;border-radius:10px;transition:width .6s"></div>
                        </div>
                        <span style="font-size:12px;color:var(--text-muted);width:30px"><?= $cnt ?></span>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- ── Formulaire d'avis ────────────────────────── -->
            <?php if (isLoggedIn()): ?>
            <div style="background:#fff;border:2px solid var(--border);border-radius:16px;padding:24px;margin-bottom:28px">
                <h4 style="font-weight:800;font-size:16px;margin-bottom:16px">✍️ Laisser un avis</h4>

                <?php if ($reviewSuccess): ?>
                    <div class="flash-message flash-success">✅ <?= h($reviewSuccess) ?></div>
                <?php elseif ($reviewError): ?>
                    <div class="flash-message flash-error">❌ <?= h($reviewError) ?></div>
                <?php endif; ?>

                <form method="POST" id="reviewForm">
                    <?= csrfField() ?>
                    <input type="hidden" name="submit_review" value="1">

                    <!-- Étoiles interactives -->
                    <div style="margin-bottom:16px">
                        <label style="font-weight:700;font-size:14px;display:block;margin-bottom:8px">Votre note *</label>
                        <div class="star-picker" id="starPicker">
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                            <span class="star-pick" data-val="<?= $s ?>"
                                  style="font-size:36px;cursor:pointer;color:#d1d5db;transition:color .15s,transform .15s;display:inline-block">★</span>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="rating" id="ratingInput" value="0">
                        <div id="ratingLabel" style="font-size:13px;color:var(--text-muted);margin-top:4px">Cliquez sur une étoile</div>
                    </div>

                    <div class="form-group">
                        <label style="font-weight:700;font-size:14px">Titre (optionnel)</label>
                        <input type="text" name="review_title" class="form-control" placeholder="Résumez votre expérience" maxlength="200">
                    </div>
                    <div class="form-group">
                        <label style="font-weight:700;font-size:14px">Commentaire *</label>
                        <textarea name="comment" class="form-control" rows="3" placeholder="Partagez votre avis détaillé..." required></textarea>
                    </div>
                    <button type="submit" class="btn-primary" style="padding:11px 28px;border-radius:30px">
                        📤 Publier mon avis
                    </button>
                </form>
            </div>
            <?php else: ?>
            <div style="background:#f8f9fa;border-radius:12px;padding:18px;text-align:center;margin-bottom:24px">
                <a href="<?= SITE_URL ?>/pages/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
                   style="color:var(--primary);font-weight:700">Connectez-vous</a> pour laisser un avis.
            </div>
            <?php endif; ?>

            <!-- Liste des avis -->
            <?php if (!empty($product['reviews'])): ?>
            <div style="display:flex;flex-direction:column;gap:14px">
                <?php foreach ($product['reviews'] as $review): ?>
                <div class="review-card" style="border:1px solid var(--border);border-radius:14px;padding:18px;background:#fff">
                    <div class="review-header" style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px">
                        <div style="display:flex;align-items:center;gap:10px">
                            <div style="width:38px;height:38px;border-radius:50%;background:var(--primary);color:#fff;font-weight:900;font-size:16px;display:flex;align-items:center;justify-content:center">
                                <?= mb_strtoupper(mb_substr($review['user_name'], 0, 1)) ?>
                            </div>
                            <div>
                                <div style="font-weight:700;font-size:14px"><?= h($review['user_name']) ?></div>
                                <div style="font-size:18px;color:#f59e0b;line-height:1">
                                    <?= str_repeat('★', $review['rating']) ?><span style="color:#d1d5db"><?= str_repeat('★', 5 - $review['rating']) ?></span>
                                </div>
                            </div>
                        </div>
                        <div style="font-size:12px;color:var(--text-muted)"><?= timeAgo($review['created_at']) ?></div>
                    </div>
                    <?php if ($review['title']): ?>
                    <div style="font-weight:700;font-size:14px;margin-bottom:5px"><?= h($review['title']) ?></div>
                    <?php endif; ?>
                    <div style="font-size:14px;color:#374151;line-height:1.7"><?= h($review['comment']) ?></div>
                    <?php if ($review['is_verified']): ?>
                    <div style="font-size:12px;color:#16a34a;margin-top:8px;font-weight:700">✅ Achat vérifié</div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div style="text-align:center;padding:32px;color:var(--text-muted)">
                <div style="font-size:40px;margin-bottom:8px">💬</div>
                <div>Aucun avis pour l'instant. Soyez le premier !</div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Produits similaires -->
    <?php if (!empty($related)): ?>
    <div class="section">
        <div class="section-header">
            <h2 class="section-title">🔗 Produits similaires</h2>
        </div>
        <div class="products-grid">
            <?php foreach ($related as $p): ?>
                <?php include __DIR__ . '/../includes/product_card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// ── Données galerie ──────────────────────────────────────────
const IMGS = <?= json_encode(array_column($allImgs, 'image_url')) ?>;
let currentImgIndex = 0;

function setImg(i) {
    currentImgIndex = i;
    // Image principale (grande)
    const mainImg = document.getElementById('mainImg');
    if (mainImg) mainImg.src = IMGS[i];
    // Miniatures
    document.querySelectorAll('[id^="thumb-"]').forEach((t, idx) => {
        t.style.borderColor = idx === i ? 'var(--primary)' : 'var(--border)';
        t.style.opacity     = idx === i ? '1' : '.65';
    });
    // Scroll miniature active dans la vue
    const activeThumb = document.getElementById('thumb-' + i);
    if (activeThumb) activeThumb.scrollIntoView({behavior:'smooth', block:'nearest', inline:'center'});
    // Lightbox
    const lbImg = document.getElementById('lightboxImg');
    if (lbImg) {
        lbImg.src = IMGS[i];
        document.getElementById('lightboxCounter').textContent = (i + 1) + ' / ' + IMGS.length;
        document.querySelectorAll('[id^="lbThumb-"]').forEach((t, idx) => {
            t.style.opacity     = idx === i ? '1' : '.45';
            t.style.borderColor = idx === i ? '#fff' : 'transparent';
        });
    }
}

function changeImg(dir) {
    setImg((currentImgIndex + dir + IMGS.length) % IMGS.length);
}

// Zoom au survol
function zoomImg(e, img) {
    const rect = img.getBoundingClientRect();
    const x = ((e.clientX - rect.left) / rect.width  * 100).toFixed(1);
    const y = ((e.clientY - rect.top)  / rect.height * 100).toFixed(1);
    img.style.transformOrigin = `${x}% ${y}%`;
    img.style.transform = 'scale(1.8)';
}

// Lightbox
function openLightbox(i) {
    const lb = document.getElementById('lightbox');
    lb.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    setImg(i);
}
function closeLightbox() {
    document.getElementById('lightbox').style.display = 'none';
    document.body.style.overflow = '';
}

// Navigation clavier
document.addEventListener('keydown', e => {
    if (document.getElementById('lightbox').style.display === 'flex') {
        if (e.key === 'ArrowLeft')  changeImg(-1);
        if (e.key === 'ArrowRight') changeImg(1);
        if (e.key === 'Escape')     closeLightbox();
    }
});

// ── Étoiles interactives ─────────────────────────────────────
const stars  = document.querySelectorAll('.star-pick');
const rInput = document.getElementById('ratingInput');
const rLabel = document.getElementById('ratingLabel');
const labels = ['', 'Très mauvais 😞', 'Mauvais 😕', 'Moyen 😐', 'Bien 😊', 'Excellent ! 🤩'];

stars.forEach(star => {
    star.addEventListener('mouseover', () => highlightStars(+star.dataset.val));
    star.addEventListener('mouseout',  () => highlightStars(+rInput.value));
    star.addEventListener('click', () => {
        rInput.value = star.dataset.val;
        rLabel.textContent = labels[+star.dataset.val];
        rLabel.style.color = '#f59e0b';
        highlightStars(+star.dataset.val);
    });
});

function highlightStars(val) {
    stars.forEach((s, i) => {
        s.style.color     = i < val ? '#f59e0b' : '#d1d5db';
        s.style.transform = i < val ? 'scale(1.15)' : 'scale(1)';
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
