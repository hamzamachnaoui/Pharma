<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $data = [
            $_POST['nom'], $_POST['forme'] ?: null, $_POST['dosage'] ?: null,
            $_POST['categorie'] ?: null, $_POST['fournisseur_id'] ?: null,
            (float)$_POST['prix_achat'], (float)$_POST['prix_vente'],
            (int)$_POST['stock_actuel'], (int)$_POST['stock_min'],
            $_POST['date_expiration'] ?: null
        ];
        if ($action === 'add') {
            $pdo->prepare("INSERT INTO medicaments (nom,forme,dosage,categorie,fournisseur_id,prix_achat,prix_vente,stock_actuel,stock_min,date_expiration) VALUES (?,?,?,?,?,?,?,?,?,?)")->execute($data);
            flash('Médicament ajouté avec succès.');
        } else {
            $data[] = (int)$_POST['id'];
            $pdo->prepare("UPDATE medicaments SET nom=?,forme=?,dosage=?,categorie=?,fournisseur_id=?,prix_achat=?,prix_vente=?,stock_actuel=?,stock_min=?,date_expiration=? WHERE id=?")->execute($data);
            flash('Médicament mis à jour.');
        }
    }

    if ($action === 'delete') {
        $pdo->prepare("DELETE FROM medicaments WHERE id=?")->execute([(int)$_POST['id']]);
        flash('Médicament supprimé.', 'danger');
    }

    if ($action === 'stock') {
        $qte = (int)$_POST['quantite'];
        $type = $_POST['type'];
        $id = (int)$_POST['id'];
        if ($type === 'add') {
            $pdo->prepare("UPDATE medicaments SET stock_actuel = stock_actuel + ? WHERE id=?")->execute([$qte, $id]);
        } else {
            $pdo->prepare("UPDATE medicaments SET stock_actuel = GREATEST(0, stock_actuel - ?) WHERE id=?")->execute([$qte, $id]);
        }
        flash('Stock mis à jour.');
    }

    header('Location: medicaments.php');
    exit;
}

$search = trim($_GET['q'] ?? '');
$filtre = $_GET['filtre'] ?? '';
$where = ['1=1']; $params = [];

if ($search) { $where[] = 'm.nom LIKE ?'; $params[] = "%$search%"; }
if ($filtre === 'alerte')   { $where[] = 'm.stock_actuel <= m.stock_min'; }
if ($filtre === 'expire')   { $where[] = 'm.date_expiration <= DATE_ADD(NOW(), INTERVAL 30 DAY) AND m.date_expiration >= NOW()'; }
if ($filtre === 'rupture')  { $where[] = 'm.stock_actuel = 0'; }

$sql = "SELECT m.*, f.nom as fournisseur_nom FROM medicaments m LEFT JOIN fournisseurs f ON m.fournisseur_id = f.id WHERE " . implode(' AND ', $where) . " ORDER BY m.nom";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$meds = $stmt->fetchAll();
$fournisseurs = $pdo->query("SELECT id, nom FROM fournisseurs ORDER BY nom")->fetchAll();
$categories = $pdo->query("SELECT DISTINCT categorie FROM medicaments WHERE categorie IS NOT NULL ORDER BY categorie")->fetchAll(PDO::FETCH_COLUMN);
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Médicaments — <?= APP_NAME ?></title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php require_once 'includes/sidebar.php'; ?>
<div class="main">
    <div class="topbar">
        <div class="topbar-title">Médicaments & Stock</div>
        <div class="topbar-actions">
            <span class="topbar-date"><?= date('d/m/Y') ?></span>
            <button class="btn btn-primary btn-sm" onclick="openModal('modalAdd')">+ Ajouter</button>
        </div>
    </div>
    <div class="content">
        <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>"><?= h($flash['msg']) ?></div>
        <?php endif; ?>

        <div class="page-header">
            <div>
                <div class="page-title">Médicaments</div>
                <div class="page-sub"><?= count($meds) ?> médicament(s) trouvé(s)</div>
            </div>
            <div class="flex items-center gap-1" style="flex-wrap:wrap">
                <form method="GET" style="display:flex;gap:0.5rem;align-items:center">
                    <div class="search-bar">
                        <span>🔍</span>
                        <input type="text" name="q" placeholder="Rechercher..." value="<?= h($search) ?>">
                    </div>
                    <select name="filtre" class="form-control" style="width:auto" onchange="this.form.submit()">
                        <option value="">Tous</option>
                        <option value="alerte" <?= $filtre==='alerte'?'selected':'' ?>>⚠️ Stock alerte</option>
                        <option value="rupture" <?= $filtre==='rupture'?'selected':'' ?>>🔴 Rupture</option>
                        <option value="expire" <?= $filtre==='expire'?'selected':'' ?>>📅 Expire bientôt</option>
                    </select>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Médicament</th><th>Forme / Dosage</th><th>Catégorie</th>
                            <th>Fournisseur</th><th>Prix vente</th><th>Stock</th>
                            <th>Expiration</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($meds)): ?>
                    <tr><td colspan="8" class="text-center text-muted" style="padding:3rem">
                        <div class="empty-state"><span class="empty-state-icon">💊</span><p>Aucun médicament trouvé</p></div>
                    </td></tr>
                    <?php endif; ?>
                    <?php foreach ($meds as $m): ?>
                    <?php
                        $stock_ok = $m['stock_actuel'] > $m['stock_min'];
                        $stock_warn = !$stock_ok && $m['stock_actuel'] > 0;
                        $stock_empty = $m['stock_actuel'] == 0;
                        $exp_soon = $m['date_expiration'] && strtotime($m['date_expiration']) <= strtotime('+30 days');
                    ?>
                    <tr>
                        <td><strong><?= h($m['nom']) ?></strong></td>
                        <td class="text-muted"><?= h($m['forme']) ?> <?= h($m['dosage']) ?></td>
                        <td><?= $m['categorie'] ? '<span class="badge badge-neutral">'.h($m['categorie']).'</span>' : '—' ?></td>
                        <td class="text-muted"><?= h($m['fournisseur_nom'] ?? '—') ?></td>
                        <td><strong><?= formatPrice($m['prix_vente']) ?></strong></td>
                        <td>
                            <?php if ($stock_empty): ?>
                            <span class="badge badge-danger">🔴 Rupture</span>
                            <?php elseif ($stock_warn): ?>
                            <span class="badge badge-warning">⚠️ <?= $m['stock_actuel'] ?></span>
                            <?php else: ?>
                            <span class="badge badge-success">✓ <?= $m['stock_actuel'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= $exp_soon ? '<span class="text-warning">'.formatDate($m['date_expiration']).'</span>' : formatDate($m['date_expiration']) ?></td>
                        <td>
                            <div class="flex gap-1">
                                <button class="btn btn-outline btn-icon btn-sm" title="Ajuster stock" onclick="openStock(<?= $m['id'] ?>, '<?= h($m['nom']) ?>', <?= $m['stock_actuel'] ?>)">📦</button>
                                <button class="btn btn-outline btn-icon btn-sm" title="Modifier" onclick='openEdit(<?= json_encode($m) ?>)'>✏️</button>
                                <button class="btn btn-danger btn-icon btn-sm" title="Supprimer" onclick="confirmDelete(<?= $m['id'] ?>, '<?= h($m['nom']) ?>')">🗑️</button>
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
<div class="modal modal-lg">
    <div class="modal-header">
        <div class="modal-title">Ajouter un médicament</div>
        <button class="modal-close" onclick="closeModal('modalAdd')">✕</button>
    </div>
    <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="modal-body">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Nom <span>*</span></label>
                    <input type="text" name="nom" class="form-control" required placeholder="Ex: Paracétamol">
                </div>
                <div class="form-group">
                    <label class="form-label">Catégorie</label>
                    <input type="text" name="categorie" class="form-control" list="cats" placeholder="Ex: Antalgique">
                    <datalist id="cats"><?php foreach($categories as $c): ?><option value="<?= h($c) ?>"><?php endforeach; ?></datalist>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Forme</label>
                    <select name="forme" class="form-control">
                        <option value="">—</option>
                        <option>Comprimé</option><option>Gélule</option><option>Sirop</option>
                        <option>Injectable</option><option>Crème</option><option>Gouttes</option><option>Sachet</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Dosage</label>
                    <input type="text" name="dosage" class="form-control" placeholder="Ex: 500mg">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Prix achat (DH) <span>*</span></label>
                    <input type="number" name="prix_achat" class="form-control" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Prix vente (DH) <span>*</span></label>
                    <input type="number" name="prix_vente" class="form-control" step="0.01" min="0" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Stock actuel <span>*</span></label>
                    <input type="number" name="stock_actuel" class="form-control" min="0" value="0" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Stock minimum <span>*</span></label>
                    <input type="number" name="stock_min" class="form-control" min="0" value="10" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Fournisseur</label>
                    <select name="fournisseur_id" class="form-control">
                        <option value="">— Aucun —</option>
                        <?php foreach($fournisseurs as $f): ?>
                        <option value="<?= $f['id'] ?>"><?= h($f['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Date d'expiration</label>
                    <input type="date" name="date_expiration" class="form-control">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal('modalAdd')">Annuler</button>
            <button type="submit" class="btn btn-primary">💊 Ajouter</button>
        </div>
    </form>
</div>
</div>

<div class="modal-overlay" id="modalEdit" style="display:none" onclick="if(event.target===this)closeModal('modalEdit')">
<div class="modal modal-lg">
    <div class="modal-header">
        <div class="modal-title">Modifier le médicament</div>
        <button class="modal-close" onclick="closeModal('modalEdit')">✕</button>
    </div>
    <form method="POST" id="formEdit">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="edit_id">
        <div class="modal-body">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Nom <span>*</span></label>
                    <input type="text" name="nom" id="edit_nom" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Catégorie</label>
                    <input type="text" name="categorie" id="edit_categorie" class="form-control" list="cats">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Forme</label>
                    <select name="forme" id="edit_forme" class="form-control">
                        <option value="">—</option>
                        <option>Comprimé</option><option>Gélule</option><option>Sirop</option>
                        <option>Injectable</option><option>Crème</option><option>Gouttes</option><option>Sachet</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Dosage</label>
                    <input type="text" name="dosage" id="edit_dosage" class="form-control">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Prix achat (DH)</label>
                    <input type="number" name="prix_achat" id="edit_prix_achat" class="form-control" step="0.01" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Prix vente (DH)</label>
                    <input type="number" name="prix_vente" id="edit_prix_vente" class="form-control" step="0.01" min="0">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Stock actuel</label>
                    <input type="number" name="stock_actuel" id="edit_stock" class="form-control" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Stock minimum</label>
                    <input type="number" name="stock_min" id="edit_stock_min" class="form-control" min="0">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Fournisseur</label>
                    <select name="fournisseur_id" id="edit_fournisseur" class="form-control">
                        <option value="">— Aucun —</option>
                        <?php foreach($fournisseurs as $f): ?>
                        <option value="<?= $f['id'] ?>"><?= h($f['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Date d'expiration</label>
                    <input type="date" name="date_expiration" id="edit_expiration" class="form-control">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal('modalEdit')">Annuler</button>
            <button type="submit" class="btn btn-primary">💾 Enregistrer</button>
        </div>
    </form>
</div>
</div>

<div class="modal-overlay" id="modalStock" style="display:none" onclick="if(event.target===this)closeModal('modalStock')">
<div class="modal">
    <div class="modal-header">
        <div class="modal-title" id="stock_title">Ajuster le stock</div>
        <button class="modal-close" onclick="closeModal('modalStock')">✕</button>
    </div>
    <form method="POST">
        <input type="hidden" name="action" value="stock">
        <input type="hidden" name="id" id="stock_id">
        <div class="modal-body">
            <p class="text-muted mb-2" style="font-size:0.875rem">Stock actuel : <strong id="stock_current" style="color:var(--text)"></strong></p>
            <div class="form-group">
                <label class="form-label">Type d'opération</label>
                <div class="flex gap-2">
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:0.875rem">
                        <input type="radio" name="type" value="add" checked> ➕ Entrée (réception)
                    </label>
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:0.875rem">
                        <input type="radio" name="type" value="remove"> ➖ Sortie (ajustement)
                    </label>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Quantité <span>*</span></label>
                <input type="number" name="quantite" class="form-control" min="1" value="1" required>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal('modalStock')">Annuler</button>
            <button type="submit" class="btn btn-success">📦 Confirmer</button>
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
function openEdit(m){
    document.getElementById('edit_id').value=m.id;
    document.getElementById('edit_nom').value=m.nom;
    document.getElementById('edit_categorie').value=m.categorie||'';
    document.getElementById('edit_forme').value=m.forme||'';
    document.getElementById('edit_dosage').value=m.dosage||'';
    document.getElementById('edit_prix_achat').value=m.prix_achat;
    document.getElementById('edit_prix_vente').value=m.prix_vente;
    document.getElementById('edit_stock').value=m.stock_actuel;
    document.getElementById('edit_stock_min').value=m.stock_min;
    document.getElementById('edit_fournisseur').value=m.fournisseur_id||'';
    document.getElementById('edit_expiration').value=m.date_expiration||'';
    openModal('modalEdit');
}
function openStock(id,nom,stock){
    document.getElementById('stock_id').value=id;
    document.getElementById('stock_title').textContent='Stock — '+nom;
    document.getElementById('stock_current').textContent=stock+' unités';
    openModal('modalStock');
}
function confirmDelete(id,nom){
    if(confirm('Supprimer "'+nom+'" ?')){
        document.getElementById('delete_id').value=id;
        document.getElementById('deleteForm').submit();
    }
}
</script>
</body>
</html>
