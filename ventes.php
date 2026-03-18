<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'new_vente') {
        $ids = $_POST['med_id'] ?? [];
        $qtes = $_POST['qte'] ?? [];
        $prix = $_POST['prix'] ?? [];
        if (!empty($ids)) {
            $total = 0;
            for ($i = 0; $i < count($ids); $i++) {
                $total += (float)$prix[$i] * (int)$qtes[$i];
            }
            $pdo->prepare("INSERT INTO ventes (client_id, total, note) VALUES (?,?,?)")
                ->execute([$_POST['client_id'] ?: null, $total, $_POST['note'] ?: null]);
            $vente_id = $pdo->lastInsertId();
            for ($i = 0; $i < count($ids); $i++) {
                $pdo->prepare("INSERT INTO vente_items (vente_id,medicament_id,quantite,prix_unitaire) VALUES (?,?,?,?)")
                    ->execute([$vente_id, (int)$ids[$i], (int)$qtes[$i], (float)$prix[$i]]);
                $pdo->prepare("UPDATE medicaments SET stock_actuel = GREATEST(0, stock_actuel - ?) WHERE id=?")
                    ->execute([(int)$qtes[$i], (int)$ids[$i]]);
            }
            flash("Vente #$vente_id enregistrée avec succès — Total : " . formatPrice($total));
        }
    }

    if ($action === 'delete') {
        $vente_id = (int)$_POST['id'];
        $items = $pdo->prepare("SELECT medicament_id, quantite FROM vente_items WHERE vente_id=?");
        $items->execute([$vente_id]);
        foreach ($items->fetchAll() as $item) {
            $pdo->prepare("UPDATE medicaments SET stock_actuel = stock_actuel + ? WHERE id=?")
                ->execute([$item['quantite'], $item['medicament_id']]);
        }
        $pdo->prepare("DELETE FROM ventes WHERE id=?")->execute([$vente_id]);
        flash('Vente annulée et stock restauré.', 'danger');
    }

    header('Location: ventes.php');
    exit;
}

$new = isset($_GET['action']) && $_GET['action'] === 'new';
$ventes = $pdo->query("SELECT v.*, c.nom as cn, c.prenom as cp FROM ventes v LEFT JOIN clients c ON v.client_id = c.id ORDER BY v.created_at DESC")->fetchAll();
$clients = $pdo->query("SELECT id, nom, prenom FROM clients ORDER BY nom")->fetchAll();
$meds = $pdo->query("SELECT id, nom, forme, dosage, prix_vente, stock_actuel FROM medicaments WHERE stock_actuel > 0 ORDER BY nom")->fetchAll();
$flash = getFlash();

$ca_today = $pdo->query("SELECT COALESCE(SUM(total),0) FROM ventes WHERE DATE(created_at)=CURDATE()")->fetchColumn();
$ca_month = $pdo->query("SELECT COALESCE(SUM(total),0) FROM ventes WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())")->fetchColumn();
$nb_today = $pdo->query("SELECT COUNT(*) FROM ventes WHERE DATE(created_at)=CURDATE()")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ventes — <?= APP_NAME ?></title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php require_once 'includes/sidebar.php'; ?>
<div class="main">
    <div class="topbar">
        <div class="topbar-title">Ventes & Caisse</div>
        <div class="topbar-actions">
            <span class="topbar-date"><?= date('d/m/Y') ?></span>
            <button class="btn btn-primary btn-sm" onclick="openModal('modalVente')">+ Nouvelle vente</button>
        </div>
    </div>
    <div class="content">
        <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>"><?= h($flash['msg']) ?></div>
        <?php endif; ?>

        <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:1.75rem">
            <div class="stat-card green">
                <div class="stat-icon green">💰</div>
                <div class="stat-label">CA aujourd'hui</div>
                <div class="stat-value" style="font-size:1.4rem"><?= formatPrice($ca_today) ?></div>
                <div class="stat-sub"><?= $nb_today ?> vente(s)</div>
            </div>
            <div class="stat-card blue">
                <div class="stat-icon blue">📅</div>
                <div class="stat-label">CA ce mois</div>
                <div class="stat-value" style="font-size:1.4rem"><?= formatPrice($ca_month) ?></div>
            </div>
            <div class="stat-card amber">
                <div class="stat-icon amber">🧾</div>
                <div class="stat-label">Total ventes</div>
                <div class="stat-value"><?= count($ventes) ?></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-title">🧾 Historique des ventes</div>
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>#</th><th>Client</th><th>Total</th><th>Note</th><th>Date</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php if (empty($ventes)): ?>
                    <tr><td colspan="6" class="text-center" style="padding:3rem">
                        <div class="empty-state"><span class="empty-state-icon">🧾</span><p>Aucune vente enregistrée</p></div>
                    </td></tr>
                    <?php endif; ?>
                    <?php foreach ($ventes as $v): ?>
                    <tr>
                        <td><strong>#<?= $v['id'] ?></strong></td>
                        <td><?= $v['cn'] ? h($v['cn'].' '.$v['cp']) : '<span class="text-muted">Anonyme</span>' ?></td>
                        <td><strong class="text-success"><?= formatPrice($v['total']) ?></strong></td>
                        <td class="text-muted"><?= $v['note'] ? h($v['note']) : '—' ?></td>
                        <td class="text-muted"><?= date('d/m/Y H:i', strtotime($v['created_at'])) ?></td>
                        <td>
                            <div class="flex gap-1">
                                <button class="btn btn-outline btn-icon btn-sm" onclick="voirDetail(<?= $v['id'] ?>)" title="Détail">👁️</button>
                                <button class="btn btn-danger btn-icon btn-sm" onclick="confirmDelete(<?= $v['id'] ?>)" title="Annuler">🗑️</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modalVente" style="display:<?= $new ? 'flex' : 'none' ?>" onclick="if(event.target===this)closeModal('modalVente')">
<div class="modal modal-lg">
    <div class="modal-header">
        <div class="modal-title">🧾 Nouvelle vente</div>
        <button class="modal-close" onclick="closeModal('modalVente')">✕</button>
    </div>
    <form method="POST">
        <input type="hidden" name="action" value="new_vente">
        <div class="modal-body">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Client</label>
                    <select name="client_id" class="form-control">
                        <option value="">— Client anonyme —</option>
                        <?php foreach($clients as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= h($c['nom'].' '.$c['prenom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Note / Ordonnance</label>
                    <input type="text" name="note" class="form-control" placeholder="Ex: Ordonnance Dr. Alami">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Médicaments <span>*</span></label>
                <div id="lignes"></div>
                <button type="button" class="btn btn-outline btn-sm mt-1" onclick="addLigne()">+ Ajouter un médicament</button>
            </div>

            <div style="background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius-sm);padding:1rem;margin-top:1rem">
                <div class="flex items-center" style="justify-content:space-between">
                    <span style="font-size:0.875rem;color:var(--text2)">Total à payer</span>
                    <span id="total_display" style="font-family:'Outfit',sans-serif;font-size:1.4rem;font-weight:700;color:var(--success)">0,00 DH</span>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal('modalVente')">Annuler</button>
            <button type="submit" class="btn btn-success">✅ Valider la vente</button>
        </div>
    </form>
</div>
</div>

<div class="modal-overlay" id="modalDetail" style="display:none" onclick="if(event.target===this)closeModal('modalDetail')">
<div class="modal">
    <div class="modal-header">
        <div class="modal-title" id="detail_title">Détail vente</div>
        <button class="modal-close" onclick="closeModal('modalDetail')">✕</button>
    </div>
    <div class="modal-body" id="detail_content">Chargement...</div>
</div>
</div>

<form method="POST" id="deleteForm" style="display:none">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="delete_id">
</form>

<script>
const meds = <?= json_encode($meds) ?>;
let ligne = 0;

function addLigne() {
    const d = document.getElementById('lignes');
    const opts = meds.map(m => `<option value="${m.id}" data-prix="${m.prix_vente}" data-stock="${m.stock_actuel}">${m.nom} ${m.forme ? '('+m.forme+')' : ''} — ${parseFloat(m.prix_vente).toFixed(2)} DH (stock: ${m.stock_actuel})</option>`).join('');
    d.insertAdjacentHTML('beforeend', `
    <div class="flex items-center gap-1 mb-1" id="ligne_${ligne}" style="flex-wrap:wrap">
        <select name="med_id[]" class="form-control" style="flex:2;min-width:200px" onchange="setPrix(this,${ligne})" required>
            <option value="">— Choisir —</option>${opts}
        </select>
        <input type="number" name="qte[]" id="qte_${ligne}" class="form-control" style="width:80px" min="1" value="1" onchange="calcTotal()" required>
        <input type="number" name="prix[]" id="prix_${ligne}" class="form-control" style="width:100px" step="0.01" min="0" placeholder="Prix" onchange="calcTotal()" required>
        <button type="button" class="btn btn-danger btn-icon btn-sm" onclick="removeLigne(${ligne})">✕</button>
    </div>`);
    ligne++;
}

function setPrix(sel, i) {
    const opt = sel.options[sel.selectedIndex];
    document.getElementById('prix_'+i).value = opt.dataset.prix || '';
    calcTotal();
}

function removeLigne(i) {
    document.getElementById('ligne_'+i)?.remove();
    calcTotal();
}

function calcTotal() {
    let t = 0;
    document.querySelectorAll('[name="qte[]"]').forEach((q, i) => {
        const p = document.querySelectorAll('[name="prix[]"]')[i];
        t += (parseFloat(q.value)||0) * (parseFloat(p?.value)||0);
    });
    document.getElementById('total_display').textContent = t.toFixed(2).replace('.',',') + ' DH';
}

function openModal(id){document.getElementById(id).style.display='flex';document.body.style.overflow='hidden'}
function closeModal(id){document.getElementById(id).style.display='none';document.body.style.overflow=''}

function voirDetail(id) {
    document.getElementById('detail_title').textContent = 'Vente #' + id;
    document.getElementById('detail_content').innerHTML = '<p class="text-muted text-center" style="padding:2rem">Chargement...</p>';
    openModal('modalDetail');
    fetch('ajax_vente.php?id='+id)
        .then(r => r.text())
        .then(h => document.getElementById('detail_content').innerHTML = h)
        .catch(() => document.getElementById('detail_content').innerHTML = '<p class="text-danger text-center">Erreur de chargement</p>');
}

function confirmDelete(id) {
    if(confirm('Annuler cette vente ? Le stock sera restauré.')) {
        document.getElementById('delete_id').value = id;
        document.getElementById('deleteForm').submit();
    }
}

<?php if ($new): ?>addLigne();<?php endif; ?>
</script>
</body>
</html>
