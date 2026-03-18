<?php
require_once 'includes/auth.php';
$pdo = db();
$stock_alerte = $pdo->query("SELECT COUNT(*) FROM medicaments WHERE stock_actuel <= stock_min")->fetchColumn();
$current = basename($_SERVER['PHP_SELF'], '.php');
$user = $_SESSION['user'];
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <div class="sidebar-logo-icon">💊</div>
        <div>
            <div class="sidebar-logo-text"><?= APP_NAME ?></div>
            <div class="sidebar-logo-sub">Gestion Pharmacie</div>
        </div>
    </div>
    <div class="sidebar-section">Principal</div>
    <ul class="sidebar-nav">
        <li><a href="index.php" class="<?= $current==='index'?'active':'' ?>"><span class="nav-icon">📊</span> Tableau de bord</a></li>
        <li><a href="statistiques.php" class="<?= $current==='statistiques'?'active':'' ?>"><span class="nav-icon">📈</span> Statistiques</a></li>
    </ul>
    <div class="sidebar-section">Gestion</div>
    <ul class="sidebar-nav">
        <li>
            <a href="medicaments.php" class="<?= $current==='medicaments'?'active':'' ?>">
                <span class="nav-icon">💊</span> Médicaments
                <?php if ($stock_alerte > 0): ?><span class="nav-badge"><?= $stock_alerte ?></span><?php endif; ?>
            </a>
        </li>
        <li><a href="ventes.php" class="<?= $current==='ventes'?'active':'' ?>"><span class="nav-icon">🧾</span> Ventes & Caisse</a></li>
        <li><a href="fournisseurs.php" class="<?= $current==='fournisseurs'?'active':'' ?>"><span class="nav-icon">🚚</span> Fournisseurs</a></li>
        <li><a href="clients.php" class="<?= $current==='clients'?'active':'' ?>"><span class="nav-icon">👥</span> Clients</a></li>
    </ul>
    <div class="sidebar-section">Rapports</div>
    <ul class="sidebar-nav">
        <li><a href="rapport.php" class="<?= $current==='rapport'?'active':'' ?>"><span class="nav-icon">📄</span> Rapport PDF</a></li>
    </ul>
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-avatar"><?= strtoupper(substr($user['nom'],0,2)) ?></div>
            <div style="flex:1;min-width:0">
                <div class="sidebar-user-name"><?= h($user['nom']) ?></div>
                <div class="sidebar-user-role"><?= ucfirst($user['role']) ?></div>
            </div>
            <a href="logout.php" title="Déconnexion" style="color:rgba(255,255,255,0.3);text-decoration:none;font-size:1rem;transition:color 0.2s" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='rgba(255,255,255,0.3)'">⏻</a>
        </div>
    </div>
</aside>
