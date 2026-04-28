<?php
// ============================================================
//  PAGE RÉSULTAT PAIEMENT MOBILE
//  Paytech redirige ici après succès ou annulation.
//  L'app Flutter détecte l'URL et agit en conséquence.
// ============================================================
$status = $_GET['status'] ?? 'cancel';
$ref    = $_GET['ref']    ?? '';
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $status === 'success' ? 'Paiement réussi' : 'Paiement annulé' ?></title>
<style>
  body { font-family: sans-serif; display:flex; flex-direction:column; align-items:center;
         justify-content:center; min-height:100vh; margin:0; background:#f5f5f5; }
  .icon { font-size: 64px; margin-bottom: 16px; }
  h1 { font-size: 22px; margin: 0 0 8px; }
  p  { color: #666; font-size: 14px; }
</style>
</head>
<body>
<?php if ($status === 'success'): ?>
  <div class="icon">✅</div>
  <h1>Paiement réussi !</h1>
  <p>Commande <?= htmlspecialchars($ref) ?> confirmée.</p>
  <p>Vous pouvez fermer cette fenêtre.</p>
<?php else: ?>
  <div class="icon">❌</div>
  <h1>Paiement annulé</h1>
  <p>Votre commande n'a pas été traitée.</p>
  <p>Vous pouvez fermer cette fenêtre.</p>
<?php endif; ?>
</body>
</html>
