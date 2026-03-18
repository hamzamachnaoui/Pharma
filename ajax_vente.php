<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
$pdo = db();
$id = (int)($_GET['id'] ?? 0);
if (!$id) exit;
$vente = $pdo->prepare("SELECT v.*, c.nom as cn, c.prenom as cp FROM ventes v LEFT JOIN clients c ON v.client_id=c.id WHERE v.id=?");
$vente->execute([$id]);
$v = $vente->fetch();
if (!$v) { echo '<p class="text-danger text-center">Vente introuvable</p>'; exit; }
$items = $pdo->prepare("SELECT vi.*, m.nom, m.forme FROM vente_items vi JOIN medicaments m ON vi.medicament_id=m.id WHERE vi.vente_id=?");
$items->execute([$id]);
$rows = $items->fetchAll();
?>
<table style="width:100%;border-collapse:collapse;font-size:0.875rem">
    <tr style="border-bottom:1px solid var(--border)">
        <td style="padding:0.5rem 0;color:var(--text3)">Client</td>
        <td style="padding:0.5rem 0;font-weight:600"><?= $v['cn'] ? h($v['cn'].' '.$v['cp']) : 'Anonyme' ?></td>
    </tr>
    <tr style="border-bottom:1px solid var(--border)">
        <td style="padding:0.5rem 0;color:var(--text3)">Date</td>
        <td style="padding:0.5rem 0"><?= date('d/m/Y H:i', strtotime($v['created_at'])) ?></td>
    </tr>
    <?php if ($v['note']): ?>
    <tr style="border-bottom:1px solid var(--border)">
        <td style="padding:0.5rem 0;color:var(--text3)">Note</td>
        <td style="padding:0.5rem 0"><?= h($v['note']) ?></td>
    </tr>
    <?php endif; ?>
</table>
<div style="margin-top:1.25rem">
    <p style="font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:var(--text3);margin-bottom:0.75rem">Articles</p>
    <?php foreach ($rows as $item): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:0.6rem 0;border-bottom:1px solid var(--border)">
        <div>
            <div style="font-weight:600;font-size:0.875rem"><?= h($item['nom']) ?> <?= $item['forme'] ? '<small style="color:var(--text3)">'.$item['forme'].'</small>' : '' ?></div>
            <div style="font-size:0.78rem;color:var(--text3)"><?= $item['quantite'] ?> × <?= formatPrice($item['prix_unitaire']) ?></div>
        </div>
        <div style="font-weight:700;color:var(--text)"><?= formatPrice($item['quantite'] * $item['prix_unitaire']) ?></div>
    </div>
    <?php endforeach; ?>
    <div style="display:flex;justify-content:space-between;padding:0.85rem 0;margin-top:0.25rem">
        <span style="font-weight:700">Total</span>
        <span style="font-family:'Outfit',sans-serif;font-size:1.2rem;font-weight:700;color:var(--success)"><?= formatPrice($v['total']) ?></span>
    </div>
</div>
