<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
$pdo = db();

$total_meds     = $pdo->query("SELECT COUNT(*) FROM medicaments")->fetchColumn();
$stock_alerte   = $pdo->query("SELECT COUNT(*) FROM medicaments WHERE stock_actuel <= stock_min")->fetchColumn();
$total_clients  = $pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn();
$ca_today       = $pdo->query("SELECT COALESCE(SUM(total),0) FROM ventes WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$ca_month       = $pdo->query("SELECT COALESCE(SUM(total),0) FROM ventes WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())")->fetchColumn();
$total_ventes   = $pdo->query("SELECT COUNT(*) FROM ventes")->fetchColumn();
$total_fournisseurs = $pdo->query("SELECT COUNT(*) FROM fournisseurs")->fetchColumn();
$expiration     = $pdo->query("SELECT COUNT(*) FROM medicaments WHERE date_expiration <= DATE_ADD(NOW(), INTERVAL 30 DAY) AND date_expiration >= NOW()")->fetchColumn();

$alertes = $pdo->query("SELECT nom, stock_actuel, stock_min FROM medicaments WHERE stock_actuel <= stock_min ORDER BY stock_actuel ASC LIMIT 6")->fetchAll();
$recentes = $pdo->query("SELECT v.*, c.nom as client_nom, c.prenom as client_prenom FROM ventes v LEFT JOIN clients c ON v.client_id = c.id ORDER BY v.created_at DESC LIMIT 7")->fetchAll();
$top_meds = $pdo->query("SELECT m.nom, SUM(vi.quantite) as total_vendu FROM vente_items vi JOIN medicaments m ON vi.medicament_id = m.id GROUP BY m.id ORDER BY total_vendu DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tableau de bord — <?= APP_NAME ?></title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php require_once 'includes/sidebar.php'; ?>
<div class="main">
    <div class="topbar">
        <div class="topbar-title">Tableau de bord</div>
        <div class="topbar-actions">
            <span class="topbar-date"><?= date('d/m/Y') ?></span>
            <a href="ventes.php?action=new" class="btn btn-primary btn-sm">+ Nouvelle vente</a>
        </div>
    </div>
    <div class="content">

        <?php if ($stock_alerte > 0 || $expiration > 0): ?>
        <div class="alert alert-warning">
            ⚠️ <?= $stock_alerte ?> médicament(s) en rupture de stock
            <?= $expiration > 0 ? " · $expiration expirant dans 30 jours" : '' ?>
        </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-icon blue">💊</div>
                <div class="stat-label">Médicaments</div>
                <div class="stat-value"><?= $total_meds ?></div>
                <div class="stat-sub"><?= $stock_alerte ?> en alerte stock</div>
            </div>
            <div class="stat-card green">
                <div class="stat-icon green">💰</div>
                <div class="stat-label">CA aujourd'hui</div>
                <div class="stat-value"><?= formatPrice($ca_today) ?></div>
                <div class="stat-sub">Ce mois : <?= formatPrice($ca_month) ?></div>
            </div>
            <div class="stat-card amber">
                <div class="stat-icon amber">🧾</div>
                <div class="stat-label">Total ventes</div>
                <div class="stat-value"><?= $total_ventes ?></div>
                <div class="stat-sub">Toutes périodes confondues</div>
            </div>
            <div class="stat-card cyan">
                <div class="stat-icon cyan">👥</div>
                <div class="stat-label">Clients</div>
                <div class="stat-value"><?= $total_clients ?></div>
                <div class="stat-sub"><?= $total_fournisseurs ?> fournisseur(s)</div>
            </div>
        </div>

        <div class="grid-2">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">🚨 Alertes stock</div>
                    <a href="medicaments.php" class="btn btn-outline btn-sm">Voir tout</a>
                </div>
                <div class="card-body">
                    <?php if (empty($alertes)): ?>
                    <div class="empty-state"><span class="empty-state-icon">✅</span><p>Aucune alerte de stock</p></div>
                    <?php else: ?>
                    <?php foreach ($alertes as $a): ?>
                    <div style="margin-bottom:1rem">
                        <div class="flex items-center gap-2 mb-1" style="justify-content:space-between">
                            <span style="font-size:0.875rem;font-weight:600;color:var(--text)"><?= h($a['nom']) ?></span>
                            <span class="badge <?= $a['stock_actuel'] == 0 ? 'badge-danger' : 'badge-warning' ?>">
                                <?= $a['stock_actuel'] ?> / <?= $a['stock_min'] ?>
                            </span>
                        </div>
                        <div class="progress-bar">
                            <?php $pct = $a['stock_min'] > 0 ? min(100, round($a['stock_actuel'] / $a['stock_min'] * 100)) : 0; ?>
                            <div class="progress-fill" style="width:<?= $pct ?>%;background:<?= $a['stock_actuel'] == 0 ? 'var(--danger)' : 'var(--warning)' ?>"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title">🏆 Top médicaments vendus</div>
                </div>
                <div class="card-body">
                    <?php if (empty($top_meds)): ?>
                    <div class="empty-state"><span class="empty-state-icon">📦</span><p>Aucune vente enregistrée</p></div>
                    <?php else: ?>
                    <?php foreach ($top_meds as $i => $m): ?>
                    <div class="flex items-center gap-2" style="margin-bottom:0.85rem">
                        <div style="width:24px;height:24px;border-radius:6px;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:0.75rem;font-weight:700;flex-shrink:0"><?= $i + 1 ?></div>
                        <div style="flex:1;font-size:0.875rem;font-weight:500;color:var(--text)"><?= h($m['nom']) ?></div>
                        <span class="badge badge-info"><?= $m['total_vendu'] ?> vendus</span>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card mt-2">
            <div class="card-header">
                <div class="card-title">🧾 Ventes récentes</div>
                <a href="ventes.php" class="btn btn-outline btn-sm">Voir tout</a>
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>#</th><th>Client</th><th>Total</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentes as $v): ?>
                    <tr>
                        <td><strong>#<?= $v['id'] ?></strong></td>
                        <td><?= $v['client_nom'] ? h($v['client_nom'] . ' ' . $v['client_prenom']) : '<span class="text-muted">Client anonyme</span>' ?></td>
                        <td><strong class="text-success"><?= formatPrice($v['total']) ?></strong></td>
                        <td class="text-muted"><?= date('d/m/Y H:i', strtotime($v['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentes)): ?>
                    <tr><td colspan="4" class="text-center text-muted" style="padding:2rem">Aucune vente</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded',()=>{
    document.querySelectorAll('.progress-fill').forEach(el=>{
        const w=el.style.width;el.style.width='0';
        setTimeout(()=>el.style.width=w,100);
    });
});
</script>
</body>
</html>
