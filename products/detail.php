<?php
require_once '../config/database.php';
$page_title = 'Produit - HairRoots';

if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: /ecommerce/products/index.php');
    exit;
}

$product_id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ? AND p.active = 1");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if(!$product) {
    header('Location: /ecommerce/products/index.php');
    exit;
}

$page_title = htmlspecialchars($product['name']) . ' - HairRoots';

// Récupérer toutes les images du produit
$stmt_imgs = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC, ordre ASC");
$stmt_imgs->execute([$product_id]);
$product_images = $stmt_imgs->fetchAll();

// Produits similaires
$stmt = $pdo->prepare("SELECT * FROM products WHERE category_id = ? AND id != ? AND active = 1 LIMIT 4");
$stmt->execute([$product['category_id'], $product_id]);
$similaires = $stmt->fetchAll();

include '../includes/header.php';
?>
<style>
.detail-page{background:#FDF8F2;min-height:80vh;padding:40px 0}

.breadcrumb-hr{display:flex;align-items:center;gap:8px;margin-bottom:30px;font-size:0.85rem;flex-wrap:wrap}
.breadcrumb-hr a{color:#C1622F;text-decoration:none;font-weight:600}
.breadcrumb-hr a:hover{color:#3E1F0D}
.breadcrumb-hr span{color:#9a7c5c}

/* IMAGE PRINCIPALE */
.product-img-main{width:100%;height:420px;object-fit:cover;object-position:top;border-radius:20px;box-shadow:0 10px 40px rgba(62,31,13,0.12)}
.product-img-ph{width:100%;height:420px;border-radius:20px;background:linear-gradient(135deg,#F5E6D3,#FDEBD0);display:flex;align-items:center;justify-content:center;font-size:6rem}

/* GALERIE MINIATURES */
.gallery-thumbs{display:flex;gap:10px;margin-top:12px;flex-wrap:wrap}
.gallery-thumb{width:70px;height:70px;border-radius:10px;object-fit:cover;cursor:pointer;border:2px solid #F5E6D3;transition:all 0.2s;opacity:0.7}
.gallery-thumb:hover,.gallery-thumb.active{border-color:#C9A84C;opacity:1;transform:scale(1.05)}

.product-detail-card{background:#fff;border-radius:20px;box-shadow:0 4px 20px rgba(62,31,13,0.06);border:1px solid #F5E6D3;padding:35px;height:100%}
.product-cat-badge{background:#F5E6D3;color:#6B3A2A;padding:5px 16px;border-radius:20px;font-size:0.78rem;font-weight:700;display:inline-block;margin-bottom:12px}
.product-name{font-family:'Playfair Display',serif;font-size:2rem;font-weight:900;color:#3E1F0D;margin-bottom:10px;line-height:1.2}
.product-price-big{font-family:'Playfair Display',serif;font-size:2.2rem;font-weight:900;color:#C1622F;margin-bottom:20px}
.product-desc{color:#6B3A2A;font-size:0.95rem;line-height:1.8;margin-bottom:25px}
.product-sku{color:#9a7c5c;font-size:0.78rem;margin-bottom:20px}

.stock-ok{background:#e8f5e9;color:#2e7d32;padding:6px 16px;border-radius:10px;font-size:0.82rem;font-weight:700;display:inline-flex;align-items:center;gap:6px}
.stock-low{background:#FFF8E1;color:#F57F17;padding:6px 16px;border-radius:10px;font-size:0.82rem;font-weight:700;display:inline-flex;align-items:center;gap:6px}
.stock-out{background:#fce4e4;color:#c62828;padding:6px 16px;border-radius:10px;font-size:0.82rem;font-weight:700;display:inline-flex;align-items:center;gap:6px}

.qty-wrap{display:flex;align-items:center;gap:15px;margin:20px 0}
.qty-label{font-weight:600;font-size:0.88rem;color:#3E1F0D}
.qty-control{display:flex;align-items:center;gap:8px;background:#F5E6D3;border-radius:25px;padding:5px}
.qty-btn{width:34px;height:34px;border-radius:50%;border:none;background:#fff;color:#3E1F0D;font-size:1.1rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s}
.qty-btn:hover{background:#C9A84C;color:#3E1F0D}
.qty-num{font-weight:800;color:#3E1F0D;min-width:30px;text-align:center;font-size:1rem}

.btn-add-big{background:linear-gradient(135deg,#C9A84C,#b8942e);color:#3E1F0D;border:none;border-radius:14px;padding:16px 30px;font-size:1rem;font-weight:800;flex:1;transition:all 0.3s;cursor:pointer}
.btn-add-big:hover{background:linear-gradient(135deg,#C1622F,#a0491f);color:#fff;transform:translateY(-2px);box-shadow:0 10px 25px rgba(193,98,47,0.3)}
.btn-wishlist{background:#F5E6D3;color:#3E1F0D;border:none;border-radius:14px;padding:16px 20px;font-size:1.1rem;transition:all 0.3s;cursor:pointer}
.btn-wishlist:hover{background:#fce4e4;color:#c62828}

.avantages{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:25px}
.avantage-item{display:flex;align-items:center;gap:8px;font-size:0.82rem;color:#6B3A2A;background:#FDFAF7;border-radius:10px;padding:10px 12px;border:1px solid #F5E6D3}
.avantage-icon{font-size:1.1rem;flex-shrink:0}

.detail-tabs{background:#fff;border-radius:20px;box-shadow:0 4px 20px rgba(62,31,13,0.06);border:1px solid #F5E6D3;overflow:hidden;margin-top:30px}
.tab-nav{display:flex;border-bottom:2px solid #F5E6D3;background:#FDFAF7}
.tab-nav-btn{padding:15px 25px;font-weight:600;font-size:0.9rem;color:#9a7c5c;background:none;border:none;cursor:pointer;transition:all 0.3s;border-bottom:3px solid transparent;margin-bottom:-2px}
.tab-nav-btn:hover{color:#3E1F0D}
.tab-nav-btn.active{color:#3E1F0D;border-bottom-color:#C9A84C;background:#fff}
.tab-content{padding:25px;display:none}
.tab-content.active{display:block}

.similaires-section{margin-top:50px}
.similaires-section h3{font-family:'Playfair Display',serif;font-size:1.6rem;font-weight:700;color:#3E1F0D;margin-bottom:25px}
.sim-card{background:#fff;border-radius:16px;box-shadow:0 4px 15px rgba(62,31,13,0.06);border:1px solid #F5E6D3;overflow:hidden;transition:all 0.3s;height:100%}
.sim-card:hover{transform:translateY(-6px);box-shadow:0 12px 30px rgba(62,31,13,0.1)}
.sim-img{height:180px;overflow:hidden}
.sim-img img{width:100%;height:100%;object-fit:cover;object-position:top;transition:transform 0.3s}
.sim-card:hover .sim-img img{transform:scale(1.05)}
.sim-img-ph{height:180px;background:linear-gradient(135deg,#F5E6D3,#FDEBD0);display:flex;align-items:center;justify-content:center;font-size:3rem}
.sim-body{padding:15px}
.sim-name{font-weight:700;color:#3E1F0D;font-size:0.9rem;margin-bottom:5px}
.sim-price{color:#C1622F;font-weight:800;font-size:1rem}
.btn-sim{background:#F5E6D3;color:#3E1F0D;border:none;border-radius:8px;padding:8px 15px;font-size:0.8rem;font-weight:700;width:100%;cursor:pointer;transition:all 0.3s;text-decoration:none;display:block;text-align:center;margin-top:10px}
.btn-sim:hover{background:linear-gradient(135deg,#C9A84C,#b8942e);color:#3E1F0D}

.toast-detail{position:fixed;bottom:30px;right:30px;background:#3E1F0D;color:#fff;padding:15px 25px;border-radius:12px;font-weight:500;box-shadow:0 10px 30px rgba(0,0,0,0.2);z-index:9999;opacity:0;transform:translateY(20px);transition:all 0.4s;border-left:4px solid #C9A84C}
.toast-detail.show{opacity:1;transform:translateY(0)}
</style>

<div class="detail-page">
<div class="container">

    <!-- BREADCRUMB -->
    <div class="breadcrumb-hr">
        <a href="/ecommerce/index.php"> Accueil</a>
        <span>›</span>
        <a href="/ecommerce/products/index.php">Produits</a>
        <?php if(!empty($product['category_name'])): ?>
        <span>›</span>
        <a href="/ecommerce/products/index.php?category=<?= $product['category_id'] ?>"><?= htmlspecialchars($product['category_name']) ?></a>
        <?php endif; ?>
        <span>›</span>
        <span style="color:#3E1F0D;font-weight:600"><?= htmlspecialchars($product['name']) ?></span>
    </div>

    <!-- PRODUIT PRINCIPAL -->
    <div class="row g-4">

        <!-- IMAGE + GALERIE -->
        <div class="col-lg-5">
            <?php
            // Image principale : priorité à product_images, sinon champ image
            $main_image = '';
            if(count($product_images) > 0) {
                $main_image = '/ecommerce/' . $product_images[0]['image'];
            } elseif(!empty($product['image'])) {
                $main_image = '/ecommerce/' . $product['image'];
            }
            ?>
            <?php if($main_image): ?>
                <img src="<?= htmlspecialchars($main_image) ?>"
                     alt="<?= htmlspecialchars($product['name']) ?>"
                     class="product-img-main"
                     id="mainProductImg">
            <?php else: ?>
                <div class="product-img-ph"></div>
            <?php endif; ?>

            <!-- MINIATURES si plusieurs photos -->
            <?php if(count($product_images) > 1): ?>
            <div class="gallery-thumbs">
                <?php foreach($product_images as $i => $img): ?>
                    <img src="/ecommerce/<?= htmlspecialchars($img['image']) ?>"
                         alt="Photo <?= $i+1 ?>"
                         class="gallery-thumb <?= $i === 0 ? 'active' : '' ?>"
                         onclick="changeMainImage(this, '/ecommerce/<?= htmlspecialchars($img['image']) ?>')">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- INFOS -->
        <div class="col-lg-7">
            <div class="product-detail-card">

                <?php if(!empty($product['category_name'])): ?>
                    <span class="product-cat-badge"> <?= htmlspecialchars($product['category_name']) ?></span>
                <?php endif; ?>

                <h1 class="product-name"><?= htmlspecialchars($product['name']) ?></h1>

                <?php if(!empty($product['sku'])): ?>
                    <p class="product-sku">Ref: <?= htmlspecialchars($product['sku']) ?></p>
                <?php endif; ?>

                <div class="product-price-big"><?= number_format($product['price'], 2) ?>€</div>

                <?php if(!empty($product['description'])): ?>
                    <p class="product-desc"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                <?php endif; ?>

                <!-- STOCK -->
                <div class="mb-3">
                    <?php if($product['stock'] > 10): ?>
                        <span class="stock-ok"> En stock (<?= $product['stock'] ?> disponibles)</span>
                    <?php elseif($product['stock'] > 0): ?>
                        <span class="stock-low"> Stock limite (<?= $product['stock'] ?> restants)</span>
                    <?php else: ?>
                        <span class="stock-out"> Rupture de stock</span>
                    <?php endif; ?>
                </div>

                <!-- QUANTITE -->
                <?php if($product['stock'] > 0): ?>
                <div class="qty-wrap">
                    <span class="qty-label">Quantite :</span>
                    <div class="qty-control">
                        <button class="qty-btn" onclick="changeQty(-1)"></button>
                        <span class="qty-num" id="qty-display">1</span>
                        <button class="qty-btn" onclick="changeQty(1)">+</button>
                    </div>
                </div>

                <div class="d-flex gap-3 mt-3">
                    <button class="btn-add-big" onclick="ajouterPanier()">
                         Ajouter au panier
                    </button>
                    <button class="btn-wishlist" id="btn-wishlist" onclick="toggleWishlist(<?= $product['id'] ?>)" title="Ajouter aux favoris"></button>
                </div>
                <?php endif; ?>

                <!-- AVANTAGES -->
                <div class="avantages">
                    <div class="avantage-item"><span class="avantage-icon"></span><span>Livraison gratuite des 50€</span></div>
                    <div class="avantage-item"><span class="avantage-icon">↩</span><span>Retour sous 30 jours</span></div>
                    <div class="avantage-item"><span class="avantage-icon"></span><span>Paiement securise</span></div>
                    <div class="avantage-item"><span class="avantage-icon"></span><span>Produits naturels</span></div>
                </div>

            </div>
        </div>
    </div>

    <!-- TABS DETAILS -->
    <div class="detail-tabs">
        <div class="tab-nav">
            <button class="tab-nav-btn active" onclick="showTab('desc', this)"> Description</button>
            <button class="tab-nav-btn" onclick="showTab('utilisation', this)"> Utilisation</button>
            <button class="tab-nav-btn" onclick="showTab('livraison', this)"> Livraison</button>
        </div>

        <div id="tab-desc" class="tab-content active">
            <p style="color:#6B3A2A;line-height:1.9;font-size:0.95rem">
                <?= !empty($product['description']) ? nl2br(htmlspecialchars($product['description'])) : 'Aucune description disponible pour ce produit.' ?>
            </p>
        </div>

        <div id="tab-utilisation" class="tab-content">
            <div style="color:#6B3A2A;font-size:0.95rem;line-height:1.9">
                <p><strong style="color:#3E1F0D">Comment utiliser ce produit :</strong></p>
                <ul style="padding-left:20px">
                    <li>Appliquer sur cheveux propres et legèrement humides</li>
                    <li>Repartir uniformement du cuir chevelu aux pointes</li>
                    <li>Laisser poser selon les instructions du produit</li>
                    <li>Rincer abondamment a l'eau tiede</li>
                    <li>Utiliser regulierement pour de meilleurs resultats</li>
                </ul>
                <p><strong style="color:#3E1F0D">Conseils de nos expertes :</strong></p>
                <p>Pour optimiser les resultats, nos coiffeuses recommandent d'associer ce produit a un massage du cuir chevelu pour stimuler la circulation sanguine et favoriser la penetration des actifs.</p>
            </div>
        </div>

        <div id="tab-livraison" class="tab-content">
            <div style="color:#6B3A2A;font-size:0.95rem;line-height:1.9">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div style="background:#F5E6D3;border-radius:12px;padding:18px">
                            <h6 style="color:#3E1F0D;font-weight:700;margin-bottom:10px"> Livraison standard</h6>
                            <p style="margin:0">Delai : 3 a 5 jours ouvrables<br>Gratuite des 50€ d'achat<br>4,99€ en dessous de 50€</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div style="background:#F5E6D3;border-radius:12px;padding:18px">
                            <h6 style="color:#3E1F0D;font-weight:700;margin-bottom:10px"> Livraison express</h6>
                            <p style="margin:0">Delai : 24h a 48h<br>Disponible en semaine<br>9,99€ quel que soit le montant</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div style="background:#F5E6D3;border-radius:12px;padding:18px">
                            <h6 style="color:#3E1F0D;font-weight:700;margin-bottom:10px">↩ Retours</h6>
                            <p style="margin:0">Retour gratuit sous 30 jours<br>Produit non ouvert uniquement<br>Remboursement sous 5 jours</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div style="background:#F5E6D3;border-radius:12px;padding:18px">
                            <h6 style="color:#3E1F0D;font-weight:700;margin-bottom:10px"> Emballage</h6>
                            <p style="margin:0">Emballage eco-responsable<br>Protection garantie<br>Discret et soigne</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- PRODUITS SIMILAIRES -->
    <?php if(count($similaires) > 0): ?>
    <div class="similaires-section">
        <h3> Vous aimerez aussi</h3>
        <div class="row g-4">
            <?php foreach($similaires as $s): ?>
            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="sim-card">
                    <div class="sim-img">
                        <?php if(!empty($s['image'])): ?>
                            <img src="/ecommerce/<?= htmlspecialchars($s['image']) ?>"
                                 alt="<?= htmlspecialchars($s['name']) ?>">
                        <?php else: ?>
                            <div class="sim-img-ph"></div>
                        <?php endif; ?>
                    </div>
                    <div class="sim-body">
                        <div class="sim-name"><?= htmlspecialchars($s['name']) ?></div>
                        <div class="sim-price"><?= number_format($s['price'],2) ?>€</div>
                        <a href="detail.php?id=<?= $s['id'] ?>" class="btn-sim">Voir le produit →</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>
</div>

<!-- TOAST -->
<div class="toast-detail" id="detail-toast"></div>

<script>
let qty = 1;
const maxStock = <?= (int)$product['stock'] ?>;

function changeQty(change) {
    qty = Math.max(1, Math.min(maxStock, qty + change));
    document.getElementById('qty-display').textContent = qty;
}

function ajouterPanier() {
    fetch('/ecommerce/cart/add.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `product_id=<?= $product['id'] ?>&quantity=${qty}`
    })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            showToast(' Ajoute au panier !');
            const badge = document.querySelector('.cart-badge');
            if(badge && data.cart_count) badge.textContent = data.cart_count;
        } else {
            showToast(data.message || 'Erreur', false);
        }
    })
    .catch(() => showToast('Erreur reseau', false));
}

function showToast(msg, ok=true) {
    const t = document.getElementById('detail-toast');
    t.textContent = msg;
    t.style.borderLeftColor = ok ? '#C9A84C' : '#C1622F';
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
}

function toggleWishlist(productId) {
    fetch('/ecommerce/cart/wishlist.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `product_id=${productId}`
    })
    .then(r => r.json())
    .then(data => {
        if(data.redirect) { window.location = data.redirect; return; }
        const btn = document.getElementById('btn-wishlist');
        if(data.action === 'added') {
            btn.textContent = '';
            showToast(' Ajoute aux favoris !');
        } else {
            btn.textContent = '';
            showToast(' Retire des favoris');
        }
    });
}

function showTab(id, btn) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-nav-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + id).classList.add('active');
    btn.classList.add('active');
}

// Changer l'image principale en cliquant sur une miniature
function changeMainImage(thumb, src) {
    document.getElementById('mainProductImg').src = src;
    document.querySelectorAll('.gallery-thumb').forEach(t => t.classList.remove('active'));
    thumb.classList.add('active');
}
</script>

<?php include '../includes/footer.php'; ?>