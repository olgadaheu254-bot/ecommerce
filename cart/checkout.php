<?php
require_once '../config/database.php';
$page_title = 'Finaliser ma commande - MonShop';

// Vérifier si l'utilisateur est connecté
if(!isset($_SESSION['user_id'])) {
    header('Location: /ecommerce/user/login.php?redirect=/ecommerce/cart/checkout.php');
    exit;
}

// Vérifier si le panier n'est pas vide
if(!isset($_SESSION['cart']) || count($_SESSION['cart']) == 0) {
    header('Location: /ecommerce/cart/index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Récupérer les infos utilisateur
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Calculer le total du panier
$cart_items = array();
$total = 0;

$ids = array_keys($_SESSION['cart']);
$placeholders = str_repeat('?,', count($ids) - 1) . '?';

$stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
$stmt->execute($ids);
$products = $stmt->fetchAll();

foreach($products as $product) {
    $quantity = $_SESSION['cart'][$product['id']];
    $subtotal = $product['price'] * $quantity;
    $total += $subtotal;
    
    $cart_items[] = array(
        'product' => $product,
        'quantity' => $quantity,
        'subtotal' => $subtotal
    );
}

// Traitement du formulaire de commande
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipping_address = trim($_POST['shipping_address']);
    $city = trim($_POST['city']);
    $postal_code = trim($_POST['postal_code']);
    $phone = trim($_POST['phone']);
    $payment_method = $_POST['payment_method'];
    $notes = trim($_POST['notes']);
    
    // Validation
    if(empty($shipping_address) || empty($city) || empty($postal_code) || empty($phone)) {
        $error = "Veuillez remplir tous les champs obligatoires.";
    } else {
        try {
            // Début de la transaction
            $pdo->beginTransaction();
            
            // Générer un numéro de commande unique
            $order_number = 'CMD-' . date('Ymd') . '-' . rand(1000, 9999);
            
            // Préparer l'adresse complète
            $full_address = $shipping_address . "\n" . $postal_code . " " . $city . "\nTél: " . $phone;
            
            // Créer la commande
            $stmt = $pdo->prepare("
                INSERT INTO orders (user_id, order_number, total_amount, status, payment_method, payment_status, shipping_address, notes) 
                VALUES (?, ?, ?, 'pending', ?, 'pending', ?, ?)
            ");
            $stmt->execute([$user_id, $order_number, $total, $payment_method, $full_address, $notes]);
            
            $order_id = $pdo->lastInsertId();
            
            // Ajouter les articles de la commande
            $stmt = $pdo->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price, subtotal) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            foreach($cart_items as $item) {
                $product = $item['product'];
                $quantity = $item['quantity'];
                $price = $product['price'];
                $subtotal = $item['subtotal'];
                
                $stmt->execute([$order_id, $product['id'], $quantity, $price, $subtotal]);
                
                // Mettre à jour le stock
                $update_stock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $update_stock->execute([$quantity, $product['id']]);
            }
            
            // Valider la transaction
            $pdo->commit();
            
            // Vider le panier
            $_SESSION['cart'] = array();
            
            $success = "Votre commande a été passée avec succès ! Numéro de commande : " . $order_number;
            
            // Redirection après 3 secondes
            header("refresh:3;url=/ecommerce/user/profile.php");
            
        } catch(Exception $e) {
            // Annuler la transaction en cas d'erreur
            $pdo->rollBack();
            $error = "Erreur lors de la création de la commande. Veuillez réessayer.";
        }
    }
}

include '../includes/header.php';
?>

<div class="container my-5">
    <h1 class="mb-4"><i class="bi bi-credit-card"></i> Finaliser ma commande</h1>
    
    <?php if($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($success); ?>
            <br><small>Redirection vers votre profil...</small>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Formulaire de livraison -->
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-truck"></i> Informations de livraison</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">Prénom</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['first_name']); ?>" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Nom</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['last_name']); ?>" readonly>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Téléphone *</label>
                            <input type="tel" class="form-control" name="phone" 
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="shipping_address" class="form-label">Adresse de livraison *</label>
                            <textarea class="form-control" name="shipping_address" rows="3" required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="city" class="form-label">Ville *</label>
                                <input type="text" class="form-control" name="city" 
                                       value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="postal_code" class="form-label">Code postal *</label>
                                <input type="text" class="form-control" name="postal_code" 
                                       value="<?php echo htmlspecialchars($user['postal_code'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <h5 class="mb-3"><i class="bi bi-credit-card-2-front"></i> Mode de paiement</h5>
                        
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" value="Carte bancaire" checked>
                            <label class="form-check-label">
                                <i class="bi bi-credit-card"></i> Carte bancaire
                            </label>
                        </div>
                        
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" value="PayPal">
                            <label class="form-check-label">
                                <i class="bi bi-paypal"></i> PayPal
                            </label>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="payment_method" value="Paiement à la livraison">
                            <label class="form-check-label">
                                <i class="bi bi-cash"></i> Paiement à la livraison
                            </label>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes / Instructions (optionnel)</label>
                            <textarea class="form-control" name="notes" rows="2"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-success btn-lg w-100">
                            <i class="bi bi-check-circle"></i> Valider ma commande
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Résumé de la commande -->
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-receipt"></i> Résumé</h5>
                </div>
                <div class="card-body">
                    <?php foreach($cart_items as $item): ?>
                        <?php $product = $item['product']; ?>
                        <div class="d-flex mb-3 pb-3 border-bottom">
                            <img src="<?php echo !empty($product['image']) ? $product['image'] : 'https://via.placeholder.com/60'; ?>" 
                                 class="img-fluid rounded" 
                                 style="width: 60px; height: 60px; object-fit: cover;">
                            <div class="ms-3 flex-grow-1">
                                <h6 class="mb-1"><?php echo htmlspecialchars($product['name']); ?></h6>
                                <small class="text-muted">Qté: <?php echo $item['quantity']; ?></small>
                                <div class="fw-bold text-end"><?php echo number_format($item['subtotal'], 2); ?> €</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Sous-total :</span>
                        <strong><?php echo number_format($total, 2); ?> €</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Livraison :</span>
                        <strong class="text-success">Gratuite</strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <h5>Total :</h5>
                        <h5 class="text-success"><?php echo number_format($total, 2); ?> €</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>