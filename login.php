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
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
    --blue:#2563eb;--blue-dark:#1d4ed8;--blue-light:#eff6ff;
    --green:#10b981;--text:#111827;--text2:#6b7280;--border:#e5e7eb;
    --white:#ffffff;--bg:#f9fafb;--red:#ef4444;--red-bg:#fef2f2;
}
html,body{height:100%}
body{font-family:'Inter',sans-serif;background:var(--bg);display:flex;min-height:100vh}

.left{
    flex:0 0 420px;background:var(--white);display:flex;flex-direction:column;
    justify-content:center;padding:3rem;border-right:1px solid var(--border);
    position:relative;overflow:hidden;
}
.left::before{
    content:'';position:absolute;bottom:-120px;left:-80px;
    width:320px;height:320px;border-radius:50%;
    background:radial-gradient(circle,rgba(37,99,235,0.06),transparent 70%);
    pointer-events:none;
}
.left::after{
    content:'';position:absolute;top:-80px;right:-60px;
    width:220px;height:220px;border-radius:50%;
    background:radial-gradient(circle,rgba(16,185,129,0.07),transparent 70%);
    pointer-events:none;
}

.right{
    flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;
    position:relative;overflow:hidden;
    background:linear-gradient(145deg,#0f172a 0%,#1e3a5f 50%,#0c2340 100%);
}

.brand{margin-bottom:2.5rem;position:relative;z-index:1}
.brand-icon{
    width:54px;height:54px;background:var(--blue);border-radius:14px;
    display:flex;align-items:center;justify-content:center;
    font-size:1.5rem;margin-bottom:1rem;
    box-shadow:0 6px 20px rgba(37,99,235,0.3);
}
.brand-name{font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:800;color:var(--text);letter-spacing:-.03em}
.brand-sub{font-size:.75rem;color:var(--text2);margin-top:3px}

.form-head{margin-bottom:1.75rem;position:relative;z-index:1}
.form-title{font-family:'Syne',sans-serif;font-size:1.15rem;font-weight:700;color:var(--text);margin-bottom:.3rem}
.form-sub{font-size:.78rem;color:var(--text2)}

.fg{margin-bottom:1.1rem;position:relative;z-index:1}
.fl{display:block;font-size:.75rem;font-weight:600;color:var(--text);margin-bottom:.38rem}
.iw{position:relative}
.ico{position:absolute;left:.8rem;top:50%;transform:translateY(-50%);font-size:.9rem;pointer-events:none;opacity:.5}
.fc{
    width:100%;padding:.65rem .85rem .65rem 2.3rem;
    border:1.5px solid var(--border);border-radius:9px;
    font-family:'Inter',sans-serif;font-size:.85rem;color:var(--text);
    background:var(--bg);outline:none;transition:all .18s;
}
.fc:focus{border-color:var(--blue);background:var(--white);box-shadow:0 0 0 3px rgba(37,99,235,.1)}
.fc::placeholder{color:#d1d5db}

.btn{
    width:100%;padding:.75rem;background:var(--blue);color:#fff;border:none;
    border-radius:9px;font-family:'Inter',sans-serif;font-size:.9rem;font-weight:600;
    cursor:pointer;transition:all .18s;margin-top:.25rem;position:relative;z-index:1;
    display:flex;align-items:center;justify-content:center;gap:8px;
}
.btn:hover{background:var(--blue-dark);box-shadow:0 6px 18px rgba(37,99,235,.3);transform:translateY(-1px)}
.btn:active{transform:translateY(0)}
.btn svg{transition:transform .2s}
.btn:hover svg{transform:translateX(3px)}

.err{
    background:var(--red-bg);color:#b91c1c;border-left:3px solid var(--red);
    border-radius:8px;padding:.65rem .9rem;font-size:.8rem;font-weight:500;
    margin-bottom:1.1rem;display:flex;align-items:center;gap:7px;
    position:relative;z-index:1;
}

.hint{
    margin-top:1.4rem;padding:.75rem 1rem;background:var(--bg);
    border:1px solid var(--border);border-radius:9px;
    font-size:.73rem;color:var(--text2);text-align:center;
    position:relative;z-index:1;
}
.hint strong{color:var(--text);font-family:'Syne',sans-serif;font-size:.78rem}

.divider{height:1px;background:var(--border);margin:1.5rem 0;position:relative;z-index:1}

.right-inner{text-align:center;padding:2rem;position:relative;z-index:1;max-width:420px}

.stats{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:2rem}
.stat{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:14px;padding:1.25rem;text-align:center}
.stat-val{font-family:'Syne',sans-serif;font-size:1.6rem;font-weight:800;color:#fff;line-height:1}
.stat-lbl{font-size:.7rem;color:rgba(255,255,255,.45);margin-top:.4rem;text-transform:uppercase;letter-spacing:.06em}

.features{display:flex;flex-direction:column;gap:.7rem;text-align:left}
.feat{display:flex;align-items:center;gap:.75rem;padding:.75rem 1rem;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);border-radius:11px}
.feat-ico{width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:.95rem;flex-shrink:0}
.feat-ico.b{background:rgba(37,99,235,.25)}
.feat-ico.g{background:rgba(16,185,129,.25)}
.feat-ico.a{background:rgba(245,158,11,.25)}
.feat-ico.v{background:rgba(139,92,246,.25)}
.feat-text{font-size:.8rem;font-weight:500;color:rgba(255,255,255,.75)}

.right-title{font-family:'Syne',sans-serif;font-size:1.6rem;font-weight:800;color:#fff;margin-bottom:.5rem;line-height:1.15}
.right-sub{font-size:.82rem;color:rgba(255,255,255,.45);margin-bottom:2rem}

.dots{position:absolute;inset:0;background-image:radial-gradient(rgba(255,255,255,.05) 1px,transparent 1px);background-size:28px 28px;pointer-events:none}
.glow{position:absolute;width:350px;height:350px;border-radius:50%;background:radial-gradient(circle,rgba(37,99,235,.18),transparent 70%);top:50%;left:50%;transform:translate(-50%,-50%);pointer-events:none}

@media(max-width:900px){
    body{flex-direction:column}
    .left{flex:none;padding:2rem 1.5rem;border-right:none;border-bottom:1px solid var(--border)}
    .right{min-height:280px;padding:2rem}
    .stats{grid-template-columns:repeat(4,1fr)}
    .features{display:none}
}
</style>
</head>
<body>
<div class="left">
    <div class="brand">
        <div class="brand-icon">💊</div>
        <div class="brand-name"><?= APP_NAME ?></div>
        <div class="brand-sub">Système de gestion de pharmacie</div>
    </div>

    <div class="form-head">
        <div class="form-title">Bon retour 👋</div>
        <div class="form-sub">Connectez-vous pour accéder à votre espace</div>
    </div>

    <?php if ($error): ?>
    <div class="err">⚠️ <?= h($error) ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="on">
        <div class="fg">
            <label class="fl">Identifiant</label>
            <div class="iw">
                <span class="ico">👤</span>
                <input type="text" name="username" class="fc" placeholder="Entrez votre identifiant" required autocomplete="username" value="<?= h($_POST['username'] ?? '') ?>">
            </div>
        </div>
        <div class="fg">
            <label class="fl">Mot de passe</label>
            <div class="iw">
                <span class="ico">🔒</span>
                <input type="password" name="password" class="fc" placeholder="••••••••" required autocomplete="current-password">
            </div>
        </div>
        <button type="submit" class="btn">
            Se connecter
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </button>
    </form>

    <div class="divider"></div>

    <div class="hint">
        Compte démo — <strong>admin</strong> / <strong>admin123</strong>
    </div>
</div>

<div class="right">
    <div class="dots"></div>
    <div class="glow"></div>
    <div class="right-inner">
        <div class="right-title">Gérez votre<br>pharmacie simplement</div>
        <div class="right-sub">Une solution complète pour la gestion quotidienne</div>

        <div class="stats">
            <div class="stat"><div class="stat-val">100%</div><div class="stat-lbl">Sécurisé</div></div>
            <div class="stat"><div class="stat-val">24/7</div><div class="stat-lbl">Disponible</div></div>
            <div class="stat"><div class="stat-val">PDF</div><div class="stat-lbl">Rapports</div></div>
            <div class="stat"><div class="stat-val">∞</div><div class="stat-lbl">Produits</div></div>
        </div>

        <div class="features">
            <div class="feat"><div class="feat-ico b">📦</div><div class="feat-text">Gestion du stock & alertes de rupture</div></div>
            <div class="feat"><div class="feat-ico g">💰</div><div class="feat-text">Caisse & historique des ventes</div></div>
            <div class="feat"><div class="feat-ico a">📊</div><div class="feat-text">Statistiques & graphiques en temps réel</div></div>
            <div class="feat"><div class="feat-ico v">🖨️</div><div class="feat-text">Export & impression de rapports PDF</div></div>
        </div>
    </div>
</div>
</body>
</html>