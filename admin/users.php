<?php
require_once 'auth_admin.php'; 
require_once '../config/database.php';
$page_title = 'Gestion Utilisateurs - HairRoots Admin';

$success = ''; $error = '';

// CHANGER ROLE
if(isset($_GET['role']) && isset($_GET['id'])) {
    $roles_valides = ['user','admin'];
    $new_role = $_GET['role'];
    $uid = (int)$_GET['id'];
    if(in_array($new_role, $roles_valides) && $uid !== (int)$_SESSION['user_id']) {
        $pdo->prepare("UPDATE users SET role=? WHERE id=?")->execute([$new_role, $uid]);
        $success = "Role mis a jour !";
    }
}

// ACTIVER / DESACTIVER
if(isset($_GET['toggle']) && (int)$_GET['toggle'] !== (int)$_SESSION['user_id']) {
    $uid = (int)$_GET['toggle'];
    $pdo->prepare("UPDATE users SET active = NOT active WHERE id=?")->execute([$uid]);
    header('Location: users.php'); exit;
}

// FILTRES
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

//  On affiche UNIQUEMENT les clients (role = 'user'), jamais les admins
$where = ["u.role = 'user'"]; 
$params = [];

if($search) {
    $where[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.username LIKE ?)";
    $params = array_merge($params, ["%$search%","%$search%","%$search%","%$search%"]);
}
$where_sql = 'WHERE '.implode(' AND ', $where);

$stmt = $pdo->prepare("SELECT u.*, 
    (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) as nb_commandes,
    (SELECT SUM(total_amount) FROM orders o WHERE o.user_id = u.id AND o.status != 'cancelled') as total_depense,
    (SELECT COUNT(*) FROM appointments a WHERE a.user_id = u.id) as nb_rdv
    FROM users u $where_sql ORDER BY u.created_at DESC");
$stmt->execute($params);
$users = $stmt->fetchAll();

// STATS (uniquement clients)
$stats = $pdo->query("SELECT
    COUNT(*) as total,
    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as nouveaux_jour,
    SUM(CASE WHEN MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW()) THEN 1 ELSE 0 END) as nouveaux_mois
FROM users WHERE role = 'user'")->fetch();

// DETAIL USER
$detail_user = null;
$user_orders = [];
if(isset($_GET['detail'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=? AND role='user'");
    $stmt->execute([(int)$_GET['detail']]);
    $detail_user = $stmt->fetch();
    if($detail_user) {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$detail_user['id']]);
        $user_orders = $stmt->fetchAll();
    }
}

function getInitiales($f,$l){ return strtoupper(substr($f,0,1).substr($l,0,1)); }
function getAvatarColor($n){ $c=['#C1622F','#C9A84C','#6B3A2A','#3E1F0D','#8B4513','#A0522D']; return $c[ord($n[0])%count($c)]; }

include 'header_admin.php';
?>
<style>
.admin-page{background:#FDF8F2;min-height:80vh;padding:30px 0}
.dash-header{background:linear-gradient(135deg,#3E1F0D,#6B3A2A);border-radius:20px;padding:22px 28px;margin-bottom:25px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:15px}
.dash-header h1{font-family:'Playfair Display',serif;color:#C9A84C;font-size:1.6rem;font-weight:900;margin:0}
.dash-nav{display:flex;gap:8px;flex-wrap:wrap}
.dash-nav-btn{background:rgba(201,168,76,0.15);color:#C9A84C;border:1px solid rgba(201,168,76,0.3);border-radius:10px;padding:7px 14px;font-size:0.8rem;font-weight:600;text-decoration:none;transition:all 0.3s}
.dash-nav-btn:hover,.dash-nav-btn.active{background:#C9A84C;color:#3E1F0D}
.stat-card{background:#fff;border-radius:14px;padding:18px;box-shadow:0 4px 15px rgba(62,31,13,0.05);border:1px solid #F5E6D3;text-align:center}
.stat-num{font-family:'Playfair Display',serif;font-size:1.8rem;font-weight:900;color:#3E1F0D}
.stat-lbl{font-size:0.75rem;color:#9a7c5c;margin-top:3px}
.dash-card{background:#fff;border-radius:18px;box-shadow:0 4px 20px rgba(62,31,13,0.06);border:1px solid #F5E6D3;overflow:hidden;margin-bottom:25px}
.dash-card-header{background:linear-gradient(135deg,#F5E6D3,#FDEBD0);padding:16px 22px;border-bottom:1px solid #F0D9C0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px}
.dash-card-header h5{font-family:'Playfair Display',serif;color:#3E1F0D;font-weight:700;margin:0;font-size:1rem}
.pinput{border:2px solid #F5E6D3;border-radius:10px;padding:9px 14px;font-size:0.88rem;transition:all 0.3s;background:#FDFAF7;width:100%}
.pinput:focus{border-color:#C9A84C;box-shadow:0 0 0 3px rgba(201,168,76,0.1);outline:none}
.pbtn{background:linear-gradient(135deg,#C9A84C,#b8942e);color:#3E1F0D;border:none;border-radius:10px;padding:9px 20px;font-weight:700;font-size:0.85rem;cursor:pointer;transition:all 0.3s;text-decoration:none;display:inline-block}
.pbtn:hover{background:linear-gradient(135deg,#C1622F,#a0491f);color:#fff}
.pbtn-sm{padding:5px 12px;font-size:0.75rem;border-radius:8px}
.pbtn-danger{background:#fce4e4;color:#c62828;border:none;border-radius:8px;padding:5px 12px;font-size:0.75rem;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block;transition:all 0.2s}
.pbtn-danger:hover{background:#c62828;color:#fff}
.pbtn-info{background:#E3F2FD;color:#1565C0;border:none;border-radius:8px;padding:5px 12px;font-size:0.75rem;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block;transition:all 0.2s}
.pbtn-info:hover{background:#1565C0;color:#fff}
.ptable{width:100%;border-collapse:collapse}
.ptable th{padding:11px 14px;font-weight:700;font-size:0.78rem;color:#9a7c5c;text-align:left;border-bottom:2px solid #F5E6D3;background:#FDFAF7;text-transform:uppercase}
.ptable td{padding:12px 14px;font-size:0.85rem;color:#3E1F0D;border-bottom:1px solid #F5E6D3;vertical-align:middle}
.ptable tr:last-child td{border-bottom:none}
.ptable tr:hover td{background:#FDFAF7}
.avatar-init{width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:900;color:#fff;font-size:0.85rem;font-family:'Playfair Display',serif;flex-shrink:0}
.avatar-img{width:38px;height:38px;border-radius:50%;object-fit:cover;flex-shrink:0;border:2px solid #F5E6D3}
.badge-user{background:#F5E6D3;color:#6B3A2A;padding:3px 12px;border-radius:10px;font-size:0.72rem;font-weight:700}
.filter-bar{background:#fff;border-radius:14px;box-shadow:0 4px 15px rgba(62,31,13,0.05);border:1px solid #F5E6D3;padding:16px 20px;margin-bottom:20px}
.alert-hr{border-radius:12px;padding:12px 18px;margin-bottom:20px;font-size:0.9rem;border:none}
.alert-hr.success{background:#e8f5e9;color:#2e7d32;border-left:4px solid #2e7d32}
.detail-overlay{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(62,31,13,0.5);z-index:1000;display:flex;align-items:center;justify-content:center;padding:20px}
.detail-modal{background:#fff;border-radius:20px;max-width:600px;width:100%;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.2)}
.detail-modal-header{background:linear-gradient(135deg,#3E1F0D,#6B3A2A);padding:20px 25px;display:flex;align-items:center;justify-content:space-between;border-radius:20px 20px 0 0}
.detail-modal-body{padding:25px}
.close-btn{background:rgba(255,255,255,0.15);border:none;color:#fff;border-radius:50%;width:32px;height:32px;font-size:1.1rem;cursor:pointer;display:flex;align-items:center;justify-content:center;text-decoration:none}
.close-btn:hover{background:rgba(255,255,255,0.3);color:#fff}
</style>

<div class="admin-page"><div class="container">

<div class="dash-header">
    <div>
        <h1> Gestion des Clients</h1>
        <p style="color:rgba(255,255,255,0.6);margin:4px 0 0;font-size:0.82rem"><?= count($users) ?> client(s) affiché(s)</p>
    </div>
    <div class="dash-nav">
        <a href="index.php" class="dash-nav-btn"> Dashboard</a>
        <a href="products.php" class="dash-nav-btn"> Produits</a>
        <a href="orders.php" class="dash-nav-btn"> Commandes</a>
        <a href="users.php" class="dash-nav-btn active"> Utilisateurs</a>
        <a href="appointments.php" class="dash-nav-btn"> RDV</a>
    </div>
</div>

<?php if($success): ?><div class="alert-hr success"> <?= htmlspecialchars($success) ?></div><?php endif; ?>

<!-- STATS -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <div class="stat-card">
            <div class="stat-num"><?= $stats['total'] ?></div>
            <div class="stat-lbl">Total clients</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="stat-card">
            <div class="stat-num" style="color:#C9A84C"><?= $stats['nouveaux_jour'] ?></div>
            <div class="stat-lbl">Nouveaux aujourd'hui</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="stat-card">
            <div class="stat-num" style="color:#2e7d32"><?= $stats['nouveaux_mois'] ?></div>
            <div class="stat-lbl">Nouveaux ce mois</div>
        </div>
    </div>
</div>

<!-- FILTRES -->
<div class="filter-bar">
    <form method="GET" action="" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
        <input type="text" class="pinput" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="🔍 Nom, email, username..." style="max-width:300px">
        <button type="submit" class="pbtn">Filtrer</button>
        <a href="users.php" style="background:#F5E6D3;color:#6B3A2A;border:none;border-radius:10px;padding:9px 16px;font-weight:600;font-size:0.85rem;text-decoration:none">Reset</a>
    </form>
</div>

<!-- TABLEAU -->
<div class="dash-card">
    <div class="dash-card-header">
        <h5>👥 Liste des clients (<?= count($users) ?>)</h5>
    </div>
    <div style="overflow-x:auto">
    <?php if(count($users) > 0): ?>
    <table class="ptable">
        <thead><tr>
            <th>Client</th>
            <th>Contact</th>
            <th>Commandes</th>
            <th>Dépenses</th>
            <th>RDV</th>
            <th>Inscrit le</th>
            <th>Actions</th>
        </tr></thead>
        <tbody>
        <?php foreach($users as $u): ?>
        <tr>
            <td>
                <div style="display:flex;align-items:center;gap:10px">
                    <?php if(!empty($u['photo'])): ?>
                        <img src="/ecommerce/<?= htmlspecialchars($u['photo']) ?>" class="avatar-img">
                    <?php else: ?>
                        <div class="avatar-init" style="background:<?= getAvatarColor($u['first_name']) ?>">
                            <?= getInitiales($u['first_name'],$u['last_name']) ?>
                        </div>
                    <?php endif; ?>
                    <div>
                        <div style="font-weight:700"><?= htmlspecialchars($u['first_name'].' '.$u['last_name']) ?></div>
                        <div style="color:#9a7c5c;font-size:0.75rem">@<?= htmlspecialchars($u['username']) ?></div>
                    </div>
                </div>
            </td>
            <td>
                <div style="font-size:0.82rem;color:#6B3A2A"><?= htmlspecialchars($u['email']) ?></div>
                <?php if(!empty($u['phone'])): ?>
                    <div style="font-size:0.75rem;color:#9a7c5c"><?= htmlspecialchars($u['phone']) ?></div>
                <?php endif; ?>
            </td>
            <td style="font-weight:700;text-align:center"><?= $u['nb_commandes'] ?></td>
            <td style="font-weight:700;color:#C1622F"><?= number_format($u['total_depense']??0,2) ?>€</td>
            <td style="text-align:center"><?= $u['nb_rdv'] ?></td>
            <td style="color:#9a7c5c;font-size:0.78rem"><?= date('d/m/Y',strtotime($u['created_at'])) ?></td>
            <td>
                <a href="?detail=<?= $u['id'] ?>" class="pbtn pbtn-sm"> Voir</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div style="text-align:center;padding:40px;color:#9a7c5c">
        <div style="font-size:3rem;margin-bottom:15px">👥</div>
        <h5 style="color:#3E1F0D">Aucun client trouvé</h5>
    </div>
    <?php endif; ?>
    </div>
</div>

<!-- DETAIL UTILISATEUR -->
<?php if($detail_user): ?>
<div class="detail-overlay" onclick="if(event.target===this)window.location='users.php'">
    <div class="detail-modal">
        <div class="detail-modal-header">
            <div style="display:flex;align-items:center;gap:12px">
                <?php if(!empty($detail_user['photo'])): ?>
                    <img src="/ecommerce/<?= htmlspecialchars($detail_user['photo']) ?>" style="width:50px;height:50px;border-radius:50%;object-fit:cover;border:2px solid rgba(201,168,76,0.5)">
                <?php else: ?>
                    <div style="width:50px;height:50px;border-radius:50%;background:<?= getAvatarColor($detail_user['first_name']) ?>;display:flex;align-items:center;justify-content:center;font-weight:900;color:#fff;font-size:1.1rem;font-family:'Playfair Display',serif">
                        <?= getInitiales($detail_user['first_name'],$detail_user['last_name']) ?>
                    </div>
                <?php endif; ?>
                <div>
                    <h5 style="font-family:'Playfair Display',serif;color:#C9A84C;margin:0;font-weight:700"><?= htmlspecialchars($detail_user['first_name'].' '.$detail_user['last_name']) ?></h5>
                    <p style="color:rgba(255,255,255,0.6);font-size:0.8rem;margin:2px 0 0">@<?= htmlspecialchars($detail_user['username']) ?></p>
                </div>
            </div>
            <a href="users.php" class="close-btn">✕</a>
        </div>
        <div class="detail-modal-body">

            <!-- INFOS -->
            <div style="background:#F5E6D3;border-radius:12px;padding:18px;margin-bottom:20px">
                <div class="row g-2">
                    <div class="col-6"><span style="font-size:0.75rem;color:#9a7c5c">Email</span><br><strong style="font-size:0.88rem;color:#3E1F0D"><?= htmlspecialchars($detail_user['email']) ?></strong></div>
                    <div class="col-6"><span style="font-size:0.75rem;color:#9a7c5c">Téléphone</span><br><strong style="font-size:0.88rem;color:#3E1F0D"><?= htmlspecialchars($detail_user['phone']??'-') ?></strong></div>
                    <div class="col-6 mt-2"><span style="font-size:0.75rem;color:#9a7c5c">Statut</span><br><span class="badge-user">👤 Client</span></div>
                    <div class="col-6 mt-2"><span style="font-size:0.75rem;color:#9a7c5c">Inscrit le</span><br><strong style="font-size:0.88rem;color:#3E1F0D"><?= date('d/m/Y',strtotime($detail_user['created_at'])) ?></strong></div>
                </div>
            </div>

            <!-- STATS USER -->
            <div class="row g-3 mb-4">
                <div class="col-6">
                    <div style="background:#fff;border:1px solid #F5E6D3;border-radius:12px;padding:14px;text-align:center">
                        <div style="font-family:'Playfair Display',serif;font-size:1.5rem;font-weight:900;color:#3E1F0D"><?= count($user_orders) ?></div>
                        <div style="font-size:0.72rem;color:#9a7c5c">Commandes</div>
                    </div>
                </div>
                <div class="col-6">
                    <div style="background:#fff;border:1px solid #F5E6D3;border-radius:12px;padding:14px;text-align:center">
                        <div style="font-family:'Playfair Display',serif;font-size:1.5rem;font-weight:900;color:#C1622F"><?= number_format(array_sum(array_column($user_orders,'total_amount')),0) ?>€</div>
                        <div style="font-size:0.72rem;color:#9a7c5c">Dépenses totales</div>
                    </div>
                </div>
            </div>

            <!-- DERNIERES COMMANDES -->
            <?php if(count($user_orders) > 0): ?>
            <h6 style="color:#3E1F0D;font-weight:700;margin-bottom:12px"> Dernières commandes</h6>
            <?php
            $sl=['pending'=>['En attente','#F57F17'],'processing'=>['En cours','#1565C0'],'shipped'=>['Expédiée','#6A1B9A'],'delivered'=>['Livrée','#2E7D32'],'cancelled'=>['Annulée','#C62828']];
            foreach($user_orders as $o):
                $s=$sl[$o['status']]??['?','#9a7c5c'];
            ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #F5E6D3;flex-wrap:wrap;gap:5px">
                <div>
                    <div style="font-weight:700;color:#3E1F0D;font-size:0.85rem"><?= htmlspecialchars($o['order_number']) ?></div>
                    <div style="color:#9a7c5c;font-size:0.75rem"><?= date('d/m/Y',strtotime($o['created_at'])) ?></div>
                </div>
                <div style="font-weight:800;color:#C1622F"><?= number_format($o['total_amount'],2) ?>€</div>
                <span style="background:<?= $s[1] ?>22;color:<?= $s[1] ?>;padding:3px 10px;border-radius:8px;font-size:0.72rem;font-weight:700"><?= $s[0] ?></span>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <p style="color:#9a7c5c;font-size:0.85rem;text-align:center;padding:20px 0">Aucune commande pour ce client</p>
            <?php endif; ?>

        </div>
    </div>
</div>
<?php endif; ?>

</div></div>
<?php include 'footer_admin.php'; ?>