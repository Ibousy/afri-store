<?php
// ============================================================
//  PAYTECH — IPN (Instant Payment Notification)
//  PayTech appelle ce endpoint en POST après chaque paiement.
// ============================================================
require_once __DIR__ . '/../includes/config.php';

// Ne rien afficher d'autre que la réponse JSON
header('Content-Type: application/json');

// Lire le corps de la requête
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

// Accepter aussi les données en POST classique
if (empty($data)) {
    $data = $_POST;
}

// Journaliser pour le débogage (désactiver en prod)
if (PAYTECH_SANDBOX) {
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    file_put_contents(
        $logDir . '/paytech_ipn.log',
        date('[Y-m-d H:i:s] ') . $raw . PHP_EOL,
        FILE_APPEND
    );
}

// ── Vérification de base ─────────────────────────────────────
$typeEvent  = $data['type_event']  ?? '';
$refCommand = $data['ref_command'] ?? '';
$customField = $data['custom_field'] ?? '';

if (empty($refCommand)) {
    http_response_code(400);
    echo json_encode(['error' => 'ref_command manquant']);
    exit;
}

// ── Retrouver la commande ────────────────────────────────────
$db   = getDB();
$stmt = $db->prepare("SELECT * FROM orders WHERE order_number = ?");
$stmt->execute([$refCommand]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    echo json_encode(['error' => 'Commande introuvable']);
    exit;
}

// Éviter le double-traitement
if ($order['payment_status'] === 'paid') {
    echo json_encode(['status' => 'already_paid']);
    exit;
}

// ── Traitement selon le type d'événement ────────────────────
if ($typeEvent === 'sale_complete') {
    // Paiement réussi
    $db->prepare("
        UPDATE orders
        SET payment_status = 'paid',
            order_status   = 'confirmed',
            updated_at     = NOW()
        WHERE order_number = ?
    ")->execute([$refCommand]);

    echo json_encode(['status' => 'ok']);
} elseif (in_array($typeEvent, ['sale_canceled', 'sale_failed'], true)) {
    // Paiement annulé / échoué
    $db->prepare("
        UPDATE orders
        SET payment_status = 'failed',
            order_status   = 'cancelled',
            updated_at     = NOW()
        WHERE order_number = ?
    ")->execute([$refCommand]);

    echo json_encode(['status' => 'cancelled']);
} else {
    // Événement inconnu — on ignore
    echo json_encode(['status' => 'ignored', 'type_event' => $typeEvent]);
}
