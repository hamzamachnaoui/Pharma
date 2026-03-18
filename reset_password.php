<?php
require_once 'config/database.php';
$pdo = db();

$hash_admin  = password_hash('admin123',   PASSWORD_DEFAULT);
$hash_pharma = password_hash('pharma123',  PASSWORD_DEFAULT);

$pdo->prepare("UPDATE utilisateurs SET password=? WHERE username='admin'")->execute([$hash_admin]);
$pdo->prepare("UPDATE utilisateurs SET password=? WHERE username='pharmacien'")->execute([$hash_pharma]);

echo '<div style="font-family:sans-serif;padding:2rem;max-width:400px;margin:3rem auto;background:#d1fae5;border:1px solid #6ee7b7;border-radius:12px;text-align:center">';
echo '<div style="font-size:2rem;margin-bottom:1rem">✅</div>';
echo '<strong style="font-size:1.1rem">Mots de passe réinitialisés !</strong><br><br>';
echo '<strong>admin</strong> → admin123<br>';
echo '<strong>pharmacien</strong> → pharma123<br><br>';
echo '<a href="login.php" style="display:inline-block;background:#10b981;color:#fff;padding:0.7rem 1.5rem;border-radius:8px;text-decoration:none;font-weight:600;margin-top:0.5rem">Se connecter →</a>';
echo '</div>';

echo '<div style="font-family:sans-serif;padding:0.5rem 2rem;max-width:400px;margin:0 auto;font-size:0.75rem;color:#ef4444;text-align:center">';
echo '⚠️ Supprimez ce fichier après utilisation !';
echo '</div>';
