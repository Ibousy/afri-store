<?php
require_once __DIR__ . '/../includes/functions.php';
startSession();
if (!isAdmin()) { header('Location: ' . SITE_URL . '/admin/login.php'); exit; }

$db     = getDB();
$action = $_GET['action'] ?? 'list';
$errors = [];
$success = '';

$imgUploadDir = UPLOAD_DIR . 'products/';
$vidUploadDir = UPLOAD_DIR . 'videos/';
$imgUploadUrl = UPLOAD_URL . 'products/';
$vidUploadUrl = UPLOAD_URL . 'videos/';

// ── Suppression produit ──────────────────────────────────────
if ($action === 'delete' && isset($_GET['id'])) {
    $db->prepare("UPDATE products SET is_active = 0 WHERE id = ?")->execute([(int)$_GET['id']]);
    header('Location: ' . SITE_URL . '/admin/products.php?success=deleted');
    exit;
}

// ── Suppression d'une image ──────────────────────────────────
if ($action === 'delete_image' && isset($_GET['image_id'])) {
    $imgId = (int)$_GET['image_id'];
    $pid   = (int)($_GET['product_id'] ?? 0);
    $img   = $db->prepare("SELECT image_url FROM product_images WHERE id = ?")->execute([$imgId]) ? null : null;
    $stmt  = $db->prepare("SELECT image_url FROM product_images WHERE id = ?");
    $stmt->execute([$imgId]);
    $img = $stmt->fetchColumn();
    // Supprimer le fichier local si c'est un upload
    if ($img && strpos($img, '/uploads/') !== false) {
        $localPath = __DIR__ . '/../' . ltrim(parse_url($img, PHP_URL_PATH), '/');
        $localPath = str_replace('/temu-clone/', '/', $localPath);
        if (file_exists($localPath)) @unlink($localPath);
    }
    $db->prepare("DELETE FROM product_images WHERE id = ?")->execute([$imgId]);
    header('Location: ?action=edit&id=' . $pid . '&success=image_deleted');
    exit;
}

// ── Définir image principale ─────────────────────────────────
if ($action === 'set_primary' && isset($_GET['image_id'])) {
    $imgId = (int)$_GET['image_id'];
    $pid   = (int)($_GET['product_id'] ?? 0);
    $db->prepare("UPDATE product_images SET is_primary = 0 WHERE product_id = ?")->execute([$pid]);
    $db->prepare("UPDATE product_images SET is_primary = 1 WHERE id = ?")->execute([$imgId]);
    header('Location: ?action=edit&id=' . $pid . '&success=primary_set');
    exit;
}

// ── Ajout / Édition ──────────────────────────────────────────
$product = null;
$productImages = [];

if (in_array($action, ['edit', 'add'])) {
    if ($action === 'edit' && isset($_GET['id'])) {
        $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([(int)$_GET['id']]);
        $product = $stmt->fetch();

        $stmt2 = $db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC");
        $stmt2->execute([(int)$_GET['id']]);
        $productImages = $stmt2->fetchAll();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $fields = [
            'category_id'      => (int)($_POST['category_id'] ?? 0),
            'name'             => sanitize($_POST['name'] ?? ''),
            'slug'             => sanitize($_POST['slug'] ?? ''),
            'description'      => sanitize($_POST['description'] ?? ''),
            'price'            => (float)($_POST['price'] ?? 0),
            'original_price'   => !empty($_POST['original_price']) ? (float)$_POST['original_price'] : null,
            'discount_percent' => (int)($_POST['discount_percent'] ?? 0),
            'stock'            => (int)($_POST['stock'] ?? 0),
            'brand'            => sanitize($_POST['brand'] ?? ''),
            'is_featured'      => isset($_POST['is_featured']) ? 1 : 0,
            'is_flash_sale'    => isset($_POST['is_flash_sale']) ? 1 : 0,
            'video_url'        => '',
        ];

        if (empty($fields['name']) || empty($fields['slug']) || $fields['price'] <= 0) {
            $errors[] = 'Nom, slug et prix sont obligatoires.';
        }

        // ── Vidéo : upload fichier OU URL ────────────────────
        if (!empty($_FILES['video']['tmp_name']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
            $vExt = strtolower(pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION));
            if (!in_array($vExt, ['mp4', 'webm', 'mov', 'avi'])) {
                $errors[] = 'Format vidéo non supporté (mp4, webm, mov, avi).';
            } elseif ($_FILES['video']['size'] > 100 * 1024 * 1024) {
                $errors[] = 'Vidéo trop lourde (max 100 Mo).';
            } else {
                if (!is_dir($vidUploadDir)) mkdir($vidUploadDir, 0755, true);
                $vName = uniqid('vid_') . '.' . $vExt;
                if (move_uploaded_file($_FILES['video']['tmp_name'], $vidUploadDir . $vName)) {
                    $fields['video_url'] = $vidUploadUrl . $vName;
                }
            }
        } elseif (!empty($_POST['video_url'])) {
            $fields['video_url'] = sanitize($_POST['video_url']);
        } elseif ($product && !empty($product['video_url'])) {
            $fields['video_url'] = $product['video_url'];
        }

        if (empty($errors)) {
            if ($action === 'add') {
                $cols = implode(',', array_keys($fields));
                $phs  = implode(',', array_fill(0, count($fields), '?'));
                $db->prepare("INSERT INTO products ($cols) VALUES ($phs)")->execute(array_values($fields));
                $productId = (int)$db->lastInsertId();
                $success = 'Produit créé ! Ajoutez maintenant les images.';
            } else {
                $productId = (int)$_GET['id'];
                $sets = implode('=?,', array_keys($fields)) . '=?';
                $vals = array_values($fields);
                $vals[] = $productId;
                $db->prepare("UPDATE products SET $sets WHERE id = ?")->execute($vals);
                $success = 'Produit mis à jour !';
            }

            // ── Upload images multiples ──────────────────────
            if (!empty($_FILES['images']['name'][0])) {
                if (!is_dir($imgUploadDir)) mkdir($imgUploadDir, 0755, true);
                $hasPrimary = $db->prepare("SELECT COUNT(*) FROM product_images WHERE product_id = ? AND is_primary = 1")->execute([$productId])
                              ? (int)$db->query("SELECT COUNT(*) FROM product_images WHERE product_id = $productId AND is_primary = 1")->fetchColumn()
                              : 0;
                $firstUpload = true;

                foreach ($_FILES['images']['tmp_name'] as $i => $tmpName) {
                    if (empty($tmpName) || $_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;
                    if ($_FILES['images']['size'][$i] > MAX_FILE_SIZE) continue;
                    $ext = strtolower(pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION));
                    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) continue;

                    $filename = uniqid('img_') . '.' . $ext;
                    if (move_uploaded_file($tmpName, $imgUploadDir . $filename)) {
                        $isPrimary = ($firstUpload && !$hasPrimary) ? 1 : 0;
                        $db->prepare("INSERT INTO product_images (product_id, image_url, is_primary) VALUES (?,?,?)")
                           ->execute([$productId, $imgUploadUrl . $filename, $isPrimary]);
                        $firstUpload = false;
                        $hasPrimary  = 1;
                    }
                }
            }

            // Recharger les images après upload
            $stmt2 = $db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC");
            $stmt2->execute([$productId]);
            $productImages = $stmt2->fetchAll();

            if ($action === 'add') {
                header('Location: ?action=edit&id=' . $productId . '&success=created');
                exit;
            }
        }
    }
}

// ── Liste ────────────────────────────────────────────────────
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 20;
$offset = ($page - 1) * $limit;
$search = sanitize($_GET['search'] ?? '');

$where  = ['p.is_active = 1'];
$params = [];
if ($search) { $where[] = 'p.name LIKE ?'; $params[] = "%$search%"; }

$total = $db->prepare("SELECT COUNT(*) FROM products p WHERE " . implode(' AND ', $where));
$total->execute($params);
$total = $total->fetchColumn();
$totalPages = ceil($total / $limit);

$stmt = $db->prepare("
    SELECT p.*, c.name AS cat_name, pi.image_url AS img
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
    WHERE " . implode(' AND ', $where) . "
    ORDER BY p.created_at DESC LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$products = $stmt->fetchAll();
$cats = $db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Produits — Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <style>
        .img-grid { display:flex;flex-wrap:wrap;gap:10px;margin-top:12px }
        .img-item { position:relative;width:100px;height:100px;border-radius:10px;overflow:hidden;border:2px solid var(--border) }
        .img-item.primary { border-color:var(--primary);box-shadow:0 0 0 3px rgba(255,77,0,.2) }
        .img-item img { width:100%;height:100%;object-fit:cover }
        .img-item .img-actions { position:absolute;inset:0;background:rgba(0,0,0,.5);display:none;flex-direction:column;align-items:center;justify-content:center;gap:4px }
        .img-item:hover .img-actions { display:flex }
        .img-item .primary-badge { position:absolute;top:4px;left:4px;background:var(--primary);color:#fff;font-size:9px;font-weight:800;padding:2px 6px;border-radius:4px }
        .upload-zone { border:2px dashed var(--border);border-radius:12px;padding:28px;text-align:center;cursor:pointer;transition:all .2s;background:#fafafa }
        .upload-zone:hover,.upload-zone.drag { border-color:var(--primary);background:#fff5f0 }
        .upload-zone input[type=file] { display:none }
        .preview-grid { display:flex;flex-wrap:wrap;gap:8px;margin-top:12px }
        .preview-item { position:relative;width:80px;height:80px;border-radius:8px;overflow:hidden;border:2px solid var(--border) }
        .preview-item img { width:100%;height:100%;object-fit:cover }
        .preview-item .rm { position:absolute;top:2px;right:2px;background:rgba(220,38,38,.9);color:#fff;border:none;border-radius:50%;width:18px;height:18px;font-size:10px;cursor:pointer;display:flex;align-items:center;justify-content:center }
        .video-preview { margin-top:12px;border-radius:10px;overflow:hidden;max-width:100% }
    </style>
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="admin-logo">🛍️ <?= SITE_NAME ?><br><small style="font-size:12px;opacity:.6">Administration</small></div>
        <nav class="admin-menu">
            <a href="<?= SITE_URL ?>/admin/index.php">📊 Dashboard</a>
            <a href="<?= SITE_URL ?>/admin/products.php" style="color:white;background:rgba(255,255,255,.15)">📦 Produits</a>
            <a href="<?= SITE_URL ?>/admin/orders.php">🛒 Commandes</a>
            <a href="<?= SITE_URL ?>/admin/payments.php">💳 Paiements</a>
            <a href="<?= SITE_URL ?>/admin/users.php">👥 Utilisateurs</a>
            <a href="<?= SITE_URL ?>/admin/categories.php">🗂️ Catégories</a>
            <a href="<?= SITE_URL ?>/admin/coupons.php">🎟️ Coupons</a>
            <a href="<?= SITE_URL ?>" style="margin-top:20px;border-top:1px solid rgba(255,255,255,.1);padding-top:16px">🏠 Voir le site</a>
            <a href="<?= SITE_URL ?>/api/auth.php?action=logout" style="color:#FF6B6B">🚪 Déconnexion</a>
        </nav>
    </aside>

    <main class="admin-main">
        <?php if ($success): ?>
            <div class="flash-message flash-success">✅ <?= h($success) ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['success']) && $_GET['success'] === 'image_deleted'): ?>
            <div class="flash-message flash-success">✅ Image supprimée.</div>
        <?php endif; ?>
        <?php foreach ($errors as $e): ?>
            <div class="flash-message flash-error">❌ <?= h($e) ?></div>
        <?php endforeach; ?>

        <?php if (in_array($action, ['add', 'edit'])): ?>
        <!-- ══ FORMULAIRE PRODUIT ══════════════════════════════ -->
        <div style="display:flex;justify-content:space-between;margin-bottom:20px;align-items:center">
            <h1 style="font-size:22px;font-weight:900"><?= $action === 'add' ? '➕ Nouveau produit' : '✏️ Modifier : ' . h($product['name'] ?? '') ?></h1>
            <a href="<?= SITE_URL ?>/admin/products.php" class="btn-secondary" style="padding:10px 20px;border-radius:20px">← Retour</a>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <div style="display:grid;grid-template-columns:1fr 380px;gap:20px">

                <!-- Colonne gauche -->
                <div>
                    <div class="bg-white rounded p-3" style="margin-bottom:16px">
                        <h3 style="font-weight:800;margin-bottom:14px">📝 Informations</h3>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                            <div class="form-group">
                                <label>Nom du produit *</label>
                                <input type="text" name="name" class="form-control" value="<?= h($product['name'] ?? '') ?>" required
                                       oninput="document.querySelector('[name=slug]').value=this.value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9]+/g,'-')">
                            </div>
                            <div class="form-group">
                                <label>Slug (URL) *</label>
                                <input type="text" name="slug" class="form-control" value="<?= h($product['slug'] ?? '') ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Catégorie *</label>
                                <select name="category_id" class="form-control">
                                    <?php foreach ($cats as $c): ?>
                                        <option value="<?= $c['id'] ?>" <?= ($product['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= h($c['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Marque</label>
                                <input type="text" name="brand" class="form-control" value="<?= h($product['brand'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Prix actuel (€) *</label>
                                <input type="number" name="price" class="form-control" step="0.01" min="0.01" value="<?= h($product['price'] ?? '') ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Prix original (€)</label>
                                <input type="number" name="original_price" class="form-control" step="0.01" value="<?= h($product['original_price'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Réduction (%)</label>
                                <input type="number" name="discount_percent" class="form-control" min="0" max="99" value="<?= h($product['discount_percent'] ?? 0) ?>">
                            </div>
                            <div class="form-group">
                                <label>Stock</label>
                                <input type="number" name="stock" class="form-control" min="0" value="<?= h($product['stock'] ?? 0) ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="4"><?= h($product['description'] ?? '') ?></textarea>
                        </div>
                        <div style="display:flex;gap:20px">
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                                <input type="checkbox" name="is_featured" <?= ($product['is_featured'] ?? 0) ? 'checked' : '' ?>> ⭐ Produit vedette
                            </label>
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                                <input type="checkbox" name="is_flash_sale" <?= ($product['is_flash_sale'] ?? 0) ? 'checked' : '' ?>> ⚡ Flash Sale
                            </label>
                        </div>
                    </div>

                    <!-- VIDÉO -->
                    <div class="bg-white rounded p-3">
                        <h3 style="font-weight:800;margin-bottom:14px">🎬 Vidéo du produit</h3>
                        <?php if (!empty($product['video_url'])): ?>
                        <div class="video-preview">
                            <?php if (strpos($product['video_url'], 'youtube') !== false || strpos($product['video_url'], 'youtu.be') !== false): ?>
                                <?php
                                preg_match('/(?:v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $product['video_url'], $m);
                                $ytId = $m[1] ?? '';
                                ?>
                                <?php if ($ytId): ?>
                                <iframe src="https://www.youtube.com/embed/<?= h($ytId) ?>" style="width:100%;height:200px;border:none;border-radius:8px" allowfullscreen></iframe>
                                <?php endif; ?>
                            <?php else: ?>
                                <video src="<?= h($product['video_url']) ?>" controls style="width:100%;border-radius:8px;max-height:220px"></video>
                            <?php endif; ?>
                            <div style="font-size:12px;color:var(--text-muted);margin-top:6px;word-break:break-all"><?= h($product['video_url']) ?></div>
                        </div>
                        <p style="font-size:13px;color:var(--text-muted);margin:10px 0 4px">Remplacer la vidéo :</p>
                        <?php endif; ?>

                        <div style="display:grid;grid-template-columns:1fr;gap:10px;margin-top:8px">
                            <div>
                                <label style="font-size:13px;font-weight:700;display:block;margin-bottom:6px">📁 Upload fichier vidéo (mp4, webm, mov — max 100 Mo)</label>
                                <input type="file" name="video" accept="video/mp4,video/webm,video/quicktime,video/x-msvideo"
                                       class="form-control" style="padding:8px" id="videoFileInput"
                                       onchange="previewVideo(this)">
                                <video id="videoPreviewEl" controls style="display:none;width:100%;margin-top:10px;border-radius:8px;max-height:200px"></video>
                            </div>
                            <div style="display:flex;align-items:center;gap:10px">
                                <div style="flex:1;height:1px;background:var(--border)"></div>
                                <span style="font-size:12px;color:var(--text-muted)">OU</span>
                                <div style="flex:1;height:1px;background:var(--border)"></div>
                            </div>
                            <div>
                                <label style="font-size:13px;font-weight:700;display:block;margin-bottom:6px">🔗 URL vidéo (YouTube, lien direct...)</label>
                                <input type="url" name="video_url" class="form-control" placeholder="https://youtube.com/watch?v=... ou lien direct"
                                       value="<?= !empty($product['video_url']) && strpos($product['video_url'], '/uploads/') === false ? h($product['video_url']) : '' ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Colonne droite : IMAGES -->
                <div>
                    <div class="bg-white rounded p-3">
                        <h3 style="font-weight:800;margin-bottom:14px">🖼️ Images du produit</h3>

                        <?php if (!empty($productImages)): ?>
                        <p style="font-size:13px;font-weight:700;margin-bottom:8px">Collection actuelle (<?= count($productImages) ?> image<?= count($productImages) > 1 ? 's' : '' ?>) :</p>
                        <div class="img-grid" style="margin-bottom:16px">
                            <?php foreach ($productImages as $img): ?>
                            <div class="img-item <?= $img['is_primary'] ? 'primary' : '' ?>">
                                <img src="<?= h($img['image_url']) ?>" alt="">
                                <?php if ($img['is_primary']): ?>
                                    <div class="primary-badge">★ Principale</div>
                                <?php endif; ?>
                                <div class="img-actions">
                                    <?php if (!$img['is_primary']): ?>
                                    <a href="?action=set_primary&image_id=<?= $img['id'] ?>&product_id=<?= $product['id'] ?>"
                                       style="background:var(--primary);color:#fff;font-size:10px;padding:3px 8px;border-radius:4px;text-decoration:none;font-weight:700">★ Principal</a>
                                    <?php endif; ?>
                                    <a href="?action=delete_image&image_id=<?= $img['id'] ?>&product_id=<?= $product['id'] ?>"
                                       onclick="return confirm('Supprimer cette image ?')"
                                       style="background:rgba(220,38,38,.9);color:#fff;font-size:10px;padding:3px 8px;border-radius:4px;text-decoration:none;font-weight:700">🗑️</a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Zone d'upload -->
                        <div class="upload-zone" id="uploadZone" onclick="document.getElementById('imagesInput').click()"
                             ondragover="event.preventDefault();this.classList.add('drag')"
                             ondragleave="this.classList.remove('drag')"
                             ondrop="handleDrop(event)">
                            <div style="font-size:36px;margin-bottom:8px">📁</div>
                            <div style="font-weight:800;font-size:15px;margin-bottom:4px">Cliquez ou glissez vos images ici</div>
                            <div style="font-size:12px;color:var(--text-muted)">JPG, PNG, WEBP, GIF — max 5 Mo par image — sélection multiple</div>
                            <input type="file" name="images[]" id="imagesInput" multiple accept="image/jpeg,image/png,image/webp,image/gif"
                                   onchange="previewImages(this)">
                        </div>

                        <!-- Prévisualisation des fichiers sélectionnés -->
                        <div class="preview-grid" id="previewGrid"></div>
                        <div id="previewCount" style="font-size:12px;color:var(--text-muted);margin-top:6px"></div>
                    </div>

                    <button type="submit" class="btn-primary" style="width:100%;padding:14px;border-radius:12px;margin-top:14px;font-size:16px">
                        <?= $action === 'add' ? '✅ Créer le produit' : '💾 Enregistrer les modifications' ?>
                    </button>
                </div>
            </div>
        </form>

        <?php else: ?>
        <!-- ══ LISTE DES PRODUITS ══════════════════════════════ -->
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px">
            <h1 style="font-size:22px;font-weight:900">📦 Produits (<?= $total ?>)</h1>
            <div style="display:flex;gap:12px">
                <form method="GET">
                    <input type="text" name="search" value="<?= h($search) ?>" placeholder="Rechercher..." class="form-control" style="width:220px;padding:8px 12px">
                </form>
                <a href="?action=add" class="btn-primary" style="padding:10px 20px;border-radius:20px;white-space:nowrap">➕ Ajouter</a>
            </div>
        </div>
        <?php if (isset($_GET['success']) && $_GET['success'] === 'deleted'): ?>
            <div class="flash-message flash-success">✅ Produit supprimé.</div>
        <?php endif; ?>
        <div class="bg-white rounded" style="overflow:auto">
            <table class="data-table">
                <thead>
                    <tr><th>Image</th><th>Nom</th><th>Catégorie</th><th>Prix</th><th>Stock</th><th>Vendus</th><th>Note</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                    <tr>
                        <td><img src="<?= h($p['img'] ?: 'https://picsum.photos/seed/'.$p['id'].'/60/60') ?>" style="width:48px;height:48px;border-radius:6px;object-fit:cover"></td>
                        <td>
                            <div style="font-weight:700;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= h($p['name']) ?></div>
                            <?php if ($p['is_featured']): ?><span style="font-size:10px;background:var(--primary-light);color:var(--primary);padding:1px 6px;border-radius:4px">⭐</span><?php endif; ?>
                            <?php if ($p['is_flash_sale']): ?><span style="font-size:10px;background:#FFF3CD;color:#856404;padding:1px 6px;border-radius:4px">⚡</span><?php endif; ?>
                            <?php if (!empty($p['video_url'])): ?><span style="font-size:10px;background:#dbeafe;color:#1d4ed8;padding:1px 6px;border-radius:4px">🎬</span><?php endif; ?>
                        </td>
                        <td style="font-size:13px"><?= h($p['cat_name']) ?></td>
                        <td style="font-weight:800;color:var(--primary)"><?= formatPrice($p['price']) ?></td>
                        <td><span style="color:<?= $p['stock'] < 10 ? 'var(--danger)' : 'var(--success)' ?>;font-weight:700"><?= $p['stock'] ?></span></td>
                        <td style="font-size:13px"><?= number_format($p['sold_count']) ?></td>
                        <td style="color:var(--secondary)">★ <?= number_format($p['rating'], 1) ?></td>
                        <td>
                            <a href="?action=edit&id=<?= $p['id'] ?>" style="color:var(--primary);font-weight:700;font-size:13px">✏️</a>
                            <a href="<?= SITE_URL ?>/pages/product.php?id=<?= $p['id'] ?>" target="_blank" style="color:var(--text-muted);margin-left:8px;font-size:13px">👁️</a>
                            <a href="?action=delete&id=<?= $p['id'] ?>" onclick="return confirm('Supprimer ce produit ?')" style="color:var(--danger);margin-left:8px;font-size:13px">🗑️</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </main>
</div>

<script>
// Prévisualisation images sélectionnées
function previewImages(input) {
    const grid = document.getElementById('previewGrid');
    const count = document.getElementById('previewCount');
    grid.innerHTML = '';
    const files = Array.from(input.files);
    files.forEach((file, i) => {
        const reader = new FileReader();
        reader.onload = e => {
            const div = document.createElement('div');
            div.className = 'preview-item';
            div.innerHTML = `<img src="${e.target.result}"><button type="button" class="rm" onclick="removePreview(${i}, this)">✕</button>`;
            grid.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
    count.textContent = files.length ? `${files.length} image(s) sélectionnée(s)` : '';
}

// Glisser-déposer
function handleDrop(e) {
    e.preventDefault();
    document.getElementById('uploadZone').classList.remove('drag');
    const input = document.getElementById('imagesInput');
    const dt = new DataTransfer();
    Array.from(e.dataTransfer.files).filter(f => f.type.startsWith('image/')).forEach(f => dt.items.add(f));
    input.files = dt.files;
    previewImages(input);
}

// Prévisualisation vidéo
function previewVideo(input) {
    const vid = document.getElementById('videoPreviewEl');
    if (input.files[0]) {
        vid.src = URL.createObjectURL(input.files[0]);
        vid.style.display = 'block';
    }
}
</script>
</body>
</html>
