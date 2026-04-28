<?php
require_once __DIR__ . '/../includes/functions.php';
startSession();
requireLogin();

$db = getDB();
$orderNumber = $_SESSION['paytech_order'] ?? null;

// Marquer la commande comme annulée si elle existe
if ($orderNumber) {
    $stmt = $db->prepare("SELECT id FROM orders WHERE order_number = ? AND user_id = ? AND payment_status = 'pending'");
    $stmt->execute([$orderNumber, $_SESSION['user_id']]);
    $order = $stmt->fetch();

    if ($order) {
        $db->prepare("
            UPDATE orders
            SET payment_status = 'failed',
                order_status   = 'cancelled',
                updated_at     = NOW()
            WHERE id = ?
        ")->execute([$order['id']]);
    }
    unset($_SESSION['paytech_order']);
}

$pageTitle = 'Paiement annulé';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width:640px;margin:60px auto;text-align:center">
    <div style="background:#fff;border-radius:16px;padding:48px 32px;box-shadow:0 4px 24px rgba(0,0,0,.08)">

        <div style="font-size:72px;margin-bottom:16px">❌</div>
        <h1 style="font-size:28px;font-weight:900;color:#ef4444;margin-bottom:8px">
            Paiement annulé
        </h1>
        <p style="font-size:16px;color:var(--text-muted);margin-bottom:28px">
            Votre paiement n'a pas abouti. Votre panier est intact — vous pouvez réessayer.
        </p>

        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
            <a href="<?= SITE_URL ?>/pages/checkout.php"
               style="background:var(--primary, #ff4d00);color:#fff;padding:12px 28px;border-radius:8px;font-weight:700;text-decoration:none">
                Réessayer le paiement
            </a>
            <a href="<?= SITE_URL ?>/pages/cart.php"
               style="background:#f1f1f1;color:#333;padding:12px 28px;border-radius:8px;font-weight:700;text-decoration:none">
                Retour au panier
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
