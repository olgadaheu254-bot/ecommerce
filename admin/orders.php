<?php
require_once '../config/database.php';
$page_title = 'Gestion Commandes - HairRoots Admin';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /ecommerce/user/login.php');
    exit;
}

$success = ''; $error = '';

// CHANGER STATUT
if(isset($_GET['statut']) && isset($_GET['id'])) {
    $statuts_valides = ['pending','processing','shipped','delivered','cancelled'];
    $new_statut = $_GET['statut'];
    $order_id   = (int)$_GET['id'];
    if(in_array($new_statut, $statuts_valides)) {
        $pdo->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$new_statut, $order_id]);
        $success = "Statut mis a jour !";
    }
}

// FILTRES
$filtre_statut = isset($_GET['status'])  ? $_GET['status']        : '';
$filtre_date   = isset($_GET['date'])    ? $_GET['date']           : '';
$search        = isset($_GET['search'])  ? trim($_GET['search'])   : '';

$where = []; $params = [];
if($filtre_statut) { $where[] = "o.status = ?";          $params[] = $filtre_statut; }
if($filtre_date)   { $where[] = "DATE(o.created_at) = ?"; $params[] = $filtre_date; }
if($search)        { $where[] = "(o.order_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)"; $params = array_merge($params, ["%$search%","%$search%","%$search%","%$search%"]); }
$where_sql = count($where) ? 'WHERE '.implode(' AND ',$where) : '';

$stmt = $pdo->prepare("SELECT o.*, u.first_name, u.last_name, u.email FROM orders o LEFT JOIN users u ON o.user_id = u.id $where_sql ORDER BY o.created_at DESC");
$stmt->execute($params);
$orders = $stmt->fetchAll();

// STATS
$stats = $pdo->query("SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status='processing' THEN 1 ELSE 0 END) as processing,
    SUM(CASE WHEN status='shipped' THEN 1 ELSE 0 END) as shipped,
    SUM(CASE WHEN status='delivered' THEN 1 ELSE 0 END) as delivered,
    SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) as cancelled,
    SUM(CASE WHEN status != 'cancelled' THEN total_amount ELSE 0 END) as revenu
FROM orders")->fetch();

// DETAIL COMMANDE
$detail_order = null;
$detail_items = [];
if(isset($_GET['detail'])) {
    $stmt = $pdo->prepare("SELECT o.*, u.first_name, u.last_name, u.email, u.phone FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?");
    $stmt->execute([(int)$_GET['detail']]);
    $detail_order = $stmt->fetch();
    if($detail_order) {
        $stmt = $pdo->prepare("SELECT oi.*, p.name, p.image FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
        $stmt->execute([$detail_order['id']]);
        $detail_items = $stmt->fetchAll();
    }
}

include '../includes/header.php';

$sl = [
    'pending'    => ['En attente',    'b-attente',  '#F57F17'],
    'processing' => ['En traitement', 'b-process',  '#1565C0'],
    'shipped'    => ['Expediee',      'b-shipped',  '#6A1B9A'],
    'delivered'  => ['Livree',        'b-delivered','#2E7D32'],
    'cancelled'  => ['Annulee',       'b-cancelled','#C62828'],
];
?>
<style>
.admin-page{background:#FDF8F2;min-height:80vh;padding:30px 0}
.dash-header{background:linear-gradient(135deg,#3E1F0D,#6B3A2A);border-radius:20px;padding:22px 28px;margin-bottom:25px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:15px}
.dash-header h1{font-family:'Playfair Display',serif;color:#C9A84C;font-size:1.6rem;font-weight:900;margin:0}
.dash-nav{display:flex;gap:8px;flex-wrap:wrap}
.dash-nav-btn{background:rgba(201,168,76,0.15);color:#C9A84C;border:1px solid rgba(201,168,76,0.3);border-radius:10px;padding:7px 14px;font-size:0.8rem;font-weight:600;text-decoration:none;transition:all 0.3s}
.dash-nav-btn:hover,.dash-nav-btn.active{background:#C9A84C;color:#3E1F0D}
.stat-card{background:#fff;border-radius:14px;padding:18px;box-shadow:0 4px 15px rgba(62,31,13,0.05);border:1px solid #F5E6D3;text-align:center;transition:all 0.3s;cursor:pointer;text-decoration:none;display:block}
.stat-card:hover{transform:translateY(-3px);box-shadow:0 8px 25px rgba(62,31,13,0.1)}
.stat-card.active-filter{border-color:#C9A84C;background:#FFFDF5}
.stat-num{font-family:'Playfair Display',serif;font-size:1.8rem;font-weight:900;color:#3E1F0D}
.stat-lbl{font-size:0.75rem;color:#9a7c5c;margin-top:3px;font-weight:500}
.dash-card{background:#fff;border-radius:18px;box-shadow:0 4px 20px rgba(62,31,13,0.06);border:1px solid #F5E6D3;overflow:hidden;margin-bottom:25px}
.dash-card-header{background:linear-gradient(135deg,#F5E6D3,#FDEBD0);padding:16px 22px;border-bottom:1px solid #F0D9C0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px}
.dash-card-header h5{font-family:'Playfair Display',serif;color:#3E1F0D;font-weight:700;margin:0;font-size:1rem}
.pinput{border:2px solid #F5E6D3;border-radius:10px;padding:9px 14px;font-size:0.88rem;transition:all 0.3s;background:#FDFAF7;width:100%}
.pinput:focus{border-color:#C9A84C;box-shadow:0 0 0 3px rgba(201,168,76,0.1);outline:none}
.pbtn{background:linear-gradient(135deg,#C9A84C,#b8942e);color:#3E1F0D;border:none;border-radius:10px;padding:9px 20px;font-weight:700;font-size:0.85rem;cursor:pointer;transition:all 0.3s;text-decoration:none;display:inline-block}
.pbtn:hover{background:linear-gradient(135deg,#C1622F,#a0491f);color:#fff}
.pbtn-sm{padding:5px 12px;font-size:0.75rem;border-radius:8px}
.ptable{width:100%;border-collapse:collapse}
.ptable th{padding:11px 14px;font-weight:700;font-size:0.78rem;color:#9a7c5c;text-align:left;border-bottom:2px solid #F5E6D3;background:#FDFAF7;text-transform:uppercase}
.ptable td{padding:12px 14px;font-size:0.85rem;color:#3E1F0D;border-bottom:1px solid #F5E6D3;vertical-align:middle}
.ptable tr:last-child td{border-bottom:none}
.ptable tr:hover td{background:#FDFAF7}
.b{padding:4px 12px;border-radius:10px;font-size:0.75rem;font-weight:700;display:inline-block}
.b-attente{background:#FFF8E1;color:#F57F17}
.b-process{background:#E3F2FD;color:#1565C0}
.b-shipped{background:#F3E5F5;color:#6A1B9A}
.b-delivered{background:#E8F5E9;color:#2E7D32}
.b-cancelled{background:#FCE4E4;color:#C62828}
.filter-bar{background:#fff;border-radius:14px;box-shadow:0 4px 15px rgba(62,31,13,0.05);border:1px solid #F5E6D3;padding:16px 20px;margin-bottom:20px}
.alert-hr{border-radius:12px;padding:12px 18px;margin-bottom:20px;font-size:0.9rem;border:none}
.alert-hr.success{background:#e8f5e9;color:#2e7d32;border-left:4px solid #2e7d32}

/* DETAIL MODAL */
.detail-overlay{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(62,31,13,0.5);z-index:1000;display:flex;align-items:center;justify-content:center;padding:20px}
.detail-modal{background:#fff;border-radius:20px;max-width:700px;width:100%;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.2)}
.detail-modal-header{background:linear-gradient(135deg,#3E1F0D,#6B3A2A);padding:20px 25px;display:flex;align-items:center;justify-content:space-between;border-radius:20px 20px 0 0}
.detail-modal-body{padding:25px}
.close-btn{background:rgba(255,255,255,0.15);border:none;color:#fff;border-radius:50%;width:32px;height:32px;font-size:1.1rem;cursor:pointer;display:flex;align-items:center;justify-content:center}
.close-btn:hover{background:rgba(255,255,255,0.3)}
.statut-select{border:2px solid #F5E6D3;border-radius:8px;padding:6px 12px;font-size:0.82rem;background:#FDFAF7;color:#3E1F0D;font-weight:600;cursor:pointer}
</style>

<div class="admin-page"><div class="container">

<div class="dash-header">
    <div>
        <h1>🛍️ Gestion des Commandes</h1>
        <p style="color:rgba(255,255,255,0.6);margin:4px 0 0;font-size:0.82rem"><?= count($orders) ?> commande(s) affichee(s)</p>
    </div>
    <div class="dash-nav">
        <a href="index.php" class="dash-nav-btn">📊 Dashboard</a>
        <a href="products.php" class="dash-nav-btn">📦 Produits</a>
        <a href="orders.php" class="dash-nav-btn active">🛍️ Commandes</a>
        <a href="users.php" class="dash-nav-btn">👥 Utilisateurs</a>
        <a href="appointments.php" class="dash-nav-btn">📅 RDV</a>
    </div>
</div>

<?php if($success): ?><div class="alert-hr success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>

<!-- STATS RAPIDES -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
        <a href="orders.php" class="stat-card <?= !$filtre_statut?'active-filter':'' ?>">
            <div class="stat-num"><?= $stats['total'] ?></div>
            <div class="stat-lbl">Total</div>
        </a>
    </div>
    <?php foreach([
        ['pending',    $stats['pending'],    'En attente'],
        ['processing', $stats['processing'], 'En cours'],
        ['shipped',    $stats['shipped'],    'Expediees'],
        ['delivered',  $stats['delivered'],  'Livrees'],
        ['cancelled',  $stats['cancelled'],  'Annulees'],
    ] as [$st, $nb, $lbl]): ?>
    <div class="col-6 col-md-2">
        <a href="?status=<?= $st ?>" class="stat-card <?= $filtre_statut===$st?'active-filter':'' ?>">
            <div class="stat-num" style="color:<?= $sl[$st][2] ?>"><?= $nb ?></div>
            <div class="stat-lbl"><?= $lbl ?></div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<!-- FILTRES -->
<div class="filter-bar">
    <form method="GET" action="" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
        <input type="text" class="pinput" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="🔍 N° commande, client..." style="max-width:220px">
        <input type="date" class="pinput" name="date" value="<?= htmlspecialchars($filtre_date) ?>" style="max-width:160px">
        <select class="pinput" name="status" style="max-width:180px">
            <option value="">Tous les statuts</option>
            <?php foreach($sl as $val=>$info): ?>
            <option value="<?= $val ?>" <?= $filtre_statut===$val?'selected':'' ?>><?= $info[0] ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="pbtn">Filtrer</button>
        <a href="orders.php" style="background:#F5E6D3;color:#6B3A2A;border:none;border-radius:10px;padding:9px 16px;font-weight:600;font-size:0.85rem;text-decoration:none">Reset</a>
    </form>
</div>

<!-- TABLEAU COMMANDES -->
<div class="dash-card">
    <div class="dash-card-header">
        <h5>📋 Liste des commandes</h5>
        <span style="color:#9a7c5c;font-size:0.82rem">Revenu total : <strong style="color:#C1622F"><?= number_format($stats['revenu'],2) ?>€</strong></span>
    </div>
    <div style="overflow-x:auto">
    <?php if(count($orders) > 0): ?>
    <table class="ptable">
        <thead><tr>
            <th>N° Commande</th>
            <th>Client</th>
            <th>Montant</th>
            <th>Paiement</th>
            <th>Statut</th>
            <th>Date</th>
            <th>Actions</th>
        </tr></thead>
        <tbody>
        <?php foreach($orders as $o):
            $s = $sl[$o['status']] ?? ['?','b-attente','#9a7c5c'];
        ?>
        <tr>
            <td style="font-weight:800;color:#C1622F"><?= htmlspecialchars($o['order_number']) ?></td>
            <td>
                <div style="font-weight:600"><?= htmlspecialchars($o['first_name'].' '.$o['last_name']) ?></div>
                <div style="color:#9a7c5c;font-size:0.75rem"><?= htmlspecialchars($o['email']) ?></div>
            </td>
            <td style="font-weight:800;color:#3E1F0D"><?= number_format($o['total_amount'],2) ?>€</td>
            <td style="font-size:0.8rem;color:#6B3A2A"><?= htmlspecialchars($o['payment_method']??'-') ?></td>
            <td>
                <select class="statut-select" onchange="changerStatut(<?= $o['id'] ?>, this.value)">
                    <?php foreach($sl as $val=>$info): ?>
                    <option value="<?= $val ?>" <?= $o['status']===$val?'selected':'' ?>><?= $info[0] ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td style="color:#9a7c5c;font-size:0.78rem;white-space:nowrap"><?= date('d/m/Y H:i',strtotime($o['created_at'])) ?></td>
            <td>
                <a href="?detail=<?= $o['id'] ?><?= $filtre_statut?'&status='.$filtre_statut:'' ?>" class="pbtn pbtn-sm">👁️ Detail</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div style="text-align:center;padding:40px;color:#9a7c5c">
        <div style="font-size:3rem;margin-bottom:15px">🛍️</div>
        <h5 style="color:#3E1F0D">Aucune commande trouvee</h5>
    </div>
    <?php endif; ?>
    </div>
</div>

<!-- DETAIL COMMANDE -->
<?php if($detail_order): ?>
<div class="detail-overlay" onclick="if(event.target===this)window.location='orders.php'">
    <div class="detail-modal">
        <div class="detail-modal-header">
            <div>
                <h5 style="font-family:'Playfair Display',serif;color:#C9A84C;margin:0;font-weight:700"><?= htmlspecialchars($detail_order['order_number']) ?></h5>
                <p style="color:rgba(255,255,255,0.6);font-size:0.8rem;margin:3px 0 0"><?= date('d/m/Y H:i',strtotime($detail_order['created_at'])) ?></p>
            </div>
            <a href="orders.php" class="close-btn">✕</a>
        </div>
        <div class="detail-modal-body">

            <!-- INFOS CLIENT -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div style="background:#F5E6D3;border-radius:12px;padding:15px">
                        <h6 style="color:#3E1F0D;font-weight:700;margin-bottom:10px">👤 Client</h6>
                        <p style="margin:3px 0;font-size:0.88rem;color:#6B3A2A"><strong><?= htmlspecialchars($detail_order['first_name'].' '.$detail_order['last_name']) ?></strong></p>
                        <p style="margin:3px 0;font-size:0.82rem;color:#6B3A2A"><?= htmlspecialchars($detail_order['email']) ?></p>
                        <?php if(!empty($detail_order['phone'])): ?>
                            <p style="margin:3px 0;font-size:0.82rem;color:#6B3A2A"><?= htmlspecialchars($detail_order['phone']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div style="background:#F5E6D3;border-radius:12px;padding:15px">
                        <h6 style="color:#3E1F0D;font-weight:700;margin-bottom:10px">📍 Adresse de livraison</h6>
                        <p style="margin:0;font-size:0.82rem;color:#6B3A2A;white-space:pre-line"><?= htmlspecialchars($detail_order['shipping_address']??'Non renseignee') ?></p>
                    </div>
                </div>
            </div>

            <!-- ARTICLES -->
            <h6 style="color:#3E1F0D;font-weight:700;margin-bottom:12px">🛍️ Articles commandes</h6>
            <?php foreach($detail_items as $item): ?>
            <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #F5E6D3">
                <?php if(!empty($item['image'])): ?>
                    <img src="<?= htmlspecialchars($item['image']) ?>" style="width:50px;height:50px;border-radius:8px;object-fit:cover;flex-shrink:0">
                <?php else: ?>
                    <div style="width:50px;height:50px;border-radius:8px;background:#F5E6D3;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0">🌿</div>
                <?php endif; ?>
                <div style="flex:1">
                    <div style="font-weight:600;color:#3E1F0D;font-size:0.88rem"><?= htmlspecialchars($item['name']) ?></div>
                    <div style="color:#9a7c5c;font-size:0.75rem">Qte: <?= $item['quantity'] ?> x <?= number_format($item['price'],2) ?>€</div>
                </div>
                <div style="font-weight:800;color:#C1622F"><?= number_format($item['subtotal'],2) ?>€</div>
            </div>
            <?php endforeach; ?>

            <!-- TOTAL -->
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:15px;padding-top:15px;border-top:2px solid #F5E6D3">
                <span style="font-family:'Playfair Display',serif;font-size:1.1rem;font-weight:700;color:#3E1F0D">Total</span>
                <span style="font-family:'Playfair Display',serif;font-size:1.4rem;font-weight:900;color:#C1622F"><?= number_format($detail_order['total_amount'],2) ?>€</span>
            </div>

            <!-- CHANGER STATUT -->
            <div style="background:#F5E6D3;border-radius:12px;padding:15px;margin-top:20px">
                <h6 style="color:#3E1F0D;font-weight:700;margin-bottom:12px">🔄 Changer le statut</h6>
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    <?php foreach($sl as $val=>$info): ?>
                    <a href="?statut=<?= $val ?>&id=<?= $detail_order['id'] ?>&detail=<?= $detail_order['id'] ?>"
                       style="background:<?= $detail_order['status']===$val?$info[2]:'#fff' ?>;color:<?= $detail_order['status']===$val?'#fff':'#3E1F0D' ?>;padding:7px 16px;border-radius:10px;font-size:0.8rem;font-weight:700;text-decoration:none;border:2px solid <?= $info[2] ?>;transition:all 0.2s">
                        <?= $info[0] ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if(!empty($detail_order['notes'])): ?>
            <div style="background:#FFF8E1;border-radius:12px;padding:15px;margin-top:15px">
                <h6 style="color:#3E1F0D;font-weight:700;margin-bottom:5px">💬 Notes client</h6>
                <p style="margin:0;font-size:0.85rem;color:#6B3A2A"><?= htmlspecialchars($detail_order['notes']) ?></p>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>
<?php endif; ?>

</div></div>

<script>
function changerStatut(orderId, newStatut) {
    window.location = 'orders.php?statut=' + newStatut + '&id=' + orderId;
}
</script>

<?php include '../includes/footer.php'; ?>