<?php
require_once '../config/database.php';
$page_title = 'Mon Panier - HairRoots';

if(!isset($_SESSION['cart'])) $_SESSION['cart'] = array();

$cart_items = array();
$total = 0;

if(count($_SESSION['cart']) > 0) {
    $ids = array_keys($_SESSION['cart']);
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id IN ($placeholders)");
    $stmt->execute($ids);
    $products = $stmt->fetchAll();
    foreach($products as $product) {
        $quantity = $_SESSION['cart'][$product['id']];
        $subtotal = $product['price'] * $quantity;
        $total += $subtotal;
        $cart_items[] = array('product'=>$product, 'quantity'=>$quantity, 'subtotal'=>$subtotal);
    }
}

$livraison = $total >= 50 ? 0 : 4.99;
$total_final = $total + $livraison;

include '../includes/header.php';
?>
<style>
.cart-page{background:#FDF8F2;min-height:80vh;padding:40px 0}
.cart-card{background:#fff;border-radius:20px;box-shadow:0 4px 20px rgba(62,31,13,0.06);border:1px solid #F5E6D3;overflow:hidden;margin-bottom:20px}
.cart-card-header{background:linear-gradient(135deg,#F5E6D3,#FDEBD0);padding:18px 25px;border-bottom:1px solid #F0D9C0;display:flex;align-items:center;gap:10px}
.cart-card-header h5{font-family:'Playfair Display',serif;color:#3E1F0D;font-weight:700;margin:0;font-size:1.1rem}
.cart-card-body{padding:20px}
.cart-item{display:flex;align-items:center;gap:15px;padding:15px 0;border-bottom:1px solid #F5E6D3}
.cart-item:last-child{border-bottom:none}
.cart-item-img{width:80px;height:80px;border-radius:12px;object-fit:cover;flex-shrink:0;border:2px solid #F5E6D3}
.cart-item-img-placeholder{width:80px;height:80px;border-radius:12px;background:linear-gradient(135deg,#F5E6D3,#FDEBD0);display:flex;align-items:center;justify-content:center;font-size:2rem;flex-shrink:0}
.cart-item-info{flex:1}
.cart-item-name{font-weight:700;color:#3E1F0D;font-size:0.95rem;margin-bottom:3px}
.cart-item-cat{background:#F5E6D3;color:#6B3A2A;padding:2px 10px;border-radius:10px;font-size:0.75rem;font-weight:600;display:inline-block;margin-bottom:5px}
.cart-item-price{color:#C1622F;font-weight:600;font-size:0.88rem}
.cart-item-subtotal{font-weight:800;color:#3E1F0D;font-size:1rem;text-align:right;min-width:70px}
.qty-control{display:flex;align-items:center;gap:8px;background:#F5E6D3;border-radius:25px;padding:4px}
.qty-btn{width:30px;height:30px;border-radius:50%;border:none;background:#fff;color:#3E1F0D;font-size:1rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s;line-height:1}
.qty-btn:hover{background:#C9A84C;color:#3E1F0D}
.qty-num{font-weight:700;color:#3E1F0D;min-width:25px;text-align:center;font-size:0.95rem}
.btn-remove{background:none;border:none;color:#ddd;cursor:pointer;font-size:1.1rem;padding:5px;border-radius:8px;transition:all 0.2s}
.btn-remove:hover{background:#fce4e4;color:#c62828}
.summary-card{background:#fff;border-radius:20px;box-shadow:0 4px 20px rgba(62,31,13,0.06);border:1px solid #F5E6D3;overflow:hidden;position:sticky;top:80px}
.summary-header{background:linear-gradient(135deg,#3E1F0D,#6B3A2A);padding:18px 25px}
.summary-header h5{font-family:'Playfair Display',serif;color:#C9A84C;font-weight:700;margin:0}
.summary-body{padding:25px}
.summary-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;font-size:0.92rem;color:#6B3A2A}
.summary-total{display:flex;justify-content:space-between;align-items:center;padding-top:15px;border-top:2px solid #F5E6D3;margin-top:5px}
.summary-total-label{font-family:'Playfair Display',serif;font-size:1.2rem;font-weight:700;color:#3E1F0D}
.summary-total-price{font-family:'Playfair Display',serif;font-size:1.5rem;font-weight:900;color:#C1622F}
.btn-checkout{background:linear-gradient(135deg,#C9A84C,#b8942e);color:#3E1F0D;border:none;border-radius:12px;padding:14px;font-size:1rem;font-weight:700;width:100%;transition:all 0.3s;cursor:pointer;text-decoration:none;display:block;text-align:center;margin-bottom:10px}
.btn-checkout:hover{background:linear-gradient(135deg,#C1622F,#a0491f);color:#fff;transform:translateY(-2px);box-shadow:0 8px 20px rgba(193,98,47,0.3)}
.btn-continue{background:#F5E6D3;color:#3E1F0D;border:none;border-radius:12px;padding:12px;font-size:0.9rem;font-weight:600;width:100%;text-decoration:none;display:block;text-align:center;transition:all 0.3s}
.btn-continue:hover{background:#FDEBD0;color:#3E1F0D}
.livraison-badge{background:#e8f5e9;color:#2e7d32;border-radius:10px;padding:8px 15px;font-size:0.82rem;font-weight:600;text-align:center;margin-bottom:15px}
.livraison-progress{background:#F5E6D3;border-radius:10px;height:6px;margin-bottom:5px;overflow:hidden}
.livraison-progress-bar{background:linear-gradient(90deg,#C9A84C,#C1622F);height:100%;border-radius:10px;transition:width 0.5s}
.empty-cart{text-align:center;padding:80px 20px}
.empty-cart-icon{font-size:6rem;margin-bottom:20px;opacity:0.5}
.toast-cart{position:fixed;bottom:30px;right:30px;background:#3E1F0D;color:#fff;padding:15px 25px;border-radius:12px;font-weight:500;box-shadow:0 10px 30px rgba(0,0,0,0.2);z-index:9999;opacity:0;transform:translateY(20px);transition:all 0.4s;border-left:4px solid #C9A84C}
.toast-cart.show{opacity:1;transform:translateY(0)}
</style>

<div class="cart-page">
<div class="container">

<div class="mb-4">
    <h1 style="font-family:'Playfair Display',serif;color:#3E1F0D;font-size:2rem;font-weight:900">
         Mon Panier
        <?php if(count($cart_items)>0): ?>
        <span style="background:#F5E6D3;color:#C1622F;font-size:1rem;padding:4px 14px;border-radius:15px;font-family:'Poppins',sans-serif;font-weight:700;vertical-align:middle;margin-left:10px">
            <?= count($cart_items) ?> article<?= count($cart_items)>1?'s':'' ?>
        </span>
        <?php endif; ?>
    </h1>
</div>

<?php if(count($cart_items) > 0): ?>
<div class="row g-4">

    <!-- ARTICLES -->
    <div class="col-lg-8">
        <div class="cart-card">
            <div class="cart-card-header">
                <i class="bi bi-bag" style="color:#C9A84C;font-size:1.2rem"></i>
                <h5>Mes articles</h5>
            </div>
            <div class="cart-card-body">
                <?php foreach($cart_items as $item): $p=$item['product']; ?>
                <div class="cart-item" id="item-<?= $p['id'] ?>">
                    <!-- IMAGE -->
                    <?php if(!empty($p['image'])): ?>
                        <img src="/ecommerce/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" class="cart-item-img">
                    <?php else: ?>
                        <div class="cart-item-img-placeholder">🌿</div>
                    <?php endif; ?>

                    <!-- INFOS -->
                    <div class="cart-item-info">
                        <?php if(!empty($p['category_name'])): ?>
                            <span class="cart-item-cat"><?= htmlspecialchars($p['category_name']) ?></span>
                        <?php endif; ?>
                        <div class="cart-item-name"><?= htmlspecialchars($p['name']) ?></div>
                        <div class="cart-item-price"><?= number_format($p['price'],2) ?>€ / unité</div>
                    </div>

                    <!-- QUANTITE -->
                    <div class="qty-control">
                        <button class="qty-btn" onclick="updateQuantity(<?= $p['id'] ?>, -1)">−</button>
                        <span class="qty-num" id="qty-<?= $p['id'] ?>"><?= $item['quantity'] ?></span>
                        <button class="qty-btn" onclick="updateQuantity(<?= $p['id'] ?>, 1)">+</button>
                    </div>

                    <!-- SOUS-TOTAL -->
                    <div class="cart-item-subtotal" id="sub-<?= $p['id'] ?>">
                        <?= number_format($item['subtotal'],2) ?>€
                    </div>

                    <!-- SUPPRIMER -->
                    <button class="btn-remove" onclick="removeFromCart(<?= $p['id'] ?>)" title="Retirer">
                        <i class="bi bi-x-circle"></i>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- CONTINUER ACHATS -->
        <a href="/ecommerce/products/index.php" style="color:#C1622F;text-decoration:none;font-weight:600;font-size:0.9rem;">
            ← Continuer mes achats
        </a>
    </div>

    <!-- RECAPITULATIF -->
    <div class="col-lg-4">
        <div class="summary-card">
            <div class="summary-header">
                <h5> Récapitulatif</h5>
            </div>
            <div class="summary-body">

                <!-- BARRE LIVRAISON GRATUITE -->
                <?php if($total < 50): ?>
                <div style="margin-bottom:15px;">
                    <div style="font-size:0.82rem;color:#6B3A2A;margin-bottom:6px;">
                        Plus que <strong style="color:#C1622F"><?= number_format(50-$total,2) ?>€</strong> pour la livraison gratuite 
                    </div>
                    <div class="livraison-progress">
                        <div class="livraison-progress-bar" style="width:<?= min(100,($total/50)*100) ?>%"></div>
                    </div>
                </div>
                <?php else: ?>
                <div class="livraison-badge"> Livraison gratuite débloquée !</div>
                <?php endif; ?>

                <div class="summary-row">
                    <span>Sous-total (<?= count($cart_items) ?> article<?= count($cart_items)>1?'s':'' ?>)</span>
                    <strong><?= number_format($total,2) ?>€</strong>
                </div>
                <div class="summary-row">
                    <span>Livraison</span>
                    <?php if($livraison == 0): ?>
                        <strong style="color:#2e7d32">Gratuite </strong>
                    <?php else: ?>
                        <strong><?= number_format($livraison,2) ?>€</strong>
                    <?php endif; ?>
                </div>

                <div class="summary-total">
                    <span class="summary-total-label">Total</span>
                    <span class="summary-total-price"><?= number_format($total_final,2) ?>€</span>
                </div>

                <div class="mt-4">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <a href="checkout.php" class="btn-checkout"> Passer la commande</a>
                    <?php else: ?>
                        <a href="/ecommerce/user/login.php?redirect=/ecommerce/cart/checkout.php" class="btn-checkout">
                             Se connecter pour commander
                        </a>
                    <?php endif; ?>
                    <a href="/ecommerce/index.php" class="btn-continue">← Continuer mes achats</a>
                </div>

                <!-- PAIEMENT SECURISE -->
                <div class="mt-4 text-center">
                    <p style="font-size:0.78rem;color:#9a7c5c;margin-bottom:8px;"> Paiement 100% sécurisé</p>
                    <div class="d-flex justify-content-center gap-2">
                        <span style="background:#F5E6D3;color:#3E1F0D;padding:4px 12px;border-radius:8px;font-size:0.75rem;font-weight:600"> CB</span>
                        <span style="background:#F5E6D3;color:#3E1F0D;padding:4px 12px;border-radius:8px;font-size:0.75rem;font-weight:600"> PayPal</span>
                        <span style="background:#F5E6D3;color:#3E1F0D;padding:4px 12px;border-radius:8px;font-size:0.75rem;font-weight:600"> Apple Pay</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<?php else: ?>
<!-- PANIER VIDE -->
<div class="cart-card">
    <div class="cart-card-body">
        <div class="empty-cart">
            <div class="empty-cart-icon"></div>
            <h3 style="font-family:'Playfair Display',serif;color:#3E1F0D;">Votre panier est vide</h3>
            <p style="color:#9a7c5c;font-size:1rem;margin:10px 0 30px;">Découvrez nos mèches et soins capillaires adaptés à vos cheveux</p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="/ecommerce/products/index.php" class="btn-checkout" style="display:inline-block;width:auto;padding:14px 35px;">
                     Voir nos produits
                </a>
                <a href="/ecommerce/coiffures.php" style="background:#F5E6D3;color:#3E1F0D;border-radius:12px;padding:14px 35px;font-weight:700;text-decoration:none;display:inline-block;">
                     Inspirations coiffures
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

</div>
</div>

<!-- TOAST -->
<div class="toast-cart" id="cart-toast"></div>

<script>
function showToast(msg, ok=true) {
    const t = document.getElementById('cart-toast');
    t.textContent = msg;
    t.style.borderLeftColor = ok ? '#C9A84C' : '#C1622F';
    t.classList.add('show');
    setTimeout(()=>t.classList.remove('show'), 3000);
}

function updateQuantity(productId, change) {
    const qtyEl = document.getElementById('qty-' + productId);
    let currentQty = parseInt(qtyEl.textContent);
    let newQty = currentQty + change;

    if(newQty < 1) {
        if(confirm('Retirer ce produit du panier ?')) removeFromCart(productId);
        return;
    }

    fetch('update.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `product_id=${productId}&quantity=${newQty}`
    })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            showToast('Quantite mise a jour !');
            setTimeout(()=>location.reload(), 800);
        } else { showToast(data.message, false); }
    });
}

function removeFromCart(productId) {
    fetch('remove.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `product_id=${productId}`
    })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            showToast('Article retire du panier');
            const item = document.getElementById('item-' + productId);
            if(item) { item.style.opacity='0'; item.style.transform='translateX(20px)'; item.style.transition='all 0.3s'; }
            setTimeout(()=>location.reload(), 600);
        } else { showToast(data.message, false); }
    });
}
</script>

<?php include '../includes/footer.php'; ?>