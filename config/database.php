<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'pharma_gestion');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
define('APP_NAME', 'PharmaGest');

function db() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die('<div style="font-family:sans-serif;padding:2rem;color:#c0392b;background:#fdf0f0;border-left:4px solid #e74c3c;margin:2rem">
                <strong>Erreur de connexion à la base de données</strong><br>
                Vérifiez vos paramètres dans <code>config/database.php</code><br><br>
                <small>' . htmlspecialchars($e->getMessage()) . '</small>
            </div>');
        }
    }
    return $pdo;
}

function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

function flash($msg, $type = 'success') {
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function formatPrice($n) { return number_format($n, 2, ',', ' ') . ' DH'; }
function formatDate($d)  { return $d ? date('d/m/Y', strtotime($d)) : '—'; }

session_start();
