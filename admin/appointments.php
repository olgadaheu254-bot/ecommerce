<?php
require_once '../config/database.php';
$page_title = 'Gestion des Rendez-vous - HairRoots Admin';

// Protection admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /ecommerce/user/login.php');
    exit;
}

// CHANGER STATUT
if(isset($_GET['statut']) && isset($_GET['id'])) {
    $statuts_valides = ['en attente','confirme','annule'];
    $new_statut = $_GET['statut'];
    $rdv_id = (int)$_GET['id'];
    if(in_array($new_statut, $statuts_valides)) {
        $pdo->prepare("UPDATE appointments SET statut=? WHERE id=?")->execute([$new_statut, $rdv_id]);
    }
    header('Location: appointments.php'); exit;
}

// SUPPRIMER
if(isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM appointments WHERE id=?")->execute([(int)$_GET['delete']]);
    header('Location: appointments.php'); exit;
}

// FILTRES
$filtre_statut = isset($_GET['statut_filtre']) ? $_GET['statut_filtre'] : '';
$filtre_coiffeuse = isset($_GET['coiffeuse_filtre']) ? (int)$_GET['coiffeuse_filtre'] : 0;
$filtre_date = isset($_GET['date_filtre']) ? $_GET['date_filtre'] : '';

$where = []; $params = [];
if($filtre_statut) { $where[] = "a.statut = ?"; $params[] = $filtre_statut; }
if($filtre_coiffeuse) { $where[] = "a.coiffeuse_id = ?"; $params[] = $filtre_coiffeuse; }
if($filtre_date) { $where[] = "a.date_rdv = ?"; $params[] = $filtre_date; }
$where_sql = count($where) ? 'WHERE '.implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT a.*, c.prenom as c_prenom, c.nom as c_nom, c.specialite FROM appointments a LEFT JOIN coiffeuses c ON a.coiffeuse_id = c.id $where_sql ORDER BY a.date_rdv DESC, a.heure_rdv DESC");
$stmt->execute($params); $appointments = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM coiffeuses ORDER BY prenom");
$coiffeuses = $stmt->fetchAll();

// Stats
$stats = $pdo->query("SELECT statut, COUNT(*) as total FROM appointments GROUP BY statut")->fetchAll(PDO::FETCH_KEY_PAIR);
$total_rdv = array_sum($stats);

include '../includes/header.php';
?>
<style>
.admin-page{background:#FDF8F2;min-height:80vh;padding:30px 0}
.admin-header{background:linear-gradient(135deg,#3E1F0D,#6B3A2A);border-radius:20px;padding:25px 30px;margin-bottom:25px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:15px}
.admin-header h1{font-family:'Playfair Display',serif;color:#C9A84C;font-size:1.8rem;font-weight:900;margin:0}
.stat-box{background:rgba(255,255,255,0.1);border-radius:12px;padding:12px 20px;text-align:center;min-width:80px}
.stat-box .num{font-size:1.6rem;font-weight:900;color:#C9A84C;font-family:'Playfair Display',serif}
.stat-box .lbl{font-size:0.72rem;color:rgba(255,255,255,0.7);margin-top:2px}
.filter-card{background:#fff;border-radius:16px;box-shadow:0 4px 15px rgba(62,31,13,0.06);border:1px solid #F5E6D3;padding:20px;margin-bottom:20px}
.f-input{border:2px solid #F5E6D3;border-radius:10px;padding:9px 14px;font-size:0.88rem;transition:all 0.3s;background:#FDFAF7;width:100%}
.f-input:focus{border-color:#C9A84C;box-shadow:0 0 0 3px rgba(201,168,76,0.1);outline:none}
.f-btn{background:linear-gradient(135deg,#C9A84C,#b8942e);color:#3E1F0D;border:none;border-radius:10px;padding:9px 20px;font-weight:700;font-size:0.88rem;cursor:pointer;text-decoration:none;display:inline-block}
.f-btn:hover{background:linear-gradient(135deg,#C1622F,#a0491f);color:#fff}
.f-btn-reset{background:#F5E6D3;color:#6B3A2A;border:none;border-radius:10px;padding:9px 20px;font-weight:600;font-size:0.88rem;cursor:pointer;text-decoration:none;display:inline-block}
.rdv-table{background:#fff;border-radius:16px;box-shadow:0 4px 15px rgba(62,31,13,0.06);border:1px solid #F5E6D3;overflow:hidden}
.rdv-table table{width:100%;border-collapse:collapse}
.rdv-table thead{background:linear-gradient(135deg,#F5E6D3,#FDEBD0)}
.rdv-table th{padding:14px 16px;font-weight:700;font-size:0.82rem;color:#3E1F0D;text-align:left;border-bottom:2px solid #F0D9C0}
.rdv-table td{padding:14px 16px;font-size:0.85rem;color:#3E1F0D;border-bottom:1px solid #F5E6D3;vertical-align:middle}
.rdv-table tr:last-child td{border-bottom:none}
.rdv-table tr:hover td{background:#FDFAF7}
.badge-statut{padding:4px 14px;border-radius:12px;font-size:0.75rem;font-weight:700;display:inline-block}
.badge-attente{background:#FFF8E1;color:#F57F17}
.badge-confirme{background:#E8F5E9;color:#2E7D32}
.badge-annule{background:#FCE4E4;color:#C62828}
.action-btn{padding:5px 12px;border-radius:8px;font-size:0.75rem;font-weight:600;text-decoration:none;display:inline-block;border:none;cursor:pointer;transition:all 0.2s;margin:2px}
.btn-confirm{background:#e8f5e9;color:#2e7d32}.btn-confirm:hover{background:#2e7d32;color:#fff}
.btn-cancel{background:#FFF8E1;color:#F57F17}.btn-cancel:hover{background:#F57F17;color:#fff}
.btn-delete{background:#fce4e4;color:#c62828}.btn-delete:hover{background:#c62828;color:#fff}
.empty-state{text-align:center;padding:50px;color:#9a7c5c}
</style>

<div class="admin-page"><div class="container">

<div class="admin-header">
    <div>
        <h1> Gestion des Rendez-vous</h1>
        <p style="color:rgba(255,255,255,0.6);margin:5px 0 0;font-size:0.88rem">HairRoots Admin</p>
    </div>
    <div class="d-flex gap-3 flex-wrap">
        <div class="stat-box"><div class="num"><?= $total_rdv ?></div><div class="lbl">Total</div></div>
        <div class="stat-box"><div class="num"><?= $stats['en attente']??0 ?></div><div class="lbl">En attente</div></div>
        <div class="stat-box"><div class="num"><?= $stats['confirme']??0 ?></div><div class="lbl">Confirmes</div></div>
        <div class="stat-box"><div class="num"><?= $stats['annule']??0 ?></div><div class="lbl">Annules</div></div>
    </div>
</div>

<!-- FILTRES -->
<div class="filter-card">
    <form method="GET" action="">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label style="font-weight:600;font-size:0.82rem;color:#3E1F0D;margin-bottom:5px;display:block">Statut</label>
                <select class="f-input" name="statut_filtre">
                    <option value="">Tous les statuts</option>
                    <option value="en attente" <?= $filtre_statut==='en attente'?'selected':'' ?>>En attente</option>
                    <option value="confirme" <?= $filtre_statut==='confirme'?'selected':'' ?>>Confirmes</option>
                    <option value="annule" <?= $filtre_statut==='annule'?'selected':'' ?>>Annules</option>
                </select>
            </div>
            <div class="col-md-3">
                <label style="font-weight:600;font-size:0.82rem;color:#3E1F0D;margin-bottom:5px;display:block">Coiffeuse</label>
                <select class="f-input" name="coiffeuse_filtre">
                    <option value="">Toutes les coiffeuses</option>
                    <?php foreach($coiffeuses as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $filtre_coiffeuse==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['prenom'].' '.$c['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label style="font-weight:600;font-size:0.82rem;color:#3E1F0D;margin-bottom:5px;display:block">Date</label>
                <input type="date" class="f-input" name="date_filtre" value="<?= htmlspecialchars($filtre_date) ?>">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="f-btn">Filtrer</button>
                <a href="appointments.php" class="f-btn-reset">Reset</a>
            </div>
        </div>
    </form>
</div>

<!-- TABLEAU -->
<div class="rdv-table">
    <?php if(count($appointments)>0): ?>
    <div class="table-responsive">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Client</th>
                <th>Coiffeuse</th>
                <th>Date & Heure</th>
                <th>Prestation</th>
                <th>Cheveux</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($appointments as $a): ?>
        <tr>
            <td style="color:#9a7c5c;font-size:0.78rem">#<?= $a['id'] ?></td>
            <td>
                <div style="font-weight:700"><?= htmlspecialchars($a['prenom_client'].' '.$a['nom_client']) ?></div>
                <div style="color:#9a7c5c;font-size:0.75rem"><?= htmlspecialchars($a['email']) ?></div>
                <div style="color:#9a7c5c;font-size:0.75rem"><?= htmlspecialchars($a['telephone']) ?></div>
            </td>
            <td>
                <div style="font-weight:600"><?= htmlspecialchars($a['c_prenom'].' '.$a['c_nom']) ?></div>
                <div style="color:#9a7c5c;font-size:0.75rem"><?= htmlspecialchars($a['specialite']) ?></div>
            </td>
            <td>
                <div style="font-weight:700;color:#C1622F"><?= date('d/m/Y', strtotime($a['date_rdv'])) ?></div>
                <div style="color:#6B3A2A;font-size:0.82rem"><?= htmlspecialchars($a['heure_rdv']) ?></div>
            </td>
            <td><?= htmlspecialchars($a['type_prestation']) ?></td>
            <td><?= htmlspecialchars($a['type_cheveux']) ?></td>
            <td>
                <?php if($a['statut']==='en attente'): ?>
                    <span class="badge-statut badge-attente">En attente</span>
                <?php elseif($a['statut']==='confirme'): ?>
                    <span class="badge-statut badge-confirme">Confirme</span>
                <?php else: ?>
                    <span class="badge-statut badge-annule">Annule</span>
                <?php endif; ?>
            </td>
            <td>
                <?php if($a['statut']!=='confirme'): ?>
                <a href="?statut=confirme&id=<?= $a['id'] ?>" class="action-btn btn-confirm">Confirmer</a>
                <?php endif; ?>
                <?php if($a['statut']!=='annule'): ?>
                <a href="?statut=annule&id=<?= $a['id'] ?>" class="action-btn btn-cancel">Annuler</a>
                <?php endif; ?>
                <a href="?delete=<?= $a['id'] ?>" class="action-btn btn-delete"
                   onclick="return confirm('Supprimer ce RDV ?')">Supprimer</a>
            </td>
        </tr>
        <?php if(!empty($a['message'])): ?>
        <tr style="background:#FDFAF7">
            <td colspan="8" style="padding:8px 16px;font-size:0.8rem;color:#6B3A2A;border-bottom:1px solid #F5E6D3">
                 <em><?= htmlspecialchars($a['message']) ?></em>
            </td>
        </tr>
        <?php endif; ?>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <div style="font-size:3rem;margin-bottom:15px"></div>
        <h5 style="color:#3E1F0D">Aucun rendez-vous</h5>
        <p>Aucun RDV ne correspond a vos filtres.</p>
    </div>
    <?php endif; ?>
</div>

</div></div>
<?php include '../includes/header.php'; ?>