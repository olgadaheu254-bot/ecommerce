<?php
require_once '../config/database.php';
$page_title = 'Mon Panier - MonShop';

// Initialiser le panier
if(!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

// Récupérer les produits du panier
$cart_items = array();
$total = 0;

if(count($_SESSION['cart']) > 0) {
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
}

include '../includes/header.php';
?>

<div class="container my-5">
    <h1 class="mb-4"><i class="bi bi-cart-fill"></i> Mon Panier</h1>
    
    <?php if(count($cart_items) > 0): ?>
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <?php foreach($cart_items as $item): ?>
                            <?php $product = $item['product']; ?>
                            <div class="row align-items-center mb-3 pb-3 border-bottom">
                                <div class="col-md-2">
                                    <img src="<?php echo !empty($product['image']) ? $product['image'] : 'https://via.placeholder.com/150'; ?>" 
                                         class="img-fluid rounded" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                                </div>
                                <div class="col-md-4">
                                    <h5><?php echo htmlspecialchars($product['name']); ?></h5>
                                    <p class="text-muted small mb-0"><?php echo number_format($product['price'], 2); ?> €</p>
                                </div>
                                <div class="col-md-3">
                                    <div class="input-group">
                                        <button class="btn btn-outline-secondary" type="button" 
                                                onclick="updateQuantity(<?php echo $product['id']; ?>, -1)">
                                            <i class="bi bi-dash"></i>
                                        </button>
                                        <input type="text" class="form-control text-center" 
                                               value="<?php echo $item['quantity']; ?>" 
                                               id="qty-<?php echo $product['id']; ?>" 
                                               readonly>
                                        <button class="btn btn-outline-secondary" type="button" 
                                                onclick="updateQuantity(<?php echo $product['id']; ?>, 1)">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-2 text-end">
                                    <strong><?php echo number_format($item['subtotal'], 2); ?> €</strong>
                                </div>
                                <div class="col-md-1 text-end">
                                    <button class="btn btn-danger btn-sm" 
                                            onclick="removeFromCart(<?php echo $product['id']; ?>)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Récapitulatif</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Sous-total :</span>
                            <strong><?php echo number_format($total, 2); ?> €</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Livraison :</span>
                            <strong>Gratuite</strong>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <h5>Total :</h5>
                            <h5 class="text-success"><?php echo number_format($total, 2); ?> €</h5>
                        </div>
                        
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <a href="checkout.php" class="btn btn-success w-100 btn-lg mb-2">
                                <i class="bi bi-credit-card"></i> Passer la commande
                            </a>
                        <?php else: ?>
                            <a href="/ecommerce/user/login.php?redirect=/ecommerce/cart/checkout.php" 
                               class="btn btn-primary w-100 btn-lg mb-2">
                                <i class="bi bi-box-arrow-in-right"></i> Se connecter pour commander
                            </a>
                        <?php endif; ?>
                        
                        <a href="/ecommerce/index.php" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-arrow-left"></i> Continuer mes achats
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-cart-x display-1 text-muted"></i>
            <h3 class="mt-4">Votre panier est vide</h3>
            <p class="text-muted">Découvrez nos produits et ajoutez-les à votre panier</p>
            <a href="/ecommerce/index.php" class="btn btn-primary btn-lg mt-3">
                <i class="bi bi-bag-check"></i> Voir nos produits
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
// Mettre à jour la quantité
function updateQuantity(productId, change) {
    const qtyInput = document.getElementById('qty-' + productId);
    let currentQty = parseInt(qtyInput.value);
    let newQty = currentQty + change;
    
    if(newQty < 1) {
        if(confirm('Voulez-vous retirer ce produit du panier ?')) {
            removeFromCart(productId);
        }
        return;
    }
    
    // Envoyer la requête AJAX
    fetch('update.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=${newQty}`
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            location.reload();
        } else {
            alert(data.message);
        }
    });
}

// Retirer du panier
function removeFromCart(productId) {
    if(!confirm('Voulez-vous vraiment retirer ce produit ?')) {
        return;
    }
    
    fetch('remove.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            location.reload();
        } else {
            alert(data.message);
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>