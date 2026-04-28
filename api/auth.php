<?php
// api/auth.php
require_once __DIR__ . '/../includes/functions.php';
startSession();

$action = $_GET['action'] ?? '';

if ($action === 'logout') {
    session_destroy();
    header('Location: ' . SITE_URL);
    exit;
}
