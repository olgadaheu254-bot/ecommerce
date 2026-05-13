<?php
require_once 'auth_admin.php'; 
require_once '../config/database.php';
$page_title = 'Gestion Produits - HairRoots Admin';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /ecommerce/user/login.php');
    exit;
}

$success = ''; $error = '';

// SUPPRIMER UNE IMAGE INDIVIDUELLE
if(isset($_GET['delete_image'])) {
    $img_id = (int)$_GET['delete_image'];
    $product_id = (int)$_GET['pid'];
    $stmt = $pdo->prepare("SELECT image FROM product_images WHERE id=? AND product_id=?");
    $stmt->execute([$img_id, $product_id]);
    $img = $stmt->fetch();
    if($img) {
        $fichier = '../' . $img['image'];
        if(file_exists($fichier)) unlink($fichier);
        $pdo->prepare("DELETE FROM product_images WHERE id=?")->execute([$img_id]);
        $success = "Photo supprimee avec succes.";
    }
    header('Location: products.php?edit='.$product_id); exit;
}

// DEFINIR PHOTO PRINCIPALE
if(isset($_GET['set_main'])) {
    $img_id = (int)$_GET['set_main'];
    $product_id = (int)$_GET['pid'];
    $pdo->prepare("UPDATE product_images SET is_main=0 WHERE product_id=?")->execute([$product_id]);
    $pdo->prepare("UPDATE product_images SET is_main=1 WHERE id=?")->execute([$img_id]);
    $stmt = $pdo->prepare("SELECT image FROM product_images WHERE id=?");
    $stmt->execute([$img_id]);
    $img = $stmt->fetch();
    if($img) {
        $pdo->prepare("UPDATE products SET image=? WHERE id=?")->execute([$img['image'], $product_id]);
    }
    header('Location: products.php?edit='.$product_id); exit;
}

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
    $sku         = trim($_POST['sku']);
    $featured    = isset($_POST['featured']) ? 1 : 0;
    $active      = isset($_POST['active']) ? 1 : 0;
    $image       = trim($_POST['image'] ?? '');

    // Generer le slug
    $slug      = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
    $slug_base = $slug;

    // Verifier si le slug est unique
    $check_slug = $pdo->prepare("SELECT COUNT(*) FROM products WHERE slug = ? AND id != ?");
    $check_slug->execute([$slug, $id > 0 ? $id : 0]);
    if($check_slug->fetchColumn() > 0) {
        $slug = $slug_base . '-' . time();
    }

    // Verifier si le SKU est unique (seulement si un SKU est fourni)
    if(!empty($sku)) {
        $check_sku = $pdo->prepare("SELECT COUNT(*) FROM products WHERE sku = ? AND id != ?");
        $check_sku->execute([$sku, $id > 0 ? $id : 0]);
        if($check_sku->fetchColumn() > 0) {
            $error = "Ce SKU existe deja pour un autre produit. Veuillez en choisir un autre.";
        }
    }

    if(empty($name) || $price <= 0 || $category_id <= 0) {
        $error = "Nom, prix et categorie sont obligatoires.";
    }

    if(empty($error)) {
        $upload_dir = '../assets/images/products/';
        if(!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        $nouvelles_images = [];
        $formats_autorises = ['jpg', 'jpeg', 'png', 'webp'];

        if(!empty($_FILES['photos']['name'][0])) {
            foreach($_FILES['photos']['tmp_name'] as $k => $tmp) {
                if($_FILES['photos']['error'][$k] !== 0) continue;
                $ext = strtolower(pathinfo($_FILES['photos']['name'][$k], PATHINFO_EXTENSION));
                if(!in_array($ext, $formats_autorises)) continue;
                if($_FILES['photos']['size'][$k] > 5 * 1024 * 1024) continue;

                $nom_fichier = 'produit_' . time() . '_' . rand(100,999) . '_' . $k . '.' . $ext;
                $chemin = $upload_dir . $nom_fichier;
                if(move_uploaded_file($tmp, $chemin)) {
                    $nouvelles_images[] = 'assets/images/products/' . $nom_fichier;
                    if(empty($image)) $image = 'assets/images/products/' . $nom_fichier;
                }
            }
        }

        if($id > 0) {
            // MODIFICATION
            $stmt = $pdo->prepare("UPDATE products SET name=?,slug=?,category_id=?,price=?,stock=?,description=?,image=?,sku=?,featured=?,active=? WHERE id=?");
            if($stmt->execute([$name,$slug,$category_id,$price,$stock,$description,$image,$sku,$featured,$active,$id])) {
                foreach($nouvelles_images as $i => $img_path) {
                    $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM product_images WHERE product_id=?");
                    $stmt2->execute([$id]);
                    $count = $stmt2->fetchColumn();
                    $is_main = ($count == 0) ? 1 : 0;
                    $pdo->prepare("INSERT INTO product_images (product_id, image, is_main, ordre) VALUES (?,?,?,?)")
                        ->execute([$id, $img_path, $is_main, $count + $i]);
                }
                $success = "Produit mis a jour !";
            } else {
                $error = "Erreur lors de la mise a jour.";
            }
        } else {
            // AJOUT
            $stmt = $pdo->prepare("INSERT INTO products (name,slug,category_id,price,stock,description,image,sku,featured,active) VALUES (?,?,?,?,?,?,?,?,?,?)");
            if($stmt->execute([$name,$slug,$category_id,$price,$stock,$description,$image,$sku,$featured,$active])) {
                $new_id = $pdo->lastInsertId();
                foreach($nouvelles_images as $i => $img_path) {
                    $is_main = ($i == 0) ? 1 : 0;
                    $pdo->prepare("INSERT INTO product_images (product_id, image, is_main, ordre) VALUES (?,?,?,?)")
                        ->execute([$new_id, $img_path, $is_main, $i]);
                }
                $success = "Produit ajoute avec succes !";
            } else {
                $error = "Erreur lors de l'ajout.";
            }
        }
    }
}

// PRODUIT A MODIFIER
$edit_product = null;
$edit_images  = [];
if(isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit_product = $stmt->fetch();
    if($edit_product) {
        $stmt2 = $pdo->prepare("SELECT * FROM product_images WHERE product_id=? ORDER BY is_main DESC, ordre ASC");
        $stmt2->execute([$edit_product['id']]);
        $edit_images = $stmt2->fetchAll();
    }
}

// FILTRES
$filtre_cat   = isset($_GET['cat'])    ? (int)$_GET['cat']    : 0;
$filtre_stock = isset($_GET['stock'])  ? $_GET['stock']        : '';
$search       = isset($_GET['search']) ? trim($_GET['search']) : '';

$where = ["p.active = 1"]; $params = [];
if($filtre_cat)   { $where[] = "p.category_id = ?"; $params[] = $filtre_cat; }
if($filtre_stock === 'rupture') { $where[] = "p.stock = 0"; }
elseif($filtre_stock === 'faible') { $where[] = "p.stock > 0 AND p.stock <= 5"; }
if($search) { $where[] = "p.name LIKE ?"; $params[] = "%$search%"; }
$where_sql = 'WHERE '.implode(' AND ', $where);

$stmt = $pdo->prepare("SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id $where_sql ORDER BY p.id DESC");
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

include 'header_admin.php';
?>
<style>
.admin-page{background:#FDF8F2;min-height:80vh;padding:30px 0}
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
.pbtn-warning{background:#FFF8E1;color:#F57F17;border:none;border-radius:8px;padding:6px 12px;font-size:0.78rem;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block;transition:all 0.2s}
.pbtn-warning:hover{background:#F57F17;color:#fff}
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
.upload-zone{border:2px dashed #C9A84C;border-radius:14px;padding:25px;background:#FFFDF5;text-align:center;cursor:pointer;transition:all 0.3s;position:relative}
.upload-zone:hover{background:#FFF8E0;border-color:#b8942e}
.upload-zone input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%}
.upload-zone-icon{font-size:2rem;display:block;margin-bottom:8px}
.upload-zone-text{color:#6B3A2A;font-size:0.88rem;font-weight:600}
.upload-zone-hint{color:#9a7c5c;font-size:0.75rem;margin-top:4px}
.photos-preview-grid{display:flex;flex-wrap:wrap;gap:12px;margin-top:15px}
.photo-preview-item{position:relative;width:100px;height:100px;border-radius:12px;overflow:hidden;border:2px solid #F5E6D3;background:#FDFAF7}
.photo-preview-item img{width:100%;height:100%;object-fit:cover}
.photo-preview-item .badge-main{position:absolute;top:4px;left:4px;background:#C9A84C;color:#3E1F0D;font-size:0.6rem;font-weight:800;padding:2px 6px;border-radius:6px}
.photo-preview-item .btn-delete-img{position:absolute;top:4px;right:4px;background:#c62828;color:#fff;border:none;border-radius:50%;width:22px;height:22px;font-size:0.7rem;cursor:pointer;display:flex;align-items:center;justify-content:center;text-decoration:none;font-weight:700}
.photo-preview-item .btn-set-main{position:absolute;bottom:4px;left:50%;transform:translateX(-50%);background:rgba(62,31,13,0.8);color:#C9A84C;border:none;border-radius:6px;padding:2px 6px;font-size:0.6rem;font-weight:700;cursor:pointer;white-space:nowrap;text-decoration:none}
.photo-preview-item.is-main{border-color:#C9A84C;border-width:3px}
.new-preview-grid{display:flex;flex-wrap:wrap;gap:10px;margin-top:12px}
.new-preview-item{position:relative;width:90px;height:90px;border-radius:10px;overflow:hidden;border:2px dashed #C9A84C}
.new-preview-item img{width:100%;height:100%;object-fit:cover}
.new-preview-item .btn-remove-new{position:absolute;top:3px;right:3px;background:#c62828;color:#fff;border:none;border-radius:50%;width:20px;height:20px;font-size:0.65rem;cursor:pointer;display:flex;align-items:center;justify-content:center;font-weight:700}
</style>

<div class="admin-page"><div class="container">

<?php if($success): ?><div class="alert-hr success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if($error):   ?><div class="alert-hr error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<!-- FORMULAIRE AJOUT / MODIFICATION -->
<div class="dash-card">
    <div class="dash-card-header">
        <h5><?= $edit_product ? 'Modifier le produit' : 'Ajouter un produit' ?></h5>
        <?php if($edit_product): ?>
            <a href="products.php" class="pbtn pbtn-sm">+ Nouveau produit</a>
        <?php endif; ?>
    </div>
    <div class="dash-card-body">
        <form method="POST" action="" enctype="multipart/form-data">
            <?php if($edit_product): ?>
                <input type="hidden" name="product_id" value="<?= $edit_product['id'] ?>">
            <?php endif; ?>
            <input type="hidden" name="image" value="<?= htmlspecialchars($edit_product['image']??'') ?>">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="plabel">Nom du produit *</label>
                    <input type="text" class="pinput" name="name"
                           value="<?= htmlspecialchars($edit_product['name']??'') ?>"
                           placeholder="Ex: Meche Bouclees Premium" required>
                </div>
                <div class="col-md-3">
                    <label class="plabel">Categorie *</label>
                    <select class="pinput" name="category_id" required>
                        <option value="">Choisir...</option>
                        <?php foreach($categories as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($edit_product['category_id']??'')==$c['id']?'selected':'' ?>>
                            <?= htmlspecialchars($c['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="plabel">SKU / Reference</label>
                    <input type="text" class="pinput" name="sku"
                           value="<?= htmlspecialchars($edit_product['sku']??'') ?>"
                           placeholder="Ex: BOUCL-001">
                </div>
                <div class="col-md-3">
                    <label class="plabel">Prix (€) *</label>
                    <input type="number" class="pinput" name="price" step="0.01" min="0"
                           value="<?= $edit_product['price']??'' ?>" placeholder="0.00" required>
                </div>
                <div class="col-md-3">
                    <label class="plabel">Stock</label>
                    <input type="number" class="pinput" name="stock" min="0"
                           value="<?= $edit_product['stock']??0 ?>">
                </div>

                <?php if($edit_product && count($edit_images) > 0): ?>
                <div class="col-12">
                    <label class="plabel">Photos actuelles</label>
                    <div class="photos-preview-grid">
                        <?php foreach($edit_images as $img): ?>
                        <div class="photo-preview-item <?= $img['is_main'] ? 'is-main' : '' ?>">
                            <img src="/ecommerce/<?= htmlspecialchars($img['image']) ?>" alt="">
                            <?php if($img['is_main']): ?>
                                <span class="badge-main">Principale</span>
                            <?php endif; ?>
                            <a href="?delete_image=<?= $img['id'] ?>&pid=<?= $edit_product['id'] ?>"
                               class="btn-delete-img"
                               onclick="return confirm('Supprimer cette photo ?')">x</a>
                            <?php if(!$img['is_main']): ?>
                                <a href="?set_main=<?= $img['id'] ?>&pid=<?= $edit_product['id'] ?>"
                                   class="btn-set-main">Principale</a>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="col-12">
                    <label class="plabel">
                        <?= ($edit_product && count($edit_images) > 0) ? 'Ajouter des photos supplementaires' : 'Photos du produit' ?>
                    </label>
                    <div class="upload-zone" id="uploadZone">
                        <input type="file" name="photos[]" id="photosInput"
                               accept="image/jpeg,image/png,image/webp"
                               multiple onchange="previewPhotos(this)">
                        <span class="upload-zone-icon"><i class="bi bi-cloud-arrow-up"></i></span>
                        <div class="upload-zone-text">Cliquer pour uploader une ou plusieurs photos</div>
                        <div class="upload-zone-hint">JPG, PNG ou WEBP — Max 5 Mo par photo</div>
                    </div>
                    <div class="new-preview-grid" id="newPreviewGrid"></div>
                </div>

                <div class="col-12">
                    <label class="plabel">Description</label>
                    <textarea class="pinput" name="description" rows="3"
                              placeholder="Description du produit..."><?= htmlspecialchars($edit_product['description']??'') ?></textarea>
                </div>

                <div class="col-md-4 d-flex align-items-center gap-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="featured" id="featured"
                               <?= ($edit_product['featured']??0)?'checked':'' ?>
                               style="border-color:#C9A84C;accent-color:#C9A84C">
                        <label class="form-check-label" for="featured"
                               style="color:#3E1F0D;font-weight:600;font-size:0.85rem">Produit vedette</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="active" id="active"
                               <?= ($edit_product['active']??1)?'checked':'' ?>
                               style="border-color:#C9A84C;accent-color:#C9A84C">
                        <label class="form-check-label" for="active"
                               style="color:#3E1F0D;font-weight:600;font-size:0.85rem">Actif</label>
                    </div>
                </div>
                <div class="col-md-8 text-end">
                    <button type="submit" class="pbtn">
                        <?= $edit_product ? 'Mettre a jour' : 'Ajouter le produit' ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- FILTRES -->
<div class="filter-bar">
    <form method="GET" action="" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;width:100%">
        <input type="text" class="pinput" name="search" value="<?= htmlspecialchars($search) ?>"
               placeholder="Rechercher un produit..." style="max-width:220px">
        <select class="pinput" name="cat" style="max-width:180px">
            <option value="">Toutes categories</option>
            <?php foreach($categories as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $filtre_cat==$c['id']?'selected':'' ?>>
                <?= htmlspecialchars($c['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <select class="pinput" name="stock" style="max-width:160px">
            <option value="">Tous les stocks</option>
            <option value="rupture" <?= $filtre_stock==='rupture'?'selected':'' ?>>Rupture</option>
            <option value="faible"  <?= $filtre_stock==='faible'?'selected':'' ?>>Stock faible</option>
        </select>
        <button type="submit" class="pbtn pbtn-sm">Filtrer</button>
        <a href="products.php" class="pbtn-info" style="padding:8px 14px">Reset</a>
    </form>
</div>

<!-- LISTE PRODUITS -->
<div class="dash-card">
    <div class="dash-card-header">
        <h5>Liste des produits (<?= count($products) ?>)</h5>
    </div>
    <div style="overflow-x:auto">
        <?php if(count($products) > 0): ?>
        <table class="ptable">
            <thead><tr>
                <th>Photos</th>
                <th>Nom</th>
                <th>Categorie</th>
                <th>Prix</th>
                <th>Stock</th>
                <th>Vedette</th>
                <th>Actions</th>
            </tr></thead>
            <tbody>
            <?php foreach($products as $p):
                $stmt_imgs = $pdo->prepare("SELECT * FROM product_images WHERE product_id=? ORDER BY is_main DESC, ordre ASC LIMIT 4");
                $stmt_imgs->execute([$p['id']]);
                $p_images = $stmt_imgs->fetchAll();
            ?>
            <tr>
                <td>
                    <div style="display:flex;gap:4px;align-items:center">
                    <?php if(count($p_images) > 0): ?>
                        <?php foreach($p_images as $pi): ?>
                            <img src="/ecommerce/<?= htmlspecialchars($pi['image']) ?>"
                                 class="prod-img"
                                 style="<?= $pi['is_main'] ? 'border:2px solid #C9A84C' : '' ?>">
                        <?php endforeach; ?>
                        <?php
                        $total = $pdo->prepare("SELECT COUNT(*) FROM product_images WHERE product_id=?");
                        $total->execute([$p['id']]);
                        $nb_total = $total->fetchColumn();
                        if($nb_total > 4): ?>
                            <span style="background:#F5E6D3;color:#6B3A2A;border-radius:8px;padding:3px 8px;font-size:0.72rem;font-weight:700">
                                +<?= $nb_total - 4 ?>
                            </span>
                        <?php endif; ?>
                    <?php elseif(!empty($p['image'])): ?>
                        <img src="/ecommerce/<?= htmlspecialchars($p['image']) ?>" class="prod-img">
                    <?php else: ?>
                        <div class="prod-img-ph"></div>
                    <?php endif; ?>
                    </div>
                </td>
                <td>
                    <div style="font-weight:700"><?= htmlspecialchars($p['name']) ?></div>
                    <?php if(!empty($p['sku'])): ?>
                        <div style="color:#9a7c5c;font-size:0.72rem"><?= htmlspecialchars($p['sku']) ?></div>
                    <?php endif; ?>
                </td>
                <td>
                    <span style="background:#F5E6D3;color:#6B3A2A;padding:3px 10px;border-radius:8px;font-size:0.75rem;font-weight:600">
                        <?= htmlspecialchars($p['cat_name']??'-') ?>
                    </span>
                </td>
                <td style="font-weight:800;color:#C1622F"><?= number_format($p['price'],2) ?>€</td>
                <td>
                    <?php if($p['stock'] > 10): ?>
                        <span class="stock-ok"><?= $p['stock'] ?></span>
                    <?php elseif($p['stock'] > 0): ?>
                        <span class="stock-low"><?= $p['stock'] ?></span>
                    <?php else: ?>
                        <span class="stock-out">0</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="?toggle_featured=<?= $p['id'] ?>" style="font-size:1.3rem;text-decoration:none">
                        <?= $p['featured'] ? '★' : '☆' ?>
                    </a>
                </td>
                <td>
                    <div style="display:flex;gap:5px;flex-wrap:wrap">
                        <a href="?edit=<?= $p['id'] ?>" class="pbtn-info">Modifier</a>
                        <a href="/ecommerce/products/detail.php?id=<?= $p['id'] ?>" class="pbtn-success" target="_blank">Voir</a>
                        <a href="?delete=<?= $p['id'] ?>" class="pbtn-danger"
                           onclick="return confirm('Desactiver ce produit ?')">Suppr.</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div style="text-align:center;padding:40px;color:#9a7c5c">
            <h5 style="color:#3E1F0D">Aucun produit trouve</h5>
            <p>Ajoutez votre premier produit avec le formulaire ci-dessus</p>
        </div>
        <?php endif; ?>
    </div>
</div>

</div></div>

<script>
let selectedFiles = [];

function previewPhotos(input) {
    const grid = document.getElementById('newPreviewGrid');
    const zone = document.getElementById('uploadZone');

    Array.from(input.files).forEach(file => {
        if(file.size > 5 * 1024 * 1024) {
            alert('"' + file.name + '" est trop grand (max 5 Mo).');
            return;
        }
        selectedFiles.push(file);
    });

    grid.innerHTML = '';
    selectedFiles.forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const item = document.createElement('div');
            item.className = 'new-preview-item';
            item.innerHTML = `<img src="${e.target.result}" alt=""><button type="button" class="btn-remove-new" onclick="removePhoto(${index})">x</button>`;
            grid.appendChild(item);
        };
        reader.readAsDataURL(file);
    });

    if(selectedFiles.length > 0) {
        zone.querySelector('.upload-zone-text').textContent = selectedFiles.length + ' photo(s) selectionnee(s)';
    }
    rebuildFileInput();
}

function removePhoto(index) {
    selectedFiles.splice(index, 1);
    const grid = document.getElementById('newPreviewGrid');
    grid.innerHTML = '';
    selectedFiles.forEach((file, i) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const item = document.createElement('div');
            item.className = 'new-preview-item';
            item.innerHTML = `<img src="${e.target.result}" alt=""><button type="button" class="btn-remove-new" onclick="removePhoto(${i})">x</button>`;
            grid.appendChild(item);
        };
        reader.readAsDataURL(file);
    });
    rebuildFileInput();
}

function rebuildFileInput() {
    const input = document.getElementById('photosInput');
    const dt = new DataTransfer();
    selectedFiles.forEach(file => dt.items.add(file));
    input.files = dt.files;
}
</script>

<?php include 'footer_admin.php'; ?>