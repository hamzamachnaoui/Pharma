<?php
require_once 'config/database.php';

if (isset($_SESSION['user'])) {
    header('Location: index.php'); exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE username = ?");
    $stmt->execute([trim($_POST['username'])]);
    $user = $stmt->fetch();
    if ($user && password_verify($_POST['password'], $user['password'])) {
        $_SESSION['user'] = ['id' => $user['id'], 'nom' => $user['nom'], 'role' => $user['role']];
        header('Location: index.php'); exit;
    }
    $error = 'Identifiant ou mot de passe incorrect.';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Connexion — <?= APP_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--primary:#1a6fdb;--primary-dark:#1457b0;--border:#e2e8f0;--text:#0f172a;--text2:#475569;--text3:#94a3b8;--surface:#ffffff;--bg:#f0f4f8;--danger:#ef4444;--danger-light:#fee2e2}
body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1rem;position:relative;overflow:hidden}
.bg-pattern{position:fixed;inset:0;z-index:0;background:linear-gradient(135deg,#1a6fdb08 0%,#ffffff 50%,#e8f1fd 100%)}
.bg-circles{position:fixed;inset:0;z-index:0;overflow:hidden}
.circle{position:absolute;border-radius:50%;animation:float 8s ease-in-out infinite}
.circle:nth-child(1){width:400px;height:400px;background:radial-gradient(circle,rgba(26,111,219,0.06),transparent);top:-100px;right:-100px;animation-delay:0s}
.circle:nth-child(2){width:300px;height:300px;background:radial-gradient(circle,rgba(16,185,129,0.05),transparent);bottom:-80px;left:-80px;animation-delay:-3s}
.circle:nth-child(3){width:200px;height:200px;background:radial-gradient(circle,rgba(26,111,219,0.04),transparent);top:50%;left:10%;animation-delay:-5s}
@keyframes float{0%,100%{transform:translate(0,0) scale(1)}50%{transform:translate(20px,-20px) scale(1.05)}}
.login-wrap{position:relative;z-index:1;width:100%;max-width:420px}
.login-logo{text-align:center;margin-bottom:2rem}
.login-logo-icon{width:64px;height:64px;background:var(--primary);border-radius:18px;display:inline-flex;align-items:center;justify-content:center;font-size:1.8rem;margin-bottom:1rem;box-shadow:0 8px 24px rgba(26,111,219,0.25)}
.login-logo-name{font-family:'Outfit',sans-serif;font-size:1.6rem;font-weight:700;color:var(--text);letter-spacing:-0.03em}
.login-logo-sub{font-size:0.82rem;color:var(--text3);margin-top:4px}
.login-card{background:var(--surface);border-radius:20px;padding:2.5rem;box-shadow:0 20px 60px rgba(0,0,0,0.08),0 1px 3px rgba(0,0,0,0.06)}
.login-title{font-family:'Outfit',sans-serif;font-size:1.1rem;font-weight:700;color:var(--text);margin-bottom:0.4rem}
.login-sub{font-size:0.82rem;color:var(--text3);margin-bottom:2rem}
.form-group{margin-bottom:1.2rem}
.form-label{display:block;font-size:0.8rem;font-weight:600;color:var(--text);margin-bottom:0.45rem}
.input-wrap{position:relative}
.input-icon{position:absolute;left:0.9rem;top:50%;transform:translateY(-50%);font-size:1rem;pointer-events:none}
.form-control{width:100%;padding:0.72rem 0.9rem 0.72rem 2.5rem;border:1.5px solid var(--border);border-radius:10px;font-family:'Plus Jakarta Sans',sans-serif;font-size:0.875rem;color:var(--text);background:var(--surface);transition:border-color 0.2s,box-shadow 0.2s;outline:none}
.form-control:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(26,111,219,0.1)}
.form-control::placeholder{color:var(--text3)}
.btn-login{width:100%;padding:0.85rem;background:var(--primary);color:#fff;border:none;border-radius:10px;font-family:'Plus Jakarta Sans',sans-serif;font-size:0.95rem;font-weight:600;cursor:pointer;transition:background 0.2s,transform 0.15s,box-shadow 0.2s;margin-top:0.5rem}
.btn-login:hover{background:var(--primary-dark);box-shadow:0 8px 20px rgba(26,111,219,0.3);transform:translateY(-1px)}
.btn-login:active{transform:translateY(0)}
.alert-err{background:var(--danger-light);color:#991b1b;border-left:3px solid var(--danger);border-radius:8px;padding:0.75rem 1rem;font-size:0.85rem;font-weight:500;margin-bottom:1.25rem;display:flex;align-items:center;gap:8px}
.login-hint{text-align:center;margin-top:1.5rem;font-size:0.78rem;color:var(--text3);background:#f8fafc;border:1px solid var(--border);border-radius:10px;padding:0.9rem}
.login-hint strong{color:var(--text2)}
</style>
</head>
<body>
<div class="bg-pattern"></div>
<div class="bg-circles"><div class="circle"></div><div class="circle"></div><div class="circle"></div></div>
<div class="login-wrap">
    <div class="login-logo">
        <div class="login-logo-icon">💊</div>
        <div class="login-logo-name"><?= APP_NAME ?></div>
        <div class="login-logo-sub">Gestion Pharmacie</div>
    </div>
    <div class="login-card">
        <div class="login-title">Connexion</div>
        <div class="login-sub">Entrez vos identifiants pour accéder au tableau de bord</div>
        <?php if ($error): ?>
        <div class="alert-err">⚠️ <?= h($error) ?></div>
        <?php endif; ?>
        <form method="POST" autocomplete="on">
            <div class="form-group">
                <label class="form-label">Identifiant</label>
                <div class="input-wrap">
                    <span class="input-icon">👤</span>
                    <input type="text" name="username" class="form-control" placeholder="admin" required autocomplete="username" value="<?= h($_POST['username'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Mot de passe</label>
                <div class="input-wrap">
                    <span class="input-icon">🔒</span>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
                </div>
            </div>
            <button type="submit" class="btn-login">Se connecter →</button>
        </form>
    </div>
    <div class="login-hint">
        Compte démo — <strong>admin</strong> / <strong>admin123</strong>
    </div>
</div>
</body>
</html>
