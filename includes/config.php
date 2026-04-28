<?php
// ============================================================
//  CONFIGURATION DE LA BASE DE DONNÉES
//  Modifier ces valeurs selon votre environnement
// ============================================================

define('DB_HOST',    getenv('MYSQLHOST')     ?: 'localhost');
define('DB_USER',    getenv('MYSQLUSER')     ?: 'root');
define('DB_PASS',    getenv('MYSQLPASSWORD') ?: '');
define('DB_NAME',    getenv('MYSQLDATABASE') ?: 'temu_clone');
define('DB_PORT',    getenv('MYSQLPORT')     ?: '3306');
define('DB_CHARSET', 'utf8mb4');

define('SITE_NAME', getenv('SITE_NAME') ?: 'Afri-Store');
define('SITE_URL',  getenv('SITE_URL')  ?: 'http://localhost/temu-clone');
define('CURRENCY',      'FCFA');
define('CURRENCY_CODE', 'XOF');

// Session
define('SESSION_LIFETIME', 86400 * 7); // 7 jours

// ============================================================
//  PAYTECH
// ============================================================
define('PAYTECH_API_KEY',    '7ecc1ed2b9455f3a930ec8538ab721cf8aa5a58890c313c1a36ac9e5afd70e45');
define('PAYTECH_API_SECRET', '040155c59b1af73a8b1e13582c64250e6193f0b5682a70103d7b467e9a32d33f');
define('PAYTECH_SANDBOX',    true);  // false en production
define('PAYTECH_API_URL',    'https://paytech.sn/api/payment/request-payment');

define('PAYTECH_PUBLIC_URL', getenv('SITE_URL') ?: 'https://maybe-unsteady-broker.ngrok-free.dev/temu-clone');

// Upload
define('UPLOAD_DIR', __DIR__ . '/../assets/images/uploads/');
define('UPLOAD_URL', SITE_URL . '/assets/images/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// ============================================================
//  CONNEXION PDO
// ============================================================

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Connexion DB échouée: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}
