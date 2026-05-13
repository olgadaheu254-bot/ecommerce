<?php
/**
 * user/wishlist_inspirations.php
 * Page qui affiche les inspirations sauvegardees par l'utilisateur
 */
require_once '../config/database.php';
$page_title = 'Mes Inspirations Favorites - HairRoots';

if (!isset($_SESSION['user_id'])) {
    header('Location: /ecommerce/user/login.php?redirect=/ecommerce/user/wishlist_inspirations.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Retirer une inspiration
if (isset($_GET['remove'])) {
    $pdo->prepare("DELETE FROM wishlist_inspirations WHERE user_id = ? AND modele_id = ?")
        ->execute([$user_id, (int)$_GET['remove']]);
    header('Location: wishlist_inspirations.php');
    exit;
}

// Recuperer les inspirations favorites
$stmt = $pdo->prepare("
    SELECT cm.*
    FROM wishlist_inspirations wi
    JOIN coiffures_modeles cm ON wi.modele_id = cm.id
    WHERE wi.user_id = ?
    ORDER BY wi.created_at DESC
");
$stmt->execute([$user_id]);
$favorites = $stmt->fetchAll();

include '../includes/header.php';
?>
<style>
.wish-page { background:#FDF8F2; min-height:80vh; padding:40px 0; }
.wish-hero { text-align:center; margin-bottom:40px; }
.wish-hero h1 { font-family:'Playfair Display',serif; font-size:2.5rem; font-weight:900; color:#3E1F0D; }
.gold-line { width:60px; height:3px; background:linear-gradient(90deg,#C9A84C,#C1622F); border-radius:2px; margin:12px auto; }
.inspi-fav-card { background:#fff; border-radius:20px; box-shadow:0 4px 20px rgba(62,31,13,0.06); border:1px solid #F5E6D3; overflow:hidden; transition:all 0.4s; height:100%; }
.inspi-fav-card:hover { transform:translateY(-8px); box-shadow:0 15px 40px rgba(62,31,13,0.12); }
.inspi-img-wrap { height:220px; overflow:hidden; position:relative; }
.inspi-img-wrap img { width:100%; height:100%; object-fit:cover; object-position:top; transition:transform 0.4s; }
.inspi-fav-card:hover .inspi-img-wrap img { transform:scale(1.05); }
.inspi-img-ph { height:220px; background:linear-gradient(135deg,#F5E6D3,#FDEBD0); display:flex; align-items:center; justify-content:center; font-size:3.5rem; }
.inspi-body { padding:18px; }
.inspi-name { font-family:'Playfair Display',serif; font-weight:700; color:#3E1F0D; font-size:1rem; margin-bottom:8px; }
.inspi-tag { padding:3px 10px; border-radius:8px; font-size:0.72rem; font-weight:600; background:#F5E6D3; color:#6B3A2A; display:inline-block; margin-right:4px; margin-bottom:6px; }
.btn-reserver { background:linear-gradient(135deg,#C9A84C,#b8942e); color:#3E1F0D; border:none; border-radius:10px; padding:10px; font-weight:700; font-size:0.85rem; width:100%; text-decoration:none; display:block; text-align:center; transition:all 0.3s; margin-bottom:8px; }
.btn-reserver:hover { background:linear-gradient(135deg,#C1622F,#a0491f); color:#fff; }
.btn-retirer { background:#fce4e4; color:#c62828; border:none; border-radius:10px; padding:8px; font-size:0.8rem; font-weight:600; width:100%; cursor:pointer; transition:all 0.3s; text-decoration:none; display:block; text-align:center; }
.btn-retirer:hover { background:#c62828; color:#fff; }
.empty-wish { text-align:center; padding:80px 20px; background:#fff; border-radius:20px; border:1px solid #F5E6D3; }
.wish-remove-top { position:absolute; top:10px; right:10px; background:rgba(255,255,255,0.9); border:none; border-radius:50%; width:34px; height:34px; font-size:1rem; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all 0.2s; box-shadow:0 2px 8px rgba(0,0,0,0.1); color:#9a7c5c; }
.wish-remove-top:hover { background:#fce4e4; color:#c62828; }
</style>

<div class="wish-page">
<div class="container">

    <div class="wish-hero">
        <h1>Mes Inspirations Favorites</h1>
        <div class="gold-line"></div>
        <p style="color:#6B3A2A;font-size:0.95rem;">
            <?= count($favorites) ?> inspiration<?= count($favorites) > 1 ? 's' : '' ?> sauvegardee<?= count($favorites) > 1 ? 's' : '' ?>
        </p>
    </div>

    <?php if (count($favorites) > 0): ?>
    <div class="row g-4">
        <?php foreach ($favorites as $m):
            $diff_class = $m['difficulte'] === 'Facile' ? 'diff-facile' : ($m['difficulte'] === 'Difficile' ? 'diff-difficile' : 'diff-moyen');
        ?>
        <div class="col-lg-3 col-md-4 col-sm-6" id="inspi-item-<?= $m['id'] ?>">
            <div class="inspi-fav-card">
                <div class="inspi-img-wrap">
                    <?php if (!empty($m['photo'])): ?>
                        <img src="/ecommerce/<?= htmlspecialchars($m['photo']) ?>" alt="<?= htmlspecialchars($m['nom']) ?>">
                    <?php else: ?>
                        <div class="inspi-img-ph"></div>
                    <?php endif; ?>
                    <button class="wish-remove-top" onclick="retirerInspi(<?= $m['id'] ?>)" title="Retirer des favoris">
                        <i class="bi bi-heart-fill" style="color:#c62828;"></i>
                    </button>
                </div>
                <div class="inspi-body">
                    <h5 class="inspi-name"><?= htmlspecialchars($m['nom']) ?></h5>
                    <span class="inspi-tag"><?= htmlspecialchars($m['type_cheveux']) ?></span>
                    <?php if (!empty($m['genre'])): ?>
                        <span class="inspi-tag"><?= htmlspecialchars($m['genre']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($m['difficulte'])): ?>
                        <span class="inspi-tag"><?= htmlspecialchars($m['difficulte']) ?></span>
                    <?php endif; ?>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin:10px 0;padding-top:10px;border-top:1px solid #F5E6D3;">
                        <span style="font-weight:800;color:#C1622F;">A partir de <?= number_format($m['prix_estimation'], 2) ?>€</span>
                        <span style="color:#9a7c5c;font-size:0.78rem;"><?= htmlspecialchars($m['duree_realisation']) ?></span>
                    </div>
                    <a href="/ecommerce/rendez-vous.php?prestation=<?= urlencode($m['nom']) ?>" class="btn-reserver">
                        Reserver ce style
                    </a>
                    <a href="wishlist_inspirations.php?remove=<?= $m['id'] ?>" class="btn-retirer">
                        Retirer des favoris
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php else: ?>
    <div class="empty-wish">
        <div style="font-size:5rem;margin-bottom:20px;"></div>
        <h3 style="font-family:'Playfair Display',serif;color:#3E1F0D;">Aucune inspiration sauvegardee</h3>
        <p style="color:#9a7c5c;margin:10px 0 30px;">Ajoutez des inspirations en cliquant sur le coeur</p>
        <a href="/ecommerce/coiffures.php"
           style="background:linear-gradient(135deg,#C9A84C,#b8942e);color:#3E1F0D;padding:14px 35px;
                  border-radius:14px;font-weight:800;font-size:1rem;text-decoration:none;display:inline-block;">
            Voir les inspirations
        </a>
    </div>
    <?php endif; ?>

</div>
</div>

<script>
function retirerInspi(modeleId) {
    fetch('/ecommerce/cart/wishlist_inspiration_toggle.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `modele_id=${modeleId}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const item = document.getElementById('inspi-item-' + modeleId);
            if (item) {
                item.style.opacity = '0';
                item.style.transform = 'scale(0.8)';
                item.style.transition = 'all 0.3s';
                setTimeout(() => {
                    item.remove();
                    const remaining = document.querySelectorAll('[id^="inspi-item-"]').length;
                    if (remaining === 0) location.reload();
                }, 300);
            }
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>