<?php
require_once '../config/database.php';
$page_title = 'Ajouter un Produit - Admin';

// Vérifier si l'utilisateur est admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /ecommerce/user/login.php');
    exit;
}

$error = '';
$success = '';

// Récupérer les catégories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

// Traitement du formulaire
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $sku = trim($_POST['sku']);
    $active = isset($_POST['active']) ? 1 : 0;
    $featured = isset($_POST['featured']) ? 1 : 0;
    $image_url = trim($_POST['image_url']);
    
    // Générer un slug
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    
    // Validation
    if(empty($name) || empty($price) || empty($sku)) {
        $error = "Le nom, le prix et le SKU sont obligatoires.";
    } elseif($price <= 0) {
        $error = "Le prix doit être supérieur à 0.";
    } elseif($stock < 0) {
        $error = "Le stock ne peut pas être négatif.";
    } else {
        // Vérifier si le SKU existe déjà
        $stmt = $pdo->prepare("SELECT id FROM products WHERE sku = ?");
        $stmt->execute([$sku]);
        
        if($stmt->fetch()) {
            $error = "Ce SKU existe déjà.";
        } else {
            try {
                // Insérer le produit
                $stmt = $pdo->prepare("
                    INSERT INTO products (category_id, name, slug, description, price, stock, image, sku, active, featured) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                if($stmt->execute([$category_id, $name, $slug, $description, $price, $stock, $image_url, $sku, $active, $featured])) {
                    $success = "Produit ajouté avec succès !";
                    header("refresh:2;url=products.php");
                } else {
                    $error = "Erreur lors de l'ajout du produit.";
                }
            } catch(PDOException $e) {
                $error = "Erreur de base de données : " . $e->getMessage();
            }
        }
    }
}

include 'header_admin.php';
?>

<div class="container-fluid my-4">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-2 d-md-block bg-light sidebar">
            <div class="position-sticky">
                <h5 class="p-3 mb-0 bg-primary text-white">
                    <i class="bi bi-speedometer2"></i> Admin Panel
                </h5>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="bi bi-house-door"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="products.php">
                            <i class="bi bi-box-seam"></i> Produits
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">
                            <i class="bi bi-cart-check"></i> Commandes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">
                            <i class="bi bi-people"></i> Utilisateurs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/ecommerce/index.php">
                            <i class="bi bi-arrow-left"></i> Retour au site
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Contenu principal -->
        <main class="col-md-10 ms-sm-auto px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-plus-circle"></i> Ajouter un Produit</h1>
                <div>
                    <a href="products.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                </div>
            </div>

            <?php if($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nom du produit *</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="5"></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="price" class="form-label">Prix (€) *</label>
                                        <input type="number" class="form-control" id="price" name="price" 
                                               step="0.01" min="0" required>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="stock" class="form-label">Stock *</label>
                                        <input type="number" class="form-control" id="stock" name="stock" 
                                               min="0" value="0" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="sku" class="form-label">SKU (Référence) *</label>
                                        <input type="text" class="form-control" id="sku" name="sku" required>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="category_id" class="form-label">Catégorie</label>
                                        <select class="form-select" id="category_id" name="category_id">
                                            <option value="">-- Sélectionner --</option>
                                            <?php foreach($categories as $cat): ?>
                                                <option value="<?php echo $cat['id']; ?>">
                                                    <?php echo htmlspecialchars($cat['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="image_url" class="form-label">URL de l'image</label>
                                    <input type="url" class="form-control" id="image_url" name="image_url" 
                                           placeholder="https://exemple.com/image.jpg">
                                    <small class="text-muted">Entrez l'URL complète d'une image en ligne</small>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title">Options</h5>

                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="active" 
                                                   name="active" checked>
                                            <label class="form-check-label" for="active">
                                                Produit actif (visible sur le site)
                                            </label>
                                        </div>

                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="featured" 
                                                   name="featured">
                                            <label class="form-check-label" for="featured">
                                                Produit en vedette (page d'accueil)
                                            </label>
                                        </div>

                                        <hr>

                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary btn-lg">
                                                <i class="bi bi-save"></i> Ajouter le produit
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>


<?php include 'footer_admin.php'; ?>