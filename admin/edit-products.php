<?php
require_once '../config/database.php';
$page_title = 'Modifier un Produit - Admin';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /ecommerce/user/login.php');
    exit;
}

$error = '';
$success = '';
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Récupérer le produit
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if(!$product) {
    header('Location: products.php');
    exit;
}

// Récupérer les catégories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

// Traitement du formulaire
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name']);
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $description = trim($_POST['description']);
    $price       = (float)$_POST['price'];
    $stock       = (int)$_POST['stock'];
    $sku         = trim($_POST['sku']);
    $active      = isset($_POST['active']) ? 1 : 0;
    $featured    = isset($_POST['featured']) ? 1 : 0;

    // Garder l'ancienne image par défaut
    $image_path = $product['image'] ?? '';

    // Slug
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

    // Upload de la nouvelle image si fournie
    if(!empty($_FILES['image']['name'])) {
        $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if(!in_array($ext, $allowed)) {
            $error = "Format d'image non autorise. Utilisez JPG, PNG ou WEBP.";
        } elseif($_FILES['image']['size'] > 5 * 1024 * 1024) {
            $error = "L'image ne doit pas depasser 5 Mo.";
        } else {
            // Créer le dossier si il n'existe pas
            $upload_dir = '../assets/images/products/';
            if(!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $filename   = 'product_' . time() . '_' . rand(100, 999) . '.' . $ext;
            $dest       = $upload_dir . $filename;

            if(move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                // Supprimer l'ancienne image si elle existe
                if(!empty($product['image'])) {
                    $old = '../' . ltrim($product['image'], '/');
                    if(file_exists($old)) unlink($old);
                }
                $image_path = 'assets/images/products/' . $filename;
            } else {
                $error = "Erreur lors de l'upload de l'image.";
            }
        }
    }

    // Validation
    if(empty($error)) {
        if(empty($name) || empty($price) || empty($sku)) {
            $error = "Le nom, le prix et le SKU sont obligatoires.";
        } elseif($price <= 0) {
            $error = "Le prix doit etre superieur a 0.";
        } elseif($stock < 0) {
            $error = "Le stock ne peut pas etre negatif.";
        } else {
            // Vérifier SKU unique
            $stmt = $pdo->prepare("SELECT id FROM products WHERE sku = ? AND id != ?");
            $stmt->execute([$sku, $product_id]);

            if($stmt->fetch()) {
                $error = "Ce SKU existe deja pour un autre produit.";
            } else {
                try {
                    $stmt = $pdo->prepare("
                        UPDATE products 
                        SET category_id=?, name=?, slug=?, description=?, price=?, 
                            stock=?, image=?, sku=?, active=?, featured=?
                        WHERE id=?
                    ");
                    if($stmt->execute([$category_id, $name, $slug, $description, $price, $stock, $image_path, $sku, $active, $featured, $product_id])) {
                        $success = "Produit modifie avec succes !";
                        // Recharger le produit
                        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
                        $stmt->execute([$product_id]);
                        $product = $stmt->fetch();
                    } else {
                        $error = "Erreur lors de la modification du produit.";
                    }
                } catch(PDOException $e) {
                    $error = "Erreur de base de donnees : " . $e->getMessage();
                }
            }
        }
    }
}

include 'header_admin.php';
?>

<style>
:root {
    --gold: #C9A84C;
    --orange: #C1622F;
    --dark: #3E1F0D;
    --medium: #6B3A2A;
    --light: #F5E6D3;
    --cream: #FDF8F2;
}

.page-header {
    background: linear-gradient(135deg, var(--dark), var(--medium));
    border-radius: 20px;
    padding: 25px 30px;
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 15px;
}
.page-header h1 {
    font-family: 'Playfair Display', serif;
    color: #fff;
    font-size: 1.6rem;
    margin: 0;
}

.form-card {
    background: #fff;
    border-radius: 20px;
    border: 1px solid var(--light);
    box-shadow: 0 4px 20px rgba(62,31,13,0.06);
    overflow: hidden;
    margin-bottom: 25px;
}
.form-card-header {
    background: linear-gradient(135deg, #FDF3E7, #FBE8D0);
    padding: 16px 24px;
    border-bottom: 1px solid var(--light);
}
.form-card-header h5 {
    font-family: 'Playfair Display', serif;
    color: var(--dark);
    font-weight: 700;
    margin: 0;
}
.form-card-body { padding: 24px; }

.form-label-custom {
    font-weight: 700;
    font-size: 0.82rem;
    color: var(--medium);
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 6px;
    display: block;
}
.form-control-custom {
    width: 100%;
    padding: 11px 15px;
    border: 2px solid var(--light);
    border-radius: 12px;
    background: var(--cream);
    font-size: 0.9rem;
    color: var(--dark);
    outline: none;
    transition: border-color 0.2s;
    font-family: 'Poppins', sans-serif;
}
.form-control-custom:focus { border-color: var(--gold); background: #fff; }
select.form-control-custom { cursor: pointer; }
textarea.form-control-custom { resize: vertical; min-height: 120px; }

/* Upload zone */
.upload-zone {
    border: 2px dashed var(--light);
    border-radius: 14px;
    padding: 24px;
    text-align: center;
    background: var(--cream);
    cursor: pointer;
    transition: all 0.3s;
}
.upload-zone:hover { border-color: var(--gold); background: #FFFDF5; }
.upload-zone input[type="file"] { display: none; }
.upload-zone label { cursor: pointer; display: block; }
.upload-zone .upload-icon { font-size: 2.5rem; color: var(--gold); margin-bottom: 8px; }
.upload-zone p { color: var(--medium); font-size: 0.85rem; margin: 0; }

/* Preview */
.img-preview {
    width: 100%;
    max-height: 220px;
    object-fit: cover;
    border-radius: 12px;
    border: 2px solid var(--light);
    margin-top: 12px;
}

/* Toggle switch */
.toggle-wrap { display: flex; align-items: center; gap: 12px; margin-bottom: 15px; }
.toggle-input { display: none; }
.toggle-label {
    width: 50px; height: 26px;
    background: #ddd; border-radius: 13px;
    position: relative; cursor: pointer; transition: background 0.3s;
}
.toggle-label::after {
    content: ''; position: absolute;
    width: 20px; height: 20px; border-radius: 50%;
    background: #fff; top: 3px; left: 3px;
    transition: left 0.3s; box-shadow: 0 1px 4px rgba(0,0,0,0.2);
}
.toggle-input:checked + .toggle-label { background: var(--gold); }
.toggle-input:checked + .toggle-label::after { left: 27px; }

/* Buttons */
.btn-save {
    background: linear-gradient(135deg, var(--gold), #b8942e);
    color: var(--dark); border: none; border-radius: 12px;
    padding: 13px 30px; font-size: 0.9rem; font-weight: 700;
    display: inline-flex; align-items: center; gap: 8px;
    cursor: pointer; transition: all 0.3s;
}
.btn-save:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(201,168,76,0.35); }
.btn-back {
    background: var(--light); color: var(--dark);
    border: none; border-radius: 12px;
    padding: 13px 24px; font-size: 0.9rem; font-weight: 700;
    text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
    transition: all 0.3s;
}
.btn-back:hover { background: #EDD9C0; color: var(--dark); }
.btn-delete {
    background: #FCE4E4; color: #C62828;
    border: none; border-radius: 12px;
    padding: 13px 24px; font-size: 0.9rem; font-weight: 700;
    text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
    transition: all 0.3s; cursor: pointer;
}
.btn-delete:hover { background: #C62828; color: #fff; }

.alert-custom {
    border-radius: 14px; padding: 14px 20px;
    font-weight: 600; font-size: 0.9rem;
    display: flex; align-items: center; gap: 10px; margin-bottom: 22px;
}
.alert-success { background: #E8F5E9; color: #2E7D32; border: 1px solid #C8E6C9; }
.alert-error   { background: #FCE4E4; color: #C62828; border: 1px solid #FFCDD2; }
</style>

<div class="container-fluid py-4 px-4">

    <!-- HEADER -->
    <div class="page-header">
        <div>
            <h1>Modifier le produit</h1>
            <p style="color:rgba(255,255,255,0.6);margin:4px 0 0;font-size:0.85rem"><?= htmlspecialchars($product['name']) ?></p>
        </div>
        <a href="products.php" class="btn-back">
            <i class="bi bi-arrow-left"></i> Retour aux produits
        </a>
    </div>

    <?php if($error): ?>
    <div class="alert-custom alert-error">
        <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <?php if($success): ?>
    <div class="alert-custom alert-success">
        <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="row g-4">

            <!-- COLONNE GAUCHE -->
            <div class="col-lg-8">

                <!-- Informations générales -->
                <div class="form-card">
                    <div class="form-card-header">
                        <h5><i class="bi bi-info-circle me-2" style="color:var(--gold)"></i>Informations generales</h5>
                    </div>
                    <div class="form-card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label-custom">Nom du produit *</label>
                                <input type="text" name="name" class="form-control-custom" required
                                    value="<?= htmlspecialchars($product['name']) ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label-custom">Description</label>
                                <textarea name="description" class="form-control-custom"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-custom">Prix (€) *</label>
                                <input type="number" name="price" class="form-control-custom"
                                    step="0.01" min="0" required value="<?= $product['price'] ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-custom">Stock *</label>
                                <input type="number" name="stock" class="form-control-custom"
                                    min="0" required value="<?= $product['stock'] ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-custom">SKU (Reference) *</label>
                                <input type="text" name="sku" class="form-control-custom" required
                                    value="<?= htmlspecialchars($product['sku']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-custom">Categorie</label>
                                <select name="category_id" class="form-control-custom">
                                    <option value="">-- Selectionner --</option>
                                    <?php foreach($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"
                                        <?= ($product['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Image -->
                <div class="form-card">
                    <div class="form-card-header">
                        <h5><i class="bi bi-image me-2" style="color:var(--gold)"></i>Image du produit</h5>
                    </div>
                    <div class="form-card-body">
                        <div class="upload-zone" onclick="document.getElementById('imageInput').click()">
                            <label>
                                <div class="upload-icon"><i class="bi bi-cloud-arrow-up"></i></div>
                                <p><strong>Cliquez pour choisir une image</strong></p>
                                <p style="margin-top:4px">JPG, PNG, WEBP — Max 5 Mo</p>
                                <p style="margin-top:4px;color:var(--gold);font-size:0.8rem">
                                    L'image sera sauvegardee dans assets/images/products/
                                </p>
                            </label>
                            <input type="file" id="imageInput" name="image" accept="image/*"
                                onchange="previewImage(this)">
                        </div>

                        <!-- Apercu -->
                        <?php if(!empty($product['image'])): ?>
                        <div style="margin-top:15px">
                            <p style="font-size:0.8rem;color:var(--medium);font-weight:600;margin-bottom:6px">Image actuelle :</p>
                            <img id="imgPreview"
                                src="/ecommerce/<?= htmlspecialchars($product['image']) ?>"
                                alt="Apercu" class="img-preview">
                            <p style="font-size:0.75rem;color:#9a7c5c;margin-top:6px">
                                Chemin : <?= htmlspecialchars($product['image']) ?>
                            </p>
                        </div>
                        <?php else: ?>
                        <img id="imgPreview" src="" alt="" class="img-preview" style="display:none">
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- COLONNE DROITE -->
            <div class="col-lg-4">
                <div class="form-card">
                    <div class="form-card-header">
                        <h5><i class="bi bi-toggles me-2" style="color:var(--gold)"></i>Options</h5>
                    </div>
                    <div class="form-card-body">

                        <div class="toggle-wrap">
                            <input type="checkbox" name="active" id="active" class="toggle-input"
                                <?= $product['active'] ? 'checked' : '' ?>>
                            <label for="active" class="toggle-label"></label>
                            <span style="font-size:0.88rem;color:var(--medium);font-weight:600">Produit actif</span>
                        </div>
                        <p style="color:#9a7c5c;font-size:0.78rem;margin-bottom:20px">Visible sur le site</p>

                        <div class="toggle-wrap">
                            <input type="checkbox" name="featured" id="featured" class="toggle-input"
                                <?= $product['featured'] ? 'checked' : '' ?>>
                            <label for="featured" class="toggle-label"></label>
                            <span style="font-size:0.88rem;color:var(--medium);font-weight:600">Produit en vedette</span>
                        </div>
                        <p style="color:#9a7c5c;font-size:0.78rem;margin-bottom:20px">Affiche sur la page d'accueil</p>

                        <hr style="border-color:var(--light)">

                        <p style="color:#9a7c5c;font-size:0.8rem;margin-bottom:20px">
                            <strong>Cree le :</strong><br>
                            <?= date('d/m/Y à H:i', strtotime($product['created_at'])) ?>
                        </p>

                        <div style="display:flex;flex-direction:column;gap:10px">
                            <button type="submit" class="btn-save">
                                <i class="bi bi-save"></i> Enregistrer
                            </button>
                            <a href="products.php" class="btn-back">
                                <i class="bi bi-x"></i> Annuler
                            </a>
                            <a href="delete-products.php?id=<?= $product['id'] ?>"
                               class="btn-delete"
                               onclick="return confirm('Supprimer ce produit ?')">
                                <i class="bi bi-trash"></i> Supprimer
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>

</div>

<script>
function previewImage(input) {
    const preview = document.getElementById('imgPreview');
    if(input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include 'footer_admin.php'; ?>