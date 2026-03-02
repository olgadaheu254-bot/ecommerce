<?php
require_once '../config/database.php';
$page_title = 'Gestion Produits - HairRoots Admin';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /ecommerce/user/login.php');
    exit;
}

$success = ''; $error = '';

// SUPPRIMER PRODUIT
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("UPDATE products SET active=0 WHERE id=?")->execute([$id]);
    $success = "Produit desactive avec succes.";
}

// TOGGLE FEATURED
if(isset($_GET['toggle_featured'])) {
    $id = (int)$_GET['toggle_featured'];
    $pdo->prepare("UPDATE products SET featured = NOT featured WHERE id=?")->execute([$id]);
    header('Location: products.php'); exit;
}

// AJOUTER / MODIFIER PRODUIT
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id          = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $name        = trim($_POST['name']);
    $category_id = (int)$_POST['category_id'];
    $price       = (float)$_POST['price'];
    $stock       = (int)$_POST['stock'];
    $description = trim($_POST['description']);
    $image       = trim($_POST['image']);
    $sku         = trim($_POST['sku']);
    $featured    = isset($_POST['featured']) ? 1 : 0;
    $active      = isset($_POST['active']) ? 1 : 0;
    $slug        = strtolower(preg_replace('/[^a-zA-Z0-9]+/','-', $name));

    if(empty($name) || $price <= 0 || $category_id <= 0) {
        $error = "Nom, prix et categorie sont obligatoires.";
    } else {
        if($id > 0) {
            $stmt = $pdo->prepare("UPDATE products SET name=?,slug=?,category_id=?,price=?,stock=?,description=?,image=?,sku=?,featured=?,active=? WHERE id=?");
            if($stmt->execute([$name,$slug,$category_id,$price,$stock,$description,$image,$sku,$featured,$active,$id])) {
                $success = "Produit mis a jour !";
            } else { $error = "Erreur lors de la mise a jour."; }
        } else {
            $stmt = $pdo->prepare("INSERT INTO products (name,slug,category_id,price,stock,description,image,sku,featured,active) VALUES (?,?,?,?,?,?,?,?,?,?)");
            if($stmt->execute([$name,$slug,$category_id,$price,$stock,$description,$image,$sku,$featured,$active])) {
                $success = "Produit ajoute avec succes !";
            } else { $error = "Erreur lors de l'ajout."; }
        }
    }
}

// PRODUIT A MODIFIER
$edit_product = null;
if(isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit_product = $stmt->fetch();
}

// FILTRES
$filtre_cat   = isset($_GET['cat'])    ? (int)$_GET['cat']       : 0;
$filtre_stock = isset($_GET['stock'])  ? $_GET['stock']           : '';
$search       = isset($_GET['search']) ? trim($_GET['search'])    : '';

$where = ["active = 1"]; $params = [];
if($filtre_cat)   { $where[] = "p.category_id = ?"; $params[] = $filtre_cat; }
if($filtre_stock === 'rupture') { $where[] = "p.stock = 0"; }
elseif($filtre_stock === 'faible') { $where[] = "p.stock > 0 AND p.stock <= 5"; }
if($search) { $where[] = "p.name LIKE ?"; $params[] = "%$search%"; }
$where_sql = 'WHERE '.implode(' AND ', $where);

$stmt = $pdo->prepare("SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id $where_sql ORDER BY p.id DESC");
$stmt->execute($params);
$products = $stmt->fetchAll();

// Categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

include '../includes/header.php';
?>
<style>
.admin-page{background:#FDF8F2;min-height:80vh;padding:30px 0}
.dash-header{background:linear-gradient(135deg,#3E1F0D,#6B3A2A);border-radius:20px;padding:22px 28px;margin-bottom:25px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:15px}
.dash-header h1{font-family:'Playfair Display',serif;color:#C9A84C;font-size:1.6rem;font-weight:900;margin:0}
.dash-nav{display:flex;gap:8px;flex-wrap:wrap}
.dash-nav-btn{background:rgba(201,168,76,0.15);color:#C9A84C;border:1px solid rgba(201,168,76,0.3);border-radius:10px;padding:7px 14px;font-size:0.8rem;font-weight:600;text-decoration:none;transition:all 0.3s}
.dash-nav-btn:hover,.dash-nav-btn.active{background:#C9A84C;color:#3E1F0D}
.dash-card{background:#fff;border-radius:18px;box-shadow:0 4px 20px rgba(62,31,13,0.06);border:1px solid #F5E6D3;overflow:hidden;margin-bottom:25px}
.dash-card-header{background:linear-gradient(135deg,#F5E6D3,#FDEBD0);padding:16px 22px;border-bottom:1px solid #F0D9C0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px}
.dash-card-header h5{font-family:'Playfair Display',serif;color:#3E1F0D;font-weight:700;margin:0;font-size:1rem}
.dash-card-body{padding:22px}
.pinput{border:2px solid #F5E6D3;border-radius:10px;padding:10px 14px;font-size:0.88rem;transition:all 0.3s;background:#FDFAF7;width:100%}
.pinput:focus{border-color:#C9A84C;box-shadow:0 0 0 3px rgba(201,168,76,0.1);outline:none;background:#fff}
.plabel{font-weight:600;font-size:0.82rem;color:#3E1F0D;margin-bottom:5px;display:block}
.pbtn{background:linear-gradient(135deg,#C9A84C,#b8942e);color:#3E1F0D;border:none;border-radius:10px;padding:10px 22px;font-weight:700;font-size:0.88rem;cursor:pointer;transition:all 0.3s;text-decoration:none;display:inline-block}
.pbtn:hover{background:linear-gradient(135deg,#C1622F,#a0491f);color:#fff}
.pbtn-sm{padding:6px 14px;font-size:0.78rem}
.pbtn-danger{background:#fce4e4;color:#c62828;border:none;border-radius:8px;padding:6px 12px;font-size:0.78rem;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block;transition:all 0.2s}
.pbtn-danger:hover{background:#c62828;color:#fff}
.pbtn-info{background:#E3F2FD;color:#1565C0;border:none;border-radius:8px;padding:6px 12px;font-size:0.78rem;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block;transition:all 0.2s}
.pbtn-info:hover{background:#1565C0;color:#fff}
.pbtn-success{background:#e8f5e9;color:#2e7d32;border:none;border-radius:8px;padding:6px 12px;font-size:0.78rem;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block;transition:all 0.2s}
.pbtn-success:hover{background:#2e7d32;color:#fff}
.ptable{width:100%;border-collapse:collapse}
.ptable th{padding:11px 14px;font-weight:700;font-size:0.78rem;color:#9a7c5c;text-align:left;border-bottom:2px solid #F5E6D3;text-transform:uppercase;background:#FDFAF7}
.ptable td{padding:12px 14px;font-size:0.85rem;color:#3E1F0D;border-bottom:1px solid #F5E6D3;vertical-align:middle}
.ptable tr:last-child td{border-bottom:none}
.ptable tr:hover td{background:#FDFAF7}
.stock-ok{color:#2e7d32;font-weight:700;font-size:0.82rem}
.stock-low{color:#F57F17;font-weight:700;font-size:0.82rem}
.stock-out{color:#c62828;font-weight:700;font-size:0.82rem}
.alert-hr{border-radius:12px;padding:12px 18px;margin-bottom:20px;font-size:0.9rem;border:none}
.alert-hr.success{background:#e8f5e9;color:#2e7d32;border-left:4px solid #2e7d32}
.alert-hr.error{background:#fce4e4;color:#c62828;border-left:4px solid #c62828}
.prod-img{width:45px;height:45px;border-radius:8px;object-fit:cover;border:1px solid #F5E6D3}
.prod-img-ph{width:45px;height:45px;border-radius:8px;background:#F5E6D3;display:flex;align-items:center;justify-content:center;font-size:1.2rem}
.filter-bar{background:#fff;border-radius:14px;box-shadow:0 4px 15px rgba(62,31,13,0.05);border:1px solid #F5E6D3;padding:16px 20px;margin-bottom:20px;display:flex;align-items:center;gap:10px;flex-wrap:wrap}
</style>

<div class="admin-page"><div class="container">

<div class="dash-header">
    <div>
        <h1>📦 Gestion des Produits</h1>
        <p style="color:rgba(255,255,255,0.6);margin:4px 0 0;font-size:0.82rem"><?= count($products) ?> produit(s) affiches</p>
    </div>
    <div class="dash-nav">
        <a href="index.php" class="dash-nav-btn">📊 Dashboard</a>
        <a href="products.php" class="dash-nav-btn active">📦 Produits</a>
        <a href="orders.php" class="dash-nav-btn">🛍️ Commandes</a>
        <a href="users.php" class="dash-nav-btn">👥 Utilisateurs</a>
        <a href="appointments.php" class="dash-nav-btn">📅 RDV</a>
    </div>
</div>

<?php if($success): ?><div class="alert-hr success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if($error):   ?><div class="alert-hr error">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>

<!-- FORMULAIRE AJOUT / MODIFICATION -->
<div class="dash-card">
    <div class="dash-card-header">
        <h5><?= $edit_product ? '✏️ Modifier le produit' : '➕ Ajouter un produit' ?></h5>
        <?php if($edit_product): ?>
            <a href="products.php" class="pbtn pbtn-sm">+ Nouveau produit</a>
        <?php endif; ?>
    </div>
    <div class="dash-card-body">
        <form method="POST" action="">
            <?php if($edit_product): ?>
                <input type="hidden" name="product_id" value="<?= $edit_product['id'] ?>">
            <?php endif; ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="plabel">Nom du produit *</label>
                    <input type="text" class="pinput" name="name" value="<?= htmlspecialchars($edit_product['name']??'') ?>" placeholder="Ex: Meche Bouclees Premium" required>
                </div>
                <div class="col-md-3">
                    <label class="plabel">Categorie *</label>
                    <select class="pinput" name="category_id" required>
                        <option value="">Choisir...</option>
                        <?php foreach($categories as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($edit_product['category_id']??'')==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="plabel">SKU / Reference</label>
                    <input type="text" class="pinput" name="sku" value="<?= htmlspecialchars($edit_product['sku']??'') ?>" placeholder="Ex: BOUCL-001">
                </div>
                <div class="col-md-3">
                    <label class="plabel">Prix (€) *</label>
                    <input type="number" class="pinput" name="price" step="0.01" min="0" value="<?= $edit_product['price']??'' ?>" placeholder="0.00" required>
                </div>
                <div class="col-md-3">
                    <label class="plabel">Stock</label>
                    <input type="number" class="pinput" name="stock" min="0" value="<?= $edit_product['stock']??0 ?>">
                </div>
                <div class="col-md-6">
                    <label class="plabel">URL de l'image</label>
                    <input type="text" class="pinput" name="image" value="<?= htmlspecialchars($edit_product['image']??'') ?>" placeholder="https://...">
                </div>
                <div class="col-12">
                    <label class="plabel">Description</label>
                    <textarea class="pinput" name="description" rows="3" placeholder="Description du produit..."><?= htmlspecialchars($edit_product['description']??'') ?></textarea>
                </div>
                <div class="col-md-4 d-flex align-items-center gap-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="featured" id="featured" <?= ($edit_product['featured']??0)?'checked':'' ?> style="border-color:#C9A84C;accent-color:#C9A84C">
                        <label class="form-check-label" for="featured" style="color:#3E1F0D;font-weight:600;font-size:0.85rem">⭐ Produit vedette</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="active" id="active" <?= ($edit_product['active']??1)?'checked':'' ?> style="border-color:#C9A84C;accent-color:#C9A84C">
                        <label class="form-check-label" for="active" style="color:#3E1F0D;font-weight:600;font-size:0.85rem">✅ Actif</label>
                    </div>
                </div>
                <div class="col-md-8 text-end">
                    <button type="submit" class="pbtn">
                        <?= $edit_product ? '💾 Mettre a jour' : '➕ Ajouter le produit' ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- FILTRES -->
<div class="filter-bar">
    <form method="GET" action="" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;width:100%">
        <input type="text" class="pinput" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="🔍 Rechercher un produit..." style="max-width:220px">
        <select class="pinput" name="cat" style="max-width:180px">
            <option value="">Toutes categories</option>
            <?php foreach($categories as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $filtre_cat==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select class="pinput" name="stock" style="max-width:160px">
            <option value="">Tous les stocks</option>
            <option value="rupture" <?= $filtre_stock==='rupture'?'selected':'' ?>>❌ Rupture</option>
            <option value="faible" <?= $filtre_stock==='faible'?'selected':'' ?>>⚠️ Stock faible</option>
        </select>
        <button type="submit" class="pbtn pbtn-sm">Filtrer</button>
        <a href="products.php" class="pbtn-info" style="padding:8px 14px">Reset</a>
    </form>
</div>

<!-- LISTE PRODUITS -->
<div class="dash-card">
    <div class="dash-card-header">
        <h5>📋 Liste des produits (<?= count($products) ?>)</h5>
    </div>
    <div style="overflow-x:auto">
        <?php if(count($products) > 0): ?>
        <table class="ptable">
            <thead><tr>
                <th>Image</th>
                <th>Nom</th>
                <th>Categorie</th>
                <th>Prix</th>
                <th>Stock</th>
                <th>Vedette</th>
                <th>Actions</th>
            </tr></thead>
            <tbody>
            <?php foreach($products as $p): ?>
            <tr>
                <td>
                    <?php if(!empty($p['image'])): ?>
                        <img src="<?= htmlspecialchars($p['image']) ?>" alt="" class="prod-img">
                    <?php else: ?>
                        <div class="prod-img-ph">🌿</div>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="font-weight:700"><?= htmlspecialchars($p['name']) ?></div>
                    <?php if(!empty($p['sku'])): ?>
                        <div style="color:#9a7c5c;font-size:0.72rem"><?= htmlspecialchars($p['sku']) ?></div>
                    <?php endif; ?>
                </td>
                <td><span style="background:#F5E6D3;color:#6B3A2A;padding:3px 10px;border-radius:8px;font-size:0.75rem;font-weight:600"><?= htmlspecialchars($p['cat_name']??'-') ?></span></td>
                <td style="font-weight:800;color:#C1622F"><?= number_format($p['price'],2) ?>€</td>
                <td>
                    <?php if($p['stock'] > 10): ?>
                        <span class="stock-ok">✅ <?= $p['stock'] ?></span>
                    <?php elseif($p['stock'] > 0): ?>
                        <span class="stock-low">⚠️ <?= $p['stock'] ?></span>
                    <?php else: ?>
                        <span class="stock-out">❌ 0</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="?toggle_featured=<?= $p['id'] ?>" style="font-size:1.2rem;text-decoration:none" title="<?= $p['featured']?'Retirer vedette':'Mettre en vedette' ?>">
                        <?= $p['featured'] ? '⭐' : '☆' ?>
                    </a>
                </td>
                <td>
                    <div style="display:flex;gap:5px;flex-wrap:wrap">
                        <a href="?edit=<?= $p['id'] ?>" class="pbtn-info">✏️ Modifier</a>
                        <a href="/ecommerce/products/detail.php?id=<?= $p['id'] ?>" class="pbtn-success" target="_blank">👁️ Voir</a>
                        <a href="?delete=<?= $p['id'] ?>" class="pbtn-danger" onclick="return confirm('Desactiver ce produit ?')">🗑️ Suppr.</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div style="text-align:center;padding:40px;color:#9a7c5c">
            <div style="font-size:3rem;margin-bottom:15px">📦</div>
            <h5 style="color:#3E1F0D">Aucun produit trouve</h5>
            <p>Ajoutez votre premier produit avec le formulaire ci-dessus</p>
        </div>
        <?php endif; ?>
    </div>
</div>

</div></div>
<?php include '../includes/footer.php'; ?>