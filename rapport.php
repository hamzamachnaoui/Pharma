<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
$pdo = db();

$debut = $_GET['debut'] ?? date('Y-m-01');
$fin   = $_GET['fin']   ?? date('Y-m-d');

$ca       = $pdo->prepare("SELECT COALESCE(SUM(total),0) FROM ventes WHERE DATE(created_at) BETWEEN ? AND ?"); $ca->execute([$debut,$fin]); $ca = $ca->fetchColumn();
$nb       = $pdo->prepare("SELECT COUNT(*) FROM ventes WHERE DATE(created_at) BETWEEN ? AND ?"); $nb->execute([$debut,$fin]); $nb = $nb->fetchColumn();
$panier   = $nb > 0 ? $ca / $nb : 0;
$nb_meds  = $pdo->query("SELECT COUNT(*) FROM medicaments")->fetchColumn();
$ruptures = $pdo->query("SELECT COUNT(*) FROM medicaments WHERE stock_actuel = 0")->fetchColumn();
$alertes  = $pdo->query("SELECT COUNT(*) FROM medicaments WHERE stock_actuel <= stock_min AND stock_actuel > 0")->fetchColumn();

$ventes   = $pdo->prepare("SELECT v.*, CONCAT(COALESCE(c.nom,''),' ',COALESCE(c.prenom,'')) as client FROM ventes v LEFT JOIN clients c ON v.client_id=c.id WHERE DATE(v.created_at) BETWEEN ? AND ? ORDER BY v.created_at DESC LIMIT 20"); $ventes->execute([$debut,$fin]); $ventes = $ventes->fetchAll();
$top5     = $pdo->query("SELECT m.nom, SUM(vi.quantite) as qte, SUM(vi.quantite*vi.prix_unitaire) as ca FROM vente_items vi JOIN medicaments m ON vi.medicament_id=m.id GROUP BY m.id ORDER BY qte DESC LIMIT 5")->fetchAll();
$stock_ko = $pdo->query("SELECT nom, stock_actuel, stock_min FROM medicaments WHERE stock_actuel <= stock_min ORDER BY stock_actuel ASC LIMIT 10")->fetchAll();

$print = isset($_GET['print']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rapport — <?= APP_NAME ?></title>
<?php if (!$print): ?>
<link rel="stylesheet" href="assets/style.css">
<?php endif; ?>
<style>
<?php if ($print): ?>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',Arial,sans-serif;color:#1a1a2e;background:#fff;font-size:13px;line-height:1.6}
@page{margin:1.5cm}
@media print{.no-print{display:none!important}.page-break{page-break-before:always}}
<?php else: ?>
.rapport-wrap{max-width:800px;margin:0 auto}
@media print{.no-print{display:none!important}}
<?php endif; ?>
.rpt-header{background:linear-gradient(135deg,#1a6fdb,#0ea5e9);color:#fff;padding:2rem;border-radius:<?= $print?'0':'12px 12px 0 0' ?>}
.rpt-logo{font-size:1.4rem;font-weight:700;letter-spacing:-0.02em}
.rpt-sub{font-size:0.82rem;opacity:0.8;margin-top:4px}
.rpt-period{font-size:0.82rem;opacity:0.7;margin-top:8px}
.rpt-body{background:#fff;<?= $print?'':'border:1px solid #e2e8f0;border-top:none;border-radius:0 0 12px 12px;' ?>padding:2rem}
.section-title{font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:#94a3b8;margin:2rem 0 1rem;padding-bottom:0.5rem;border-bottom:2px solid #f1f5f9}
.section-title:first-child{margin-top:0}
.kpi-row{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:0.5rem}
.kpi{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:1rem;text-align:center}
.kpi-val{font-size:1.2rem;font-weight:700;color:#0f172a;line-height:1}
.kpi-lbl{font-size:0.7rem;color:#94a3b8;margin-top:4px;text-transform:uppercase;letter-spacing:0.05em}
table{width:100%;border-collapse:collapse;font-size:0.82rem;margin-top:0.5rem}
th{background:#f8fafc;padding:0.6rem 0.75rem;text-align:left;font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:#94a3b8;border-bottom:2px solid #e2e8f0}
td{padding:0.65rem 0.75rem;border-bottom:1px solid #f1f5f9;color:#475569}
td strong{color:#0f172a}
tr:last-child td{border-bottom:none}
.badge{display:inline-block;padding:2px 8px;border-radius:100px;font-size:0.7rem;font-weight:600}
.badge-ok{background:#d1fae5;color:#065f46}
.badge-warn{background:#fef3c7;color:#92400e}
.badge-err{background:#fee2e2;color:#991b1b}
.rpt-footer{margin-top:3rem;padding-top:1rem;border-top:1px solid #e2e8f0;font-size:0.72rem;color:#94a3b8;display:flex;justify-content:space-between}
</style>
</head>
<body>
<?php if (!$print): ?>
<div class="main" style="margin-left:260px">
    <div class="topbar no-print">
        <div class="topbar-title">Rapport PDF</div>
        <div class="topbar-actions">
            <form method="GET" style="display:flex;gap:0.5rem;align-items:center">
                <label style="font-size:0.82rem;color:var(--text3)">Du</label>
                <input type="date" name="debut" value="<?= $debut ?>" class="form-control" style="width:auto">
                <label style="font-size:0.82rem;color:var(--text3)">au</label>
                <input type="date" name="fin" value="<?= $fin ?>" class="form-control" style="width:auto">
                <button type="submit" class="btn btn-outline btn-sm">Filtrer</button>
            </form>
            <a href="?debut=<?= $debut ?>&fin=<?= $fin ?>&print=1" target="_blank" class="btn btn-primary btn-sm">🖨️ Imprimer / PDF</a>
        </div>
    </div>
    <div class="content">
    <div class="rapport-wrap">
<?php
require_once 'includes/sidebar.php';
endif;
?>

<div class="rpt-header">
    <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <div>
            <div class="rpt-logo">💊 <?= APP_NAME ?></div>
            <div class="rpt-sub">Application de Gestion de Pharmacie</div>
        </div>
        <div style="text-align:right">
            <div style="font-size:1rem;font-weight:700">Rapport d'activité</div>
            <div class="rpt-period">Période : <?= date('d/m/Y',strtotime($debut)) ?> — <?= date('d/m/Y',strtotime($fin)) ?></div>
            <div class="rpt-period">Généré le <?= date('d/m/Y à H:i') ?></div>
        </div>
    </div>
</div>

<div class="rpt-body">

    <div class="section-title">Résumé de la période</div>
    <div class="kpi-row">
        <div class="kpi"><div class="kpi-val"><?= formatPrice($ca) ?></div><div class="kpi-lbl">CA total</div></div>
        <div class="kpi"><div class="kpi-val"><?= $nb ?></div><div class="kpi-lbl">Ventes</div></div>
        <div class="kpi"><div class="kpi-val"><?= formatPrice($panier) ?></div><div class="kpi-lbl">Panier moyen</div></div>
        <div class="kpi"><div class="kpi-val"><?= $nb_meds ?></div><div class="kpi-lbl">Médicaments</div></div>
    </div>

    <?php if (!empty($top5)): ?>
    <div class="section-title">Top 5 médicaments vendus</div>
    <table>
        <thead><tr><th>#</th><th>Médicament</th><th>Quantité</th><th>CA généré</th></tr></thead>
        <tbody>
        <?php foreach ($top5 as $i => $m): ?>
        <tr>
            <td><?= $i+1 ?></td>
            <td><strong><?= h($m['nom']) ?></strong></td>
            <td><?= $m['qte'] ?> unités</td>
            <td><strong><?= formatPrice($m['ca']) ?></strong></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <?php if (!empty($ventes)): ?>
    <div class="section-title">Dernières ventes de la période</div>
    <table>
        <thead><tr><th>#</th><th>Client</th><th>Total</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach ($ventes as $v): ?>
        <tr>
            <td><strong>#<?= $v['id'] ?></strong></td>
            <td><?= trim($v['client']) ?: 'Anonyme' ?></td>
            <td><strong><?= formatPrice($v['total']) ?></strong></td>
            <td><?= date('d/m/Y H:i',strtotime($v['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <?php if (!empty($stock_ko)): ?>
    <div class="section-title <?= $print?'page-break':'' ?>">État du stock — Alertes</div>
    <table>
        <thead><tr><th>Médicament</th><th>Stock actuel</th><th>Stock min</th><th>Statut</th></tr></thead>
        <tbody>
        <?php foreach ($stock_ko as $s): ?>
        <tr>
            <td><strong><?= h($s['nom']) ?></strong></td>
            <td><?= $s['stock_actuel'] ?></td>
            <td><?= $s['stock_min'] ?></td>
            <td>
                <?php if ($s['stock_actuel']==0): ?>
                <span class="badge badge-err">Rupture</span>
                <?php else: ?>
                <span class="badge badge-warn">Alerte</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <div class="rpt-footer">
        <span><?= APP_NAME ?> — Rapport confidentiel</span>
        <span>Généré par <?= h($_SESSION['user']['nom'] ?? 'Système') ?></span>
    </div>
</div>

<?php if (!$print): ?>
    </div></div></div>
<?php else: ?>
<script>window.onload=()=>window.print()</script>
<?php endif; ?>
</body>
</html>
