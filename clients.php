<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add' || $action === 'edit') {
        $data = [trim($_POST['nom']), trim($_POST['prenom']), trim($_POST['telephone']), trim($_POST['email']), $_POST['date_naissance'] ?: null, trim($_POST['adresse'])];
        if ($action === 'add') {
            $pdo->prepare("INSERT INTO clients (nom,prenom,telephone,email,date_naissance,adresse) VALUES (?,?,?,?,?,?)")->execute($data);
            flash('Client ajouté.');
        } else {
            $data[] = (int)$_POST['id'];
            $pdo->prepare("UPDATE clients SET nom=?,prenom=?,telephone=?,email=?,date_naissance=?,adresse=? WHERE id=?")->execute($data);
            flash('Client mis à jour.');
        }
    }
    if ($action === 'delete') {
        $pdo->prepare("DELETE FROM clients WHERE id=?")->execute([(int)$_POST['id']]);
        flash('Client supprimé.', 'danger');
    }
    header('Location: clients.php'); exit;
}

$search = trim($_GET['q'] ?? '');
$where = '1=1'; $params = [];
if ($search) { $where = "(c.nom LIKE ? OR c.prenom LIKE ? OR c.telephone LIKE ?)"; $params = ["%$search%","%$search%","%$search%"]; }
$stmt = $pdo->prepare("SELECT c.*, COUNT(v.id) as nb_achats, COALESCE(SUM(v.total),0) as total_achats FROM clients c LEFT JOIN ventes v ON v.client_id=c.id WHERE $where GROUP BY c.id ORDER BY c.nom");
$stmt->execute($params);
$clients = $stmt->fetchAll();
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Clients — <?= APP_NAME ?></title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php require_once 'includes/sidebar.php'; ?>
<div class="main">
    <div class="topbar">
        <div class="topbar-title">Clients</div>
        <div class="topbar-actions">
            <span class="topbar-date"><?= date('d/m/Y') ?></span>
            <button class="btn btn-primary btn-sm" onclick="openModal('modalAdd')">+ Ajouter</button>
        </div>
    </div>
    <div class="content">
        <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?>"><?= h($flash['msg']) ?></div>
        <?php endif; ?>

        <div class="page-header">
            <div>
                <div class="page-title">Clients</div>
                <div class="page-sub"><?= count($clients) ?> client(s)</div>
            </div>
            <form method="GET">
                <div class="search-bar">
                    <span>🔍</span>
                    <input type="text" name="q" placeholder="Rechercher un client..." value="<?= h($search) ?>">
                </div>
            </form>
        </div>

        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Client</th><th>Téléphone</th><th>Email</th><th>Date naissance</th><th>Achats</th><th>Total</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                    <?php if (empty($clients)): ?>
                    <tr><td colspan="7" class="text-center" style="padding:3rem">
                        <div class="empty-state"><span class="empty-state-icon">👥</span><p>Aucun client trouvé</p></div>
                    </td></tr>
                    <?php endif; ?>
                    <?php foreach ($clients as $c): ?>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px">
                                <div style="width:36px;height:36px;border-radius:50%;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:0.8rem;font-weight:700;flex-shrink:0">
                                    <?= strtoupper(substr($c['nom'],0,1).substr($c['prenom'],0,1)) ?>
                                </div>
                                <div>
                                    <div style="font-weight:600;color:var(--text)"><?= h($c['nom'].' '.$c['prenom']) ?></div>
                                    <?php if ($c['adresse']): ?><div style="font-size:0.75rem;color:var(--text3)"><?= h($c['adresse']) ?></div><?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td><?= $c['telephone'] ? h($c['telephone']) : '—' ?></td>
                        <td class="text-muted"><?= $c['email'] ? h($c['email']) : '—' ?></td>
                        <td class="text-muted"><?= formatDate($c['date_naissance']) ?></td>
                        <td><span class="badge badge-info"><?= $c['nb_achats'] ?> achat(s)</span></td>
                        <td><strong class="text-success"><?= formatPrice($c['total_achats']) ?></strong></td>
                        <td>
                            <div class="flex gap-1">
                                <button class="btn btn-outline btn-icon btn-sm" onclick='openEdit(<?= json_encode($c) ?>)' title="Modifier">✏️</button>
                                <button class="btn btn-danger btn-icon btn-sm" onclick="confirmDelete(<?= $c['id'] ?>,'<?= h($c['nom']) ?>')" title="Supprimer">🗑️</button>
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

<div class="modal-overlay" id="modalAdd" style="display:none" onclick="if(event.target===this)closeModal('modalAdd')">
<div class="modal">
    <div class="modal-header">
        <div class="modal-title">Ajouter un client</div>
        <button class="modal-close" onclick="closeModal('modalAdd')">✕</button>
    </div>
    <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="modal-body">
            <div class="form-row">
                <div class="form-group"><label class="form-label">Nom <span>*</span></label><input type="text" name="nom" class="form-control" required></div>
                <div class="form-group"><label class="form-label">Prénom</label><input type="text" name="prenom" class="form-control"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">Téléphone</label><input type="text" name="telephone" class="form-control"></div>
                <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">Date de naissance</label><input type="date" name="date_naissance" class="form-control"></div>
                <div class="form-group"><label class="form-label">Adresse</label><input type="text" name="adresse" class="form-control"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal('modalAdd')">Annuler</button>
            <button type="submit" class="btn btn-primary">👤 Ajouter</button>
        </div>
    </form>
</div>
</div>

<div class="modal-overlay" id="modalEdit" style="display:none" onclick="if(event.target===this)closeModal('modalEdit')">
<div class="modal">
    <div class="modal-header">
        <div class="modal-title">Modifier le client</div>
        <button class="modal-close" onclick="closeModal('modalEdit')">✕</button>
    </div>
    <form method="POST">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="edit_id">
        <div class="modal-body">
            <div class="form-row">
                <div class="form-group"><label class="form-label">Nom <span>*</span></label><input type="text" name="nom" id="edit_nom" class="form-control" required></div>
                <div class="form-group"><label class="form-label">Prénom</label><input type="text" name="prenom" id="edit_prenom" class="form-control"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">Téléphone</label><input type="text" name="telephone" id="edit_tel" class="form-control"></div>
                <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" id="edit_email" class="form-control"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">Date de naissance</label><input type="date" name="date_naissance" id="edit_ddn" class="form-control"></div>
                <div class="form-group"><label class="form-label">Adresse</label><input type="text" name="adresse" id="edit_adresse" class="form-control"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal('modalEdit')">Annuler</button>
            <button type="submit" class="btn btn-primary">💾 Enregistrer</button>
        </div>
    </form>
</div>
</div>

<form method="POST" id="deleteForm" style="display:none">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="delete_id">
</form>

<script>
function openModal(id){document.getElementById(id).style.display='flex';document.body.style.overflow='hidden'}
function closeModal(id){document.getElementById(id).style.display='none';document.body.style.overflow=''}
function openEdit(c){
    document.getElementById('edit_id').value=c.id;
    document.getElementById('edit_nom').value=c.nom||'';
    document.getElementById('edit_prenom').value=c.prenom||'';
    document.getElementById('edit_tel').value=c.telephone||'';
    document.getElementById('edit_email').value=c.email||'';
    document.getElementById('edit_ddn').value=c.date_naissance||'';
    document.getElementById('edit_adresse').value=c.adresse||'';
    openModal('modalEdit');
}
function confirmDelete(id,nom){
    if(confirm('Supprimer "'+nom+'" ?')){document.getElementById('delete_id').value=id;document.getElementById('deleteForm').submit()}
}
</script>
</body>
</html>
