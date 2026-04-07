<?php
require_once '../config/database.php';
$page_title = 'Mes Favoris - HairRoots';

if(!isset($_SESSION['user_id'])) {
    header('Location: /ecommerce/user/login.php?redirect=/ecommerce/user/wishlist.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Retirer un produit
if(isset($_GET['remove'])) {
    $pdo->prepare("DELETE FROM wishlists WHERE user_id=? AND product_id=?")->execute([$user_id, (int)$_GET['remove']]);
    header('Location: wishlist.php'); exit;
}

// Recuperer les favoris
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM wishlists w JOIN products p ON w.product_id = p.id LEFT JOIN categories c ON p.category_id = c.id WHERE w.user_id = ? AND p.active = 1 ORDER BY w.created_at DESC");
$stmt->execute([$user_id]);
$favorites = $stmt->fetchAll();

include '../includes/header.php';
?>
<style>
.wish-page{background:#FDF8F2;min-height:80vh;padding:40px 0}
.wish-hero{text-align:center;margin-bottom:40px}
.wish-hero h1{font-family:'Playfair Display',serif;font-size:2.5rem;font-weight:900;color:#3E1F0D}
.gold-line{width:60px;height:3px;background:linear-gradient(90deg,#C9A84C,#C1622F);border-radius:2px;margin:12px auto}

.wish-card{background:#fff;border-radius:20px;box-shadow:0 4px 20px rgba(62,31,13,0.06);border:1px solid #F5E6D3;overflow:hidden;transition:all 0.4s;height:100%}
.wish-card:hover{transform:translateY(-8px);box-shadow:0 15px 40px rgba(62,31,13,0.12)}
.wish-img-wrap{height:220px;overflow:hidden;position:relative}
.wish-img-wrap img{width:100%;height:100%;object-fit:cover;object-position:top;transition:transform 0.4s}
.wish-card:hover .wish-img-wrap img{transform:scale(1.05)}
.wish-img-ph{height:220px;background:linear-gradient(135deg,#F5E6D3,#FDEBD0);display:flex;align-items:center;justify-content:center;font-size:3.5rem}
.wish-body{padding:18px}
.wish-cat{background:#F5E6D3;color:#6B3A2A;padding:3px 12px;border-radius:10px;font-size:0.75rem;font-weight:600;display:inline-block;margin-bottom:8px}
.wish-name{font-family:'Playfair Display',serif;font-weight:700;color:#3E1F0D;font-size:1rem;margin-bottom:8px}
.wish-price{font-weight:900;color:#C1622F;font-size:1.1rem;margin-bottom:12px}
.wish-stock-ok{color:#2e7d32;font-size:0.78rem;font-weight:600}
.wish-stock-out{color:#c62828;font-size:0.78rem;font-weight:600}
.btn-wish-cart{background:linear-gradient(135deg,#C9A84C,#b8942e);color:#3E1F0D;border:none;border-radius:10px;padding:10px;font-weight:700;font-size:0.85rem;width:100%;cursor:pointer;transition:all 0.3s;text-decoration:none;display:block;text-align:center;margin-bottom:8px}
.btn-wish-cart:hover{background:linear-gradient(135deg,#C1622F,#a0491f);color:#fff;transform:translateY(-1px)}
.btn-wish-remove{background:#fce4e4;color:#c62828;border:none;border-radius:10px;padding:8px;font-size:0.8rem;font-weight:600;width:100%;cursor:pointer;transition:all 0.3s}
.btn-wish-remove:hover{background:#c62828;color:#fff}
.wish-remove-top{position:absolute;top:10px;right:10px;background:rgba(255,255,255,0.9);border:none;border-radius:50%;width:34px;height:34px;font-size:1rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s;box-shadow:0 2px 8px rgba(0,0,0,0.1)}
.wish-remove-top:hover{background:#fce4e4;color:#c62828}
.empty-wish{text-align:center;padding:80px 20px;background:#fff;border-radius:20px;border:1px solid #F5E6D3}

/* TOAST */
.toast-wish{position:fixed;bottom:30px;right:30px;background:#3E1F0D;color:#fff;padding:15px 25px;border-radius:12px;font-weight:500;box-shadow:0 10px 30px rgba(0,0,0,0.2);z-index:9999;opacity:0;transform:translateY(20px);transition:all 0.4s;border-left:4px solid #C9A84C}
.toast-wish.show{opacity:1;transform:translateY(0)}
</style>

<div class="wish-page">
<div class="container">

    <div class="wish-hero">
        <h1> Mes Favoris</h1>
        <div class="gold-line"></div>
        <p style="color:#6B3A2A;font-size:0.95rem">
            <?= count($favorites) ?> produit<?= count($favorites)>1?'s':'' ?> dans vos favoris
        </p>
    </div>

    <?php if(count($favorites) > 0): ?>

    <!-- ACTIONS EN MASSE -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;flex-wrap:wrap;gap:10px">
        <span style="color:#6B3A2A;font-size:0.9rem;font-weight:600">
            <?= count($favorites) ?> article<?= count($favorites)>1?'s':'' ?> sauvegarde<?= count($favorites)>1?'s':'' ?>
        </span>
        <button onclick="ajouterToutPanier()" style="background:linear-gradient(135deg,#C9A84C,#b8942e);color:#3E1F0D;border:none;border-radius:12px;padding:10px 22px;font-weight:700;font-size:0.88rem;cursor:pointer;transition:all 0.3s">
             Tout ajouter au panier
        </button>
    </div>

    <div class="row g-4">
        <?php foreach($favorites as $p): ?>
        <div class="col-lg-3 col-md-4 col-sm-6" id="wish-item-<?= $p['id'] ?>">
            <div class="wish-card">
                <div class="wish-img-wrap">
                    <?php if(!empty($p['image'])): ?>
                        <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                    <?php else: ?>
                        <div class="wish-img-ph"></div>
                    <?php endif; ?>
                    <button class="wish-remove-top" onclick="retirerFavori(<?= $p['id'] ?>)" title="Retirer des favoris"></button>
                </div>
                <div class="wish-body">
                    <?php if(!empty($p['category_name'])): ?>
                        <span class="wish-cat"><?= htmlspecialchars($p['category_name']) ?></span>
                    <?php endif; ?>
                    <h5 class="wish-name"><?= htmlspecialchars($p['name']) ?></h5>
                    <div class="wish-price"><?= number_format($p['price'],2) ?>€</div>
                    <?php if($p['stock'] > 0): ?>
                        <div class="wish-stock-ok mb-2"> En stock (<?= $p['stock'] ?>)</div>
                        <button class="btn-wish-cart" onclick="ajouterPanier(<?= $p['id'] ?>)">
                             Ajouter au panier
                        </button>
                    <?php else: ?>
                        <div class="wish-stock-out mb-2"> Rupture de stock</div>
                        <button class="btn-wish-cart" disabled style="opacity:0.5;cursor:not-allowed">
                             Indisponible
                        </button>
                    <?php endif; ?>
                    <a href="/ecommerce/products/detail.php?id=<?= $p['id'] ?>" class="btn-wish-remove" style="display:block;text-align:center;text-decoration:none;background:#F5E6D3;color:#3E1F0D;border-radius:10px;padding:8px;font-size:0.8rem;font-weight:600">
                         Voir le produit
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php else: ?>
    <div class="empty-wish">
        <div style="font-size:5rem;margin-bottom:20px"></div>
        <h3 style="font-family:'Playfair Display',serif;color:#3E1F0D">Aucun favori pour le moment</h3>
        <p style="color:#9a7c5c;margin:10px 0 30px">Ajoutez des produits a vos favoris en cliquant sur le coeur </p>
        <a href="/ecommerce/products/index.php" style="background:linear-gradient(135deg,#C9A84C,#b8942e);color:#3E1F0D;padding:14px 35px;border-radius:14px;font-weight:800;font-size:1rem;text-decoration:none;display:inline-block">
             Decouvrir nos produits
        </a>
    </div>
    <?php endif; ?>

</div>
</div>

<div class="toast-wish" id="wish-toast"></div>

<script>
function showToast(msg, ok=true) {
    const t = document.getElementById('wish-toast');
    t.textContent = msg;
    t.style.borderLeftColor = ok ? '#C9A84C' : '#C1622F';
    t.classList.add('show');
    setTimeout(()=>t.classList.remove('show'), 3000);
}

function ajouterPanier(productId) {
    fetch('/ecommerce/cart/add.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `product_id=${productId}&quantity=1`
    })
    .then(r => r.json())
    .then(data => {
        if(data.success) showToast(' Ajoute au panier !');
        else showToast(' ' + data.message, false);
    });
}

function retirerFavori(productId) {
    fetch('/ecommerce/cart/wishlist.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `product_id=${productId}`
    })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            showToast(' Retire des favoris');
            const item = document.getElementById('wish-item-' + productId);
            if(item) {
                item.style.opacity = '0';
                item.style.transform = 'scale(0.8)';
                item.style.transition = 'all 0.3s';
                setTimeout(()=>{ item.remove(); updateCount(); }, 300);
            }
        }
    });
}

function updateCount() {
    const remaining = document.querySelectorAll('[id^="wish-item-"]').length;
    if(remaining === 0) location.reload();
}

function ajouterToutPanier() {
    const items = document.querySelectorAll('[id^="wish-item-"]');
    let count = 0;
    items.forEach(item => {
        const id = item.id.replace('wish-item-','');
        const btn = item.querySelector('.btn-wish-cart');
        if(btn && !btn.disabled) {
            fetch('/ecommerce/cart/add.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `product_id=${id}&quantity=1`
            }).then(()=>{ count++; if(count===items.length) showToast(' Tous les articles ajoutes au panier !'); });
        }
    });
}
</script>
<?php include '../includes/footer.php'; ?>