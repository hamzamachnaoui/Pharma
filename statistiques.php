<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
$pdo = db();

$periode = $_GET['periode'] ?? '30';

$ca_total    = $pdo->query("SELECT COALESCE(SUM(total),0) FROM ventes")->fetchColumn();
$ca_mois     = $pdo->query("SELECT COALESCE(SUM(total),0) FROM ventes WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())")->fetchColumn();
$nb_ventes   = $pdo->query("SELECT COUNT(*) FROM ventes")->fetchColumn();
$nb_clients  = $pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn();
$panier_moy  = $nb_ventes > 0 ? $ca_total / $nb_ventes : 0;
$nb_meds     = $pdo->query("SELECT COUNT(*) FROM medicaments")->fetchColumn();

$ca_courbe = $pdo->prepare("
    SELECT DATE(created_at) as jour, SUM(total) as total, COUNT(*) as nb
    FROM ventes
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
    GROUP BY DATE(created_at)
    ORDER BY jour ASC
");
$ca_courbe->execute([$periode]);
$courbe = $ca_courbe->fetchAll();

$labels_courbe = array_column($courbe, 'jour');
$data_courbe   = array_column($courbe, 'total');
$data_nb       = array_column($courbe, 'nb');

$categories = $pdo->query("
    SELECT COALESCE(m.categorie,'Non classé') as cat, SUM(vi.quantite) as total
    FROM vente_items vi
    JOIN medicaments m ON vi.medicament_id = m.id
    GROUP BY cat
    ORDER BY total DESC
    LIMIT 8
")->fetchAll();

$top10 = $pdo->query("
    SELECT m.nom, SUM(vi.quantite) as qte, SUM(vi.quantite * vi.prix_unitaire) as ca
    FROM vente_items vi
    JOIN medicaments m ON vi.medicament_id = m.id
    GROUP BY m.id
    ORDER BY qte DESC
    LIMIT 10
")->fetchAll();

$ca_mensuel = $pdo->query("
    SELECT DATE_FORMAT(created_at,'%Y-%m') as mois,
           DATE_FORMAT(created_at,'%b %Y') as label,
           SUM(total) as total, COUNT(*) as nb
    FROM ventes
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY mois
    ORDER BY mois ASC
")->fetchAll();

$palette = ['#1a6fdb','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#ec4899','#84cc16'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Statistiques — <?= APP_NAME ?></title>
<link rel="stylesheet" href="assets/style.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<style>
.chart-wrap{position:relative;height:320px;width:100%}
.kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.75rem}
.kpi{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:1.3rem;text-align:center;box-shadow:var(--shadow)}
.kpi-icon{font-size:1.6rem;margin-bottom:0.5rem}
.kpi-val{font-family:'Outfit',sans-serif;font-size:1.5rem;font-weight:700;color:var(--text);line-height:1}
.kpi-lbl{font-size:0.75rem;color:var(--text3);margin-top:0.35rem;font-weight:500;text-transform:uppercase;letter-spacing:0.05em}
.periode-tabs{display:flex;gap:0.4rem;background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius-sm);padding:4px}
.periode-tab{padding:0.45rem 1rem;border-radius:6px;font-size:0.82rem;font-weight:500;cursor:pointer;text-decoration:none;color:var(--text3);transition:all 0.2s}
.periode-tab.active,.periode-tab:hover{background:var(--surface);color:var(--primary);box-shadow:var(--shadow)}
</style>
</head>
<body>
<?php require_once 'includes/sidebar.php'; ?>
<div class="main">
    <div class="topbar">
        <div class="topbar-title">Statistiques & Analyses</div>
        <div class="topbar-actions">
            <span class="topbar-date"><?= date('d/m/Y') ?></span>
            <a href="rapport.php" class="btn btn-primary btn-sm">📄 Exporter PDF</a>
        </div>
    </div>
    <div class="content">

        <div class="kpi-grid">
            <div class="kpi">
                <div class="kpi-icon">💰</div>
                <div class="kpi-val" style="color:var(--success)"><?= formatPrice($ca_total) ?></div>
                <div class="kpi-lbl">CA total</div>
            </div>
            <div class="kpi">
                <div class="kpi-icon">📅</div>
                <div class="kpi-val" style="color:var(--primary)"><?= formatPrice($ca_mois) ?></div>
                <div class="kpi-lbl">CA ce mois</div>
            </div>
            <div class="kpi">
                <div class="kpi-icon">🧾</div>
                <div class="kpi-val"><?= $nb_ventes ?></div>
                <div class="kpi-lbl">Total ventes</div>
            </div>
            <div class="kpi">
                <div class="kpi-icon">🛒</div>
                <div class="kpi-val"><?= formatPrice($panier_moy) ?></div>
                <div class="kpi-lbl">Panier moyen</div>
            </div>
            <div class="kpi">
                <div class="kpi-icon">👥</div>
                <div class="kpi-val"><?= $nb_clients ?></div>
                <div class="kpi-lbl">Clients</div>
            </div>
            <div class="kpi">
                <div class="kpi-icon">💊</div>
                <div class="kpi-val"><?= $nb_meds ?></div>
                <div class="kpi-lbl">Médicaments</div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <div class="card-title">📈 Évolution du chiffre d'affaires</div>
                <div class="periode-tabs">
                    <a href="?periode=7"  class="periode-tab <?= $periode=='7'?'active':'' ?>">7j</a>
                    <a href="?periode=30" class="periode-tab <?= $periode=='30'?'active':'' ?>">30j</a>
                    <a href="?periode=90" class="periode-tab <?= $periode=='90'?'active':'' ?>">90j</a>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($courbe)): ?>
                <div class="empty-state"><span class="empty-state-icon">📊</span><p>Aucune donnée sur cette période</p></div>
                <?php else: ?>
                <div class="chart-wrap"><canvas id="chartCourbe"></canvas></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid-2">
            <div class="card">
                <div class="card-header"><div class="card-title">🍩 Ventes par catégorie</div></div>
                <div class="card-body">
                    <?php if (empty($categories)): ?>
                    <div class="empty-state"><span class="empty-state-icon">📦</span><p>Aucune donnée</p></div>
                    <?php else: ?>
                    <div class="chart-wrap" style="height:280px"><canvas id="chartDoughnut"></canvas></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><div class="card-title">📊 CA mensuel (12 mois)</div></div>
                <div class="card-body">
                    <?php if (empty($ca_mensuel)): ?>
                    <div class="empty-state"><span class="empty-state-icon">📊</span><p>Aucune donnée</p></div>
                    <?php else: ?>
                    <div class="chart-wrap" style="height:280px"><canvas id="chartMensuel"></canvas></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card mt-2">
            <div class="card-header"><div class="card-title">🏆 Top 10 médicaments les plus vendus</div></div>
            <div class="card-body">
                <?php if (empty($top10)): ?>
                <div class="empty-state"><span class="empty-state-icon">💊</span><p>Aucune vente enregistrée</p></div>
                <?php else: ?>
                <div class="chart-wrap" style="height:<?= count($top10) * 42 ?>px"><canvas id="chartTop"></canvas></div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($top10)): ?>
        <div class="card mt-2">
            <div class="card-header"><div class="card-title">📋 Détail des performances</div></div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>#</th><th>Médicament</th><th>Quantité vendue</th><th>CA généré</th><th>Part CA</th></tr></thead>
                    <tbody>
                    <?php $ca_top_total = array_sum(array_column($top10,'ca')); ?>
                    <?php foreach ($top10 as $i => $m): ?>
                    <tr>
                        <td><div style="width:26px;height:26px;border-radius:6px;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:0.75rem;font-weight:700"><?= $i+1 ?></div></td>
                        <td><strong><?= h($m['nom']) ?></strong></td>
                        <td><span class="badge badge-info"><?= $m['qte'] ?> unités</span></td>
                        <td><strong class="text-success"><?= formatPrice($m['ca']) ?></strong></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px">
                                <?php $pct = $ca_top_total > 0 ? round($m['ca']/$ca_top_total*100) : 0; ?>
                                <div class="progress-bar" style="flex:1"><div class="progress-fill" style="width:<?= $pct ?>%;background:var(--primary)"></div></div>
                                <span style="font-size:0.78rem;font-weight:600;color:var(--text2);min-width:32px"><?= $pct ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<script>
Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
Chart.defaults.color = '#94a3b8';

<?php if (!empty($courbe)): ?>
new Chart(document.getElementById('chartCourbe'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_map(
            function($d) { return date('d/m', strtotime($d));}, $labels_courbe)) ?>,
        datasets: [{
            label: 'CA (DH)',
            data: <?= json_encode(array_map('floatval', $data_courbe)) ?>,
            borderColor: '#1a6fdb',
            backgroundColor: 'rgba(26,111,219,0.08)',
            borderWidth: 2.5,
            pointBackgroundColor: '#1a6fdb',
            pointRadius: 4,
            pointHoverRadius: 6,
            fill: true,
            tension: 0.4
        },{
            label: 'Nb ventes',
            data: <?= json_encode(array_map('intval', $data_nb)) ?>,
            borderColor: '#10b981',
            backgroundColor: 'transparent',
            borderWidth: 2,
            pointBackgroundColor: '#10b981',
            pointRadius: 3,
            pointHoverRadius: 5,
            fill: false,
            tension: 0.4,
            yAxisID: 'y2'
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { position: 'top' }, tooltip: { callbacks: { label: ctx => ctx.dataset.label === 'CA (DH)' ? ctx.dataset.label + ': ' + parseFloat(ctx.raw).toFixed(2) + ' DH' : ctx.dataset.label + ': ' + ctx.raw } } },
        scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { callback: v => v + ' DH' } },
            y2: { position: 'right', beginAtZero: true, grid: { display: false } },
            x: { grid: { display: false } }
        }
    }
});
<?php endif; ?>

<?php if (!empty($categories)): ?>
new Chart(document.getElementById('chartDoughnut'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($categories,'cat')) ?>,
        datasets: [{ data: <?= json_encode(array_map('intval', array_column($categories,'total'))) ?>, backgroundColor: <?= json_encode(array_slice($palette,0,count($categories))) ?>, borderWidth: 0, hoverOffset: 8 }]
    },
    options: {
        responsive: true, maintainAspectRatio: false, cutout: '65%',
        plugins: { legend: { position: 'right', labels: { boxWidth: 12, padding: 16 } } }
    }
});
<?php endif; ?>

<?php if (!empty($ca_mensuel)): ?>
new Chart(document.getElementById('chartMensuel'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($ca_mensuel,'label')) ?>,
        datasets: [{ label: 'CA mensuel (DH)', data: <?= json_encode(array_map('floatval', array_column($ca_mensuel,'total'))) ?>, backgroundColor: 'rgba(26,111,219,0.8)', borderRadius: 6, borderSkipped: false }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { callback: v => v + ' DH' } }, x: { grid: { display: false } } }
    }
});
<?php endif; ?>

<?php if (!empty($top10)): ?>
new Chart(document.getElementById('chartTop'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($top10,'nom')) ?>,
        datasets: [{ label: 'Unités vendues', data: <?= json_encode(array_map('intval', array_column($top10,'qte'))) ?>, backgroundColor: <?= json_encode(array_map(function($i) use ($palette){ return $palette[$i % count($palette)]; }, range(0,count($top10)-1))) ?>, borderRadius: 6, borderSkipped: false }]
    },
    options: {
        indexAxis: 'y',
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' } }, y: { grid: { display: false } } }
    }
});
<?php endif; ?>

document.querySelectorAll('.progress-fill').forEach(el => {
    const w = el.style.width; el.style.width = '0';
    setTimeout(() => el.style.width = w, 200);
});
</script>
</body>
</html>
