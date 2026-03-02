<?php
require_once '../config/database.php';
$page_title = 'Dashboard Admin - HairRoots';

// Protection admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /ecommerce/user/login.php');
    exit;
}

// STATS COMMANDES
$stats_commandes = $pdo->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as en_attente,
    SUM(CASE WHEN status='processing' THEN 1 ELSE 0 END) as en_cours,
    SUM(CASE WHEN status='delivered' THEN 1 ELSE 0 END) as livrees,
    SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) as annulees,
    SUM(CASE WHEN status != 'cancelled' THEN total_amount ELSE 0 END) as revenu_total,
    SUM(CASE WHEN DATE(created_at) = CURDATE() AND status != 'cancelled' THEN total_amount ELSE 0 END) as revenu_jour,
    SUM(CASE WHEN MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW()) AND status != 'cancelled' THEN total_amount ELSE 0 END) as revenu_mois
FROM orders")->fetch();

// STATS PRODUITS
$stats_produits = $pdo->query("SELECT
    COUNT(*) as total,
    SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) as rupture,
    SUM(CASE WHEN stock > 0 AND stock <= 5 THEN 1 ELSE 0 END) as stock_faible,
    SUM(CASE WHEN featured = 1 THEN 1 ELSE 0 END) as vedettes
FROM products WHERE active = 1")->fetch();

// STATS UTILISATEURS
$stats_users = $pdo->query("SELECT
    COUNT(*) as total,
    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as nouveaux_jour,
    SUM(CASE WHEN MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW()) THEN 1 ELSE 0 END) as nouveaux_mois
FROM users WHERE role = 'user'")->fetch();

// STATS RDV
$stats_rdv = $pdo->query("SELECT
    COUNT(*) as total,
    SUM(CASE WHEN statut='en attente' THEN 1 ELSE 0 END) as en_attente,
    SUM(CASE WHEN statut='confirme' THEN 1 ELSE 0 END) as confirmes,
    SUM(CASE WHEN date_rdv = CURDATE() THEN 1 ELSE 0 END) as aujourd_hui
FROM appointments")->fetch();

// DERNIERES COMMANDES
$dernieres_commandes = $pdo->query("SELECT o.*, u.first_name, u.last_name FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 6")->fetchAll();

// DERNIERS UTILISATEURS
$derniers_users = $pdo->query("SELECT * FROM users WHERE role='user' ORDER BY created_at DESC LIMIT 5")->fetchAll();

// PRODUITS EN RUPTURE
$ruptures = $pdo->query("SELECT * FROM products WHERE stock = 0 AND active = 1 LIMIT 5")->fetchAll();

// PROCHAINS RDV
$prochains_rdv = $pdo->query("SELECT a.*, c.prenom as c_prenom, c.nom as c_nom FROM appointments a LEFT JOIN coiffeuses c ON a.coiffeuse_id = c.id WHERE a.date_rdv >= CURDATE() AND a.statut != 'annule' ORDER BY a.date_rdv ASC, a.heure_rdv ASC LIMIT 5")->fetchAll();

// REVENUS PAR MOIS (6 derniers mois)
$revenus_mois = $pdo->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as mois, DATE_FORMAT(created_at, '%b %Y') as mois_label, SUM(total_amount) as total FROM orders WHERE status != 'cancelled' AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY mois ASC")->fetchAll();

include '../includes/header.php';
?>
<style>
.admin-dash{background:#FDF8F2;min-height:80vh;padding:30px 0}

/* HEADER */
.dash-header{background:linear-gradient(135deg,#3E1F0D,#6B3A2A);border-radius:20px;padding:25px 30px;margin-bottom:30px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:15px}
.dash-header h1{font-family:'Playfair Display',serif;color:#C9A84C;font-size:1.8rem;font-weight:900;margin:0}
.dash-header p{color:rgba(255,255,255,0.6);margin:4px 0 0;font-size:0.88rem}
.dash-nav{display:flex;gap:8px;flex-wrap:wrap}
.dash-nav-btn{background:rgba(201,168,76,0.15);color:#C9A84C;border:1px solid rgba(201,168,76,0.3);border-radius:10px;padding:8px 16px;font-size:0.82rem;font-weight:600;text-decoration:none;transition:all 0.3s}
.dash-nav-btn:hover{background:#C9A84C;color:#3E1F0D}
.dash-nav-btn.active{background:#C9A84C;color:#3E1F0D}

/* STAT CARDS */
.stat-card{background:#fff;border-radius:18px;padding:22px;box-shadow:0 4px 20px rgba(62,31,13,0.06);border:1px solid #F5E6D3;transition:all 0.3s;height:100%}
.stat-card:hover{transform:translateY(-4px);box-shadow:0 12px 30px rgba(62,31,13,0.1)}
.stat-icon{width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;margin-bottom:15px}
.stat-num{font-family:'Playfair Display',serif;font-size:2rem;font-weight:900;color:#3E1F0D;margin-bottom:3px}
.stat-label{color:#9a7c5c;font-size:0.82rem;font-weight:500}
.stat-sub{font-size:0.75rem;margin-top:8px;padding-top:8px;border-top:1px solid #F5E6D3}
.stat-sub span{font-weight:700}
.trend-up{color:#2e7d32}.trend-down{color:#c62828}.trend-neutral{color:#9a7c5c}

/* SECTION CARDS */
.dash-card{background:#fff;border-radius:18px;box-shadow:0 4px 20px rgba(62,31,13,0.06);border:1px solid #F5E6D3;overflow:hidden;margin-bottom:25px}
.dash-card-header{background:linear-gradient(135deg,#F5E6D3,#FDEBD0);padding:16px 22px;border-bottom:1px solid #F0D9C0;display:flex;align-items:center;justify-content:space-between}
.dash-card-header h5{font-family:'Playfair Display',serif;color:#3E1F0D;font-weight:700;margin:0;font-size:1rem}
.dash-card-body{padding:20px}
.dash-link{color:#C1622F;font-size:0.8rem;font-weight:600;text-decoration:none}
.dash-link:hover{color:#3E1F0D}

/* TABLEAU */
.dash-table{width:100%;border-collapse:collapse}
.dash-table th{padding:10px 12px;font-weight:700;font-size:0.78rem;color:#9a7c5c;text-align:left;border-bottom:1px solid #F5E6D3;text-transform:uppercase}
.dash-table td{padding:12px;font-size:0.85rem;color:#3E1F0D;border-bottom:1px solid #F5E6D3;vertical-align:middle}
.dash-table tr:last-child td{border-bottom:none}
.dash-table tr:hover td{background:#FDFAF7}

/* BADGES */
.b{padding:3px 10px;border-radius:8px;font-size:0.72rem;font-weight:700;display:inline-block}
.b-attente{background:#FFF8E1;color:#F57F17}
.b-process{background:#E3F2FD;color:#1565C0}
.b-shipped{background:#F3E5F5;color:#6A1B9A}
.b-delivered{background:#E8F5E9;color:#2E7D32}
.b-cancelled{background:#FCE4E4;color:#C62828}

/* CHART BARS */
.chart-bar-wrap{display:flex;align-items:flex-end;gap:8px;height:120px;padding:10px 0}
.chart-bar-item{flex:1;display:flex;flex-direction:column;align-items:center;gap:4px}
.chart-bar{background:linear-gradient(180deg,#C9A84C,#C1622F);border-radius:6px 6px 0 0;width:100%;min-height:4px;transition:all 0.5s}
.chart-bar-label{font-size:0.68rem;color:#9a7c5c;text-align:center;white-space:nowrap}
.chart-bar-val{font-size:0.7rem;font-weight:700;color:#3E1F0D}

/* ACTIVITE RECENTE */
.activity-item{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #F5E6D3}
.activity-item:last-child{border-bottom:none}
.activity-dot{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0}
.activity-text{font-size:0.85rem;color:#3E1F0D;flex:1}
.activity-time{font-size:0.75rem;color:#9a7c5c;white-space:nowrap}

/* QUICK ACTIONS */
.quick-actions{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:25px}
.qa-btn{background:#fff;border:2px solid #F5E6D3;border-radius:14px;padding:16px;text-align:center;text-decoration:none;transition:all 0.3s;display:block}
.qa-btn:hover{border-color:#C9A84C;background:#FFFDF5;transform:translateY(-2px)}
.qa-btn-icon{font-size:1.8rem;margin-bottom:6px}
.qa-btn-label{font-weight:700;color:#3E1F0D;font-size:0.82rem}
</style>

<div class="admin-dash"><div class="container">

<!-- HEADER -->
<div class="dash-header">
    <div>
        <h1>👑 Dashboard Admin</h1>
        <p>Bonjour <?= htmlspecialchars($_SESSION['first_name']) ?> · <?= date('d/m/Y') ?></p>
    </div>
    <div class="dash-nav">
        <a href="index.php" class="dash-nav-btn active">📊 Vue generale</a>
        <a href="products.php" class="dash-nav-btn">📦 Produits</a>
        <a href="orders.php" class="dash-nav-btn">🛍️ Commandes</a>
        <a href="users.php" class="dash-nav-btn">👥 Utilisateurs</a>
        <a href="appointments.php" class="dash-nav-btn">📅 RDV</a>
    </div>
</div>

<!-- STATS PRINCIPALES -->
<div class="row g-3 mb-4">

    <!-- REVENU -->
    <div class="col-lg-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon" style="background:linear-gradient(135deg,#FFF8E1,#FDEBD0)">💰</div>
            <div class="stat-num"><?= number_format($stats_commandes['revenu_total']??0,0,'.',''.' ') ?>€</div>
            <div class="stat-label">Revenu total</div>
            <div class="stat-sub">
                Aujourd'hui : <span class="trend-up"><?= number_format($stats_commandes['revenu_jour']??0,2) ?>€</span><br>
                Ce mois : <span class="trend-up"><?= number_format($stats_commandes['revenu_mois']??0,2) ?>€</span>
            </div>
        </div>
    </div>

    <!-- COMMANDES -->
    <div class="col-lg-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon" style="background:linear-gradient(135deg,#E3F2FD,#BBDEFB)">🛍️</div>
            <div class="stat-num"><?= $stats_commandes['total']??0 ?></div>
            <div class="stat-label">Commandes totales</div>
            <div class="stat-sub">
                En attente : <span class="trend-down"><?= $stats_commandes['en_attente']??0 ?></span><br>
                Livrees : <span class="trend-up"><?= $stats_commandes['livrees']??0 ?></span>
            </div>
        </div>
    </div>

    <!-- UTILISATEURS -->
    <div class="col-lg-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon" style="background:linear-gradient(135deg,#E8F5E9,#C8E6C9)">👥</div>
            <div class="stat-num"><?= $stats_users['total']??0 ?></div>
            <div class="stat-label">Clients inscrits</div>
            <div class="stat-sub">
                Nouveaux aujourd'hui : <span class="trend-up"><?= $stats_users['nouveaux_jour']??0 ?></span><br>
                Ce mois : <span class="trend-up"><?= $stats_users['nouveaux_mois']??0 ?></span>
            </div>
        </div>
    </div>

    <!-- RDV -->
    <div class="col-lg-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon" style="background:linear-gradient(135deg,#F3E5F5,#E1BEE7)">📅</div>
            <div class="stat-num"><?= $stats_rdv['total']??0 ?></div>
            <div class="stat-label">Rendez-vous</div>
            <div class="stat-sub">
                Aujourd'hui : <span class="trend-up"><?= $stats_rdv['aujourd_hui']??0 ?></span><br>
                En attente : <span class="trend-down"><?= $stats_rdv['en_attente']??0 ?></span>
            </div>
        </div>
    </div>
</div>

<!-- ALERTES -->
<?php if(($stats_produits['rupture']??0) > 0 || ($stats_produits['stock_faible']??0) > 0): ?>
<div style="background:#FFF8E1;border:1px solid #F57F17;border-radius:14px;padding:14px 20px;margin-bottom:25px;display:flex;align-items:center;gap:12px;flex-wrap:wrap">
    <span style="font-size:1.4rem">⚠️</span>
    <div style="flex:1">
        <?php if($stats_produits['rupture'] > 0): ?>
            <strong style="color:#F57F17"><?= $stats_produits['rupture'] ?> produit(s) en rupture de stock</strong>
        <?php endif; ?>
        <?php if($stats_produits['stock_faible'] > 0): ?>
            <?php if($stats_produits['rupture'] > 0): ?> · <?php endif; ?>
            <span style="color:#F57F17"><?= $stats_produits['stock_faible'] ?> produit(s) avec stock faible (≤5)</span>
        <?php endif; ?>
    </div>
    <a href="products.php" style="background:#F57F17;color:#fff;padding:7px 16px;border-radius:8px;font-size:0.82rem;font-weight:700;text-decoration:none">Voir les produits</a>
</div>
<?php endif; ?>

<div class="row g-4">

    <!-- COLONNE GAUCHE -->
    <div class="col-lg-8">

        <!-- GRAPHIQUE REVENUS -->
        <?php if(count($revenus_mois) > 0):
            $max_rev = max(array_column($revenus_mois, 'total'));
        ?>
        <div class="dash-card">
            <div class="dash-card-header">
                <h5>📈 Revenus des 6 derniers mois</h5>
            </div>
            <div class="dash-card-body">
                <div class="chart-bar-wrap">
                    <?php foreach($revenus_mois as $r):
                        $height = $max_rev > 0 ? max(4, ($r['total']/$max_rev)*100) : 4;
                    ?>
                    <div class="chart-bar-item">
                        <div class="chart-bar-val"><?= number_format($r['total'],0) ?>€</div>
                        <div class="chart-bar" style="height:<?= $height ?>%"></div>
                        <div class="chart-bar-label"><?= htmlspecialchars($r['mois_label']) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- DERNIERES COMMANDES -->
        <div class="dash-card">
            <div class="dash-card-header">
                <h5>🛍️ Dernieres commandes</h5>
                <a href="orders.php" class="dash-link">Voir toutes →</a>
            </div>
            <div class="dash-card-body" style="padding:0">
                <?php if(count($dernieres_commandes) > 0): ?>
                <div style="overflow-x:auto">
                <table class="dash-table">
                    <thead><tr>
                        <th>N° Commande</th>
                        <th>Client</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th>Date</th>
                    </tr></thead>
                    <tbody>
                    <?php
                    $sl=['pending'=>['En attente','b-attente'],'processing'=>['En cours','b-process'],'shipped'=>['Expediee','b-shipped'],'delivered'=>['Livree','b-delivered'],'cancelled'=>['Annulee','b-cancelled']];
                    foreach($dernieres_commandes as $o):
                        $s=$sl[$o['status']]??['?','b-attente'];
                    ?>
                    <tr>
                        <td style="font-weight:700;color:#C1622F"><?= htmlspecialchars($o['order_number']) ?></td>
                        <td><?= htmlspecialchars($o['first_name'].' '.$o['last_name']) ?></td>
                        <td style="font-weight:700"><?= number_format($o['total_amount'],2) ?>€</td>
                        <td><span class="b <?= $s[1] ?>"><?= $s[0] ?></span></td>
                        <td style="color:#9a7c5c;font-size:0.78rem"><?= date('d/m/Y H:i',strtotime($o['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php else: ?>
                <div style="text-align:center;padding:30px;color:#9a7c5c">Aucune commande pour le moment</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- PROCHAINS RDV -->
        <?php if(count($prochains_rdv) > 0): ?>
        <div class="dash-card">
            <div class="dash-card-header">
                <h5>📅 Prochains rendez-vous</h5>
                <a href="appointments.php" class="dash-link">Voir tous →</a>
            </div>
            <div class="dash-card-body" style="padding:0">
                <table class="dash-table">
                    <thead><tr><th>Client</th><th>Coiffeuse</th><th>Date</th><th>Prestation</th><th>Statut</th></tr></thead>
                    <tbody>
                    <?php foreach($prochains_rdv as $r): ?>
                    <tr>
                        <td style="font-weight:600"><?= htmlspecialchars($r['prenom_client'].' '.$r['nom_client']) ?></td>
                        <td style="color:#6B3A2A"><?= htmlspecialchars($r['c_prenom'].' '.$r['c_nom']) ?></td>
                        <td style="font-weight:700;color:#C1622F"><?= date('d/m/Y',strtotime($r['date_rdv'])) ?> <span style="color:#9a7c5c;font-weight:400"><?= $r['heure_rdv'] ?></span></td>
                        <td><?= htmlspecialchars($r['type_prestation']) ?></td>
                        <td>
                            <?php if($r['statut']==='confirme'): ?>
                                <span class="b b-delivered">Confirme</span>
                            <?php else: ?>
                                <span class="b b-attente">En attente</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- COLONNE DROITE -->
    <div class="col-lg-4">

        <!-- ACTIONS RAPIDES -->
        <div class="dash-card mb-4">
            <div class="dash-card-header"><h5>⚡ Actions rapides</h5></div>
            <div class="dash-card-body">
                <div class="quick-actions">
                    <a href="products.php?action=add" class="qa-btn">
                        <div class="qa-btn-icon">➕</div>
                        <div class="qa-btn-label">Nouveau produit</div>
                    </a>
                    <a href="appointments.php" class="qa-btn">
                        <div class="qa-btn-icon">📅</div>
                        <div class="qa-btn-label">Voir les RDV</div>
                    </a>
                    <a href="orders.php?status=pending" class="qa-btn">
                        <div class="qa-btn-icon">🔔</div>
                        <div class="qa-btn-label">Commandes en attente</div>
                    </a>
                    <a href="users.php" class="qa-btn">
                        <div class="qa-btn-icon">👥</div>
                        <div class="qa-btn-label">Voir les clients</div>
                    </a>
                </div>
            </div>
        </div>

        <!-- PRODUITS EN RUPTURE -->
        <?php if(count($ruptures) > 0): ?>
        <div class="dash-card mb-4">
            <div class="dash-card-header">
                <h5>⚠️ Ruptures de stock</h5>
                <a href="products.php" class="dash-link">Gerer →</a>
            </div>
            <div class="dash-card-body">
                <?php foreach($ruptures as $p): ?>
                <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #F5E6D3">
                    <div style="width:38px;height:38px;border-radius:8px;overflow:hidden;flex-shrink:0">
                        <?php if(!empty($p['image'])): ?>
                            <img src="<?= htmlspecialchars($p['image']) ?>" style="width:100%;height:100%;object-fit:cover">
                        <?php else: ?>
                            <div style="width:100%;height:100%;background:#F5E6D3;display:flex;align-items:center;justify-content:center;font-size:1rem">🌿</div>
                        <?php endif; ?>
                    </div>
                    <div style="flex:1">
                        <div style="font-weight:600;color:#3E1F0D;font-size:0.83rem"><?= htmlspecialchars($p['name']) ?></div>
                        <div style="color:#c62828;font-size:0.72rem;font-weight:600">❌ Rupture</div>
                    </div>
                    <a href="products.php?edit=<?= $p['id'] ?>" style="background:#fce4e4;color:#c62828;padding:4px 10px;border-radius:6px;font-size:0.72rem;font-weight:700;text-decoration:none">Modifier</a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- DERNIERS CLIENTS -->
        <div class="dash-card">
            <div class="dash-card-header">
                <h5>👥 Derniers clients</h5>
                <a href="users.php" class="dash-link">Voir tous →</a>
            </div>
            <div class="dash-card-body">
                <?php foreach($derniers_users as $u): ?>
                <div class="activity-item">
                    <div class="activity-dot" style="background:linear-gradient(135deg,#C9A84C,#C1622F);color:#fff;font-weight:900;font-family:'Playfair Display',serif;font-size:0.9rem">
                        <?= strtoupper(substr($u['first_name'],0,1).substr($u['last_name'],0,1)) ?>
                    </div>
                    <div class="activity-text">
                        <strong><?= htmlspecialchars($u['first_name'].' '.$u['last_name']) ?></strong><br>
                        <span style="color:#9a7c5c;font-size:0.75rem"><?= htmlspecialchars($u['email']) ?></span>
                    </div>
                    <div class="activity-time"><?= date('d/m', strtotime($u['created_at'])) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</div>

</div></div>
<?php include '../includes/footer.php'; ?>