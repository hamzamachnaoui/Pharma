<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add' || $action === 'edit') {
        $data = [trim($_POST['nom']), trim($_POST['contact']), trim($_POST['telephone']), trim($_POST['email']), trim($_POST['adresse'])];
        if ($action === 'add') {
            $pdo->prepare("INSERT INTO fournisseurs (nom,contact,telephone,email,adresse) VALUES (?,?,?,?,?)")->execute($data);
            flash('Fournisseur ajouté.');
        } else {
            $data[] = (int)$_POST['id'];
            $pdo->prepare("UPDATE fournisseurs SET nom=?,contact=?,telephone=?,email=?,adresse=? WHERE id=?")->execute($data);
            flash('Fournisseur mis à jour.');
        }
    }
    if ($action === 'delete') {
        $pdo->prepare("DELETE FROM fournisseurs WHERE id=?")->execute([(int)$_POST['id']]);
        flash('Fournisseur supprimé.', 'danger');
    }
    header('Location: fournisseurs.php'); exit;
}

$fournisseurs = $pdo->query("SELECT f.*, COUNT(m.id) as nb_meds FROM fournisseurs f LEFT JOIN medicaments m ON m.fournisseur_id=f.id GROUP BY f.id ORDER BY f.nom")->fetchAll();
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Fournisseurs — <?= APP_NAME ?></title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php require_once 'includes/sidebar.php'; ?>
<div class="main">
    <div class="topbar">
        <div class="topbar-title">Fournisseurs</div>
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
                <div class="page-title">Fournisseurs</div>
                <div class="page-sub"><?= count($fournisseurs) ?> fournisseur(s)</div>
            </div>
        </div>
        <div class="grid-3">
        <?php if (empty($fournisseurs)): ?>
            <div class="empty-state" style="grid-column:1/-1"><span class="empty-state-icon">🚚</span><p>Aucun fournisseur</p></div>
        <?php endif; ?>
        <?php foreach ($fournisseurs as $f): ?>
        <div class="card" style="transition:transform 0.2s,box-shadow 0.2s" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='var(--shadow-md)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
            <div class="card-body">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:1rem">
                    <div style="width:44px;height:44px;border-radius:12px;background:var(--primary-light);display:flex;align-items:center;justify-content:center;font-size:1.3rem">🚚</div>
                    <span class="badge badge-info"><?= $f['nb_meds'] ?> méd.</span>
                </div>
                <div style="font-family:'Outfit',sans-serif;font-size:1rem;font-weight:700;color:var(--text);margin-bottom:0.75rem"><?= h($f['nom']) ?></div>
                <?php if ($f['contact']): ?><div style="font-size:0.82rem;color:var(--text3);margin-bottom:4px">👤 <?= h($f['contact']) ?></div><?php endif; ?>
                <?php if ($f['telephone']): ?><div style="font-size:0.82rem;color:var(--text3);margin-bottom:4px">📞 <?= h($f['telephone']) ?></div><?php endif; ?>
                <?php if ($f['email']): ?><div style="font-size:0.82rem;color:var(--text3);margin-bottom:4px">✉️ <?= h($f['email']) ?></div><?php endif; ?>
                <?php if ($f['adresse']): ?><div style="font-size:0.82rem;color:var(--text3)">📍 <?= h($f['adresse']) ?></div><?php endif; ?>
                <div style="display:flex;gap:0.5rem;margin-top:1.2rem;padding-top:1rem;border-top:1px solid var(--border)">
                    <button class="btn btn-outline btn-sm" style="flex:1" onclick='openEdit(<?= json_encode($f) ?>)'>✏️ Modifier</button>
                    <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?= $f['id'] ?>,'<?= h($f['nom']) ?>')">🗑️</button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modalAdd" style="display:none" onclick="if(event.target===this)closeModal('modalAdd')">
<div class="modal">
    <div class="modal-header">
        <div class="modal-title">Ajouter un fournisseur</div>
        <button class="modal-close" onclick="closeModal('modalAdd')">✕</button>
    </div>
    <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="modal-body">
            <div class="form-group"><label class="form-label">Nom <span>*</span></label><input type="text" name="nom" class="form-control" required></div>
            <div class="form-group"><label class="form-label">Contact</label><input type="text" name="contact" class="form-control"></div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">Téléphone</label><input type="text" name="telephone" class="form-control"></div>
                <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
            </div>
            <div class="form-group"><label class="form-label">Adresse</label><textarea name="adresse" class="form-control" rows="2"></textarea></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal('modalAdd')">Annuler</button>
            <button type="submit" class="btn btn-primary">🚚 Ajouter</button>
        </div>
    </form>
</div>
</div>

<div class="modal-overlay" id="modalEdit" style="display:none" onclick="if(event.target===this)closeModal('modalEdit')">
<div class="modal">
    <div class="modal-header">
        <div class="modal-title">Modifier le fournisseur</div>
        <button class="modal-close" onclick="closeModal('modalEdit')">✕</button>
    </div>
    <form method="POST">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="edit_id">
        <div class="modal-body">
            <div class="form-group"><label class="form-label">Nom <span>*</span></label><input type="text" name="nom" id="edit_nom" class="form-control" required></div>
            <div class="form-group"><label class="form-label">Contact</label><input type="text" name="contact" id="edit_contact" class="form-control"></div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">Téléphone</label><input type="text" name="telephone" id="edit_tel" class="form-control"></div>
                <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" id="edit_email" class="form-control"></div>
            </div>
            <div class="form-group"><label class="form-label">Adresse</label><textarea name="adresse" id="edit_adresse" class="form-control" rows="2"></textarea></div>
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
function openEdit(f){
    ['id','nom','contact'].forEach(k=>document.getElementById('edit_'+k).value=f[k]||'');
    document.getElementById('edit_tel').value=f.telephone||'';
    document.getElementById('edit_email').value=f.email||'';
    document.getElementById('edit_adresse').value=f.adresse||'';
    openModal('modalEdit');
}
function confirmDelete(id,nom){
    if(confirm('Supprimer "'+nom+'" ?')){document.getElementById('delete_id').value=id;document.getElementById('deleteForm').submit()}
}
</script>
</body>
</html>
