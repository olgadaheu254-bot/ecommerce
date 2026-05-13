<?php
require_once 'config/database.php';
$page_title = 'Accueil - HairRoots';
include 'includes/header.php';

$stmt = $pdo->query("SELECT * FROM products WHERE featured = 1 AND active = 1 LIMIT 4");
$featured_products = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM coiffures_modeles WHERE tendance = 1 LIMIT 4");
$modeles_tendance = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM coiffeuses WHERE disponible = 1");
$coiffeuses = $stmt->fetchAll();
?>

<div class="container-fluid p-0">

    <!-- HERO -->
    <section style="background: url('/ecommerce/assets/images/beauty.jpg') center/cover no-repeat; padding: 80px 0; position:relative; overflow:hidden;">
        <div style="position:absolute;top:-50px;right:-50px;width:400px;height:400px;background:radial-gradient(circle,rgba(201,168,76,0.15),transparent);border-radius:50%;"></div>
        <div style="position:absolute;bottom:-50px;left:-50px;width:300px;height:300px;background:radial-gradient(circle,rgba(193,98,47,0.1),transparent);border-radius:50%;"></div>
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <div class="mb-4 d-flex flex-wrap gap-2">
                        <span style="background:#fff;border:2px solid #C9A84C;color:#3E1F0D;padding:6px 16px;border-radius:20px;font-size:0.85rem;font-weight:600;">Femmes</span>
                        <span style="background:#fff;border:2px solid #C9A84C;color:#3E1F0D;padding:6px 16px;border-radius:20px;font-size:0.85rem;font-weight:600;">Hommes</span>
                        <span style="background:#fff;border:2px solid #C9A84C;color:#3E1F0D;padding:6px 16px;border-radius:20px;font-size:0.85rem;font-weight:600;">Enfants</span>
                        <span style="background:#fff;border:2px solid #C9A84C;color:#3E1F0D;padding:6px 16px;border-radius:20px;font-size:0.85rem;font-weight:600;">Tous types</span>
                    </div>
                    <h1 style="font-family:'Playfair Display',serif;font-size:3.5rem;font-weight:900;color:#3E1F0D;line-height:1.2;">
                        Sublimez vos<br>
                        <span style="color:#C1622F;">Racines</span> avec<br>
                        <span style="color:#C9A84C;">HairRoots</span>
                    </h1>
                    <p style="font-size:1.1rem;color:#6B3A2A;margin:20px 0;">
                        Meches adaptees, soins capillaires premium et coiffeuses expertes —<br>
                        tout ce dont vos cheveux ont besoin, au meme endroit.
                    </p>
                    <div class="d-flex gap-3 flex-wrap mt-4">
                        <a href="products/index.php" class="btn btn-gold btn-lg px-4">
                            Decouvrir nos produits
                        </a>
                        <a href="rendez-vous.php"
                           style="background:transparent;border:2px solid #3E1F0D;color:#3E1F0D;padding:12px 28px;border-radius:25px;font-weight:600;font-size:1rem;text-decoration:none;"
                           onmouseover="this.style.background='#3E1F0D';this.style.color='#fff'"
                           onmouseout="this.style.background='transparent';this.style.color='#3E1F0D'">
                            Prendre RDV
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

</div>

<style>
@keyframes float {
    0%,100% { transform: translateY(0); }
    50%      { transform: translateY(-15px); }
}
</style>

<div class="container my-5">

    <!-- TYPES DE CHEVEUX -->
    <section class="mb-5">
        <h2 style="font-family:'Playfair Display',serif;font-size:2.2rem;font-weight:700;color:#3E1F0D;text-align:center;">Votre type de cheveux</h2>
        <div class="gold-line mx-auto"></div>
        <p class="text-center mb-4" style="color:#6B3A2A;">Des produits et meches specialement selectionnes pour vous</p>
        <div class="row g-3">
            <?php
            $types = [
                ['nom'=>'Boucles', 'icon'=>'assets/images/produits/pp3.jpg', 'bg'=>'linear-gradient(50deg,#8B4513,#C1622F)', 'cat'=>1],
                ['nom'=>'Crepus',  'icon'=>'assets/images/produits/pp4.jpg', 'bg'=>'linear-gradient(50deg,#2c1810,#6B3A2A)', 'cat'=>2],
                ['nom'=>'Lisses',  'icon'=>'assets/images/produits/pp.jpg',  'bg'=>'linear-gradient(50deg,#C9A84C,#8B7355)', 'cat'=>3],
                ['nom'=>'Ondules', 'icon'=>'assets/images/produits/pp1.jpg', 'bg'=>'linear-gradient(50deg,#A0522D,#D2691E)', 'cat'=>4],
            ];
            foreach($types as $t): ?>
            <div class="col-md-3 col-6">
                <a href="products/index.php?category=<?= $t['cat'] ?>"
                   class="d-block text-center text-decoration-none rounded-4 p-4"
                   style="background:<?= $t['bg'] ?>;transition:all 0.3s;box-shadow:0 5px 20px rgba(0,0,0,0.1);"
                   onmouseover="this.style.transform='translateY(-8px)';this.style.boxShadow='0 15px 35px rgba(0,0,0,0.2)'"
                   onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 5px 20px rgba(0,0,0,0.1)'">

                    <div style="margin-bottom:10px;">
                        <img src="<?= $t['icon'] ?>" alt="<?= $t['nom'] ?>"
                             style="width:38px;height:38px;object-fit:cover;border-radius:50%;border:2px solid rgba(255,255,255,0.4);">
                    </div>

                    <h5 style="color:#fff;font-family:'Playfair Display',serif;font-weight:700;margin:0;">
                        Cheveux <?= $t['nom'] ?>
                    </h5>

                    <p style="color:rgba(255,255,255,0.75);font-size:0.85rem;margin:5px 0 0;">
                        Meches & soins adaptes
                    </p>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- PRODUITS VEDETTES -->
    <?php if(!empty($featured_products)): ?>
    <section class="mb-5">
        <h2 style="font-family:'Playfair Display',serif;font-size:2.2rem;font-weight:700;color:#3E1F0D;text-align:center;">Nos Produits Vedettes</h2>
        <div class="gold-line mx-auto"></div>
        <div class="scroll-horizontal mt-2">
            <?php foreach($featured_products as $p): ?>
            <div class="scroll-item">
                <div class="product-card-hr h-100">
                    <div class="product-img-wrap position-relative">
                        <div style="height:220px;overflow:hidden;">
                            <img src="/ecommerce/<?= htmlspecialchars($p['image']) ?>"
                                 alt="<?= htmlspecialchars($p['name']) ?>"
                                 style="width:100%;height:100%;object-fit:cover;object-position:top;">
                        </div>
                        <span class="badge-featured">Vedette</span>
                        <div class="product-overlay">
                            <a href="products/detail.php?id=<?= $p['id'] ?>" class="overlay-btn">Voir</a>
                        </div>
                    </div>
                    <div class="product-body">
                        <button class="btn-favori-bottom" style="width:auto" onclick="toggleFavori(this, <?= $p['id'] ?>)">
                            <i class="bi bi-heart"></i> Favoris
                        </button>
                        <h6 class="product-name"><?= htmlspecialchars($p['name']) ?></h6>
                        <p class="product-desc"><?= substr(htmlspecialchars($p['description']), 0, 80) ?>...</p>
                        <div class="product-footer">
                            <span class="product-price"><?= number_format($p['price'], 2) ?>€</span>
                            <button class="btn-add-cart" onclick="addToCart(<?= $p['id'] ?>)">
                                Ajouter
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="products/index.php" class="btn btn-gold btn-lg px-5">Voir tous les produits →</a>
        </div>
    </section>
    <?php endif; ?>

    <!-- MODELES COIFFURE -->
    <?php if(!empty($modeles_tendance)): ?>
    <section class="mb-5 p-4 rounded-4" style="background:#F5E6D3;">
        <h2 style="font-family:'Playfair Display',serif;font-size:2.2rem;font-weight:700;color:#3E1F0D;text-align:center;">Inspirations du Moment</h2>
        <div class="gold-line mx-auto"></div>
        <div class="scroll-horizontal mt-2">
            <?php foreach($modeles_tendance as $m): ?>
            <div class="scroll-item">
                <div class="card border-0 shadow-sm h-100 position-relative" style="border-radius:15px;overflow:hidden;">
                    <div style="height:180px;overflow:hidden;border-radius:15px 15px 0 0;">
                        <img src="<?= htmlspecialchars($m['photo']) ?>"
                        alt="<?= htmlspecialchars($m['nom']) ?>"
                        style="width:100%;height:100%;object-fit:cover;object-position:top;">
                    </div>
                    <div style="position:absolute;bottom:0;left:0;right:0;background:linear-gradient(transparent,rgba(62,31,13,0.9));padding:20px 15px 15px;">
                        <h6 style="color:#fff;font-family:'Playfair Display',serif;margin-bottom:5px;"><?= htmlspecialchars($m['nom']) ?></h6>
                        <div class="d-flex gap-1 flex-wrap">
                            <span class="badge" style="background:#C9A84C;color:#3E1F0D;font-size:0.7rem;"><?= $m['type_cheveux'] ?></span>
                            <span class="badge bg-light text-dark" style="font-size:0.7rem;"><?= $m['genre'] ?></span>
                        </div>
                        <div style="color:rgba(255,255,255,0.85);font-size:0.82rem;margin-top:4px;">
                            <?= $m['duree_realisation'] ?> • A partir de <?= number_format($m['prix_estimation'],2) ?>€
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="coiffures.php" class="btn btn-lg px-5" style="background:#C1622F;color:#fff;border-radius:25px;font-weight:700;">Toutes les inspirations →</a>
        </div>
    </section>
    <?php endif; ?>

    <!-- COIFFEUSES -->
    <?php if(!empty($coiffeuses)): ?>
    <section class="mb-5">
        <h2 style="font-family:'Playfair Display',serif;font-size:2.2rem;font-weight:700;color:#3E1F0D;text-align:center;">Nos Coiffeuses Expertes</h2>
        <div class="gold-line mx-auto"></div>
        <div class="scroll-horizontal mt-2">
            <?php foreach($coiffeuses as $c): ?>
            <div class="scroll-item">
                <div class="card border-0 shadow-sm h-100 text-center"
                     style="border-radius:20px;overflow:hidden;transition:all 0.3s;"
                     onmouseover="this.style.transform='translateY(-8px)'"
                     onmouseout="this.style.transform='translateY(0)'">
                    <div style="height:240px;overflow:hidden;">
                        <img src="<?= htmlspecialchars($c['photo']) ?>"
                             alt="<?= htmlspecialchars($c['prenom']) ?>"
                             style="width:100%;height:100%;object-fit:cover;object-position:top;">
                    </div>
                    <div class="card-body p-4">
                        <h5 style="font-family:'Playfair Display',serif;color:#3E1F0D;font-weight:700;">
                            <?= htmlspecialchars($c['prenom'] . ' ' . $c['nom']) ?>
                        </h5>
                        <p style="color:#6B3A2A;font-size:0.88rem;"><?= htmlspecialchars($c['specialite']) ?></p>
                        <p style="font-size:0.85rem;color:#888;"><?= htmlspecialchars($c['bio']) ?></p>
                        <a href="rendez-vous.php?coiffeuse=<?= $c['id'] ?>" class="btn btn-gold w-100 mt-2">
                            Prendre RDV
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- AVANTAGES -->
    <section class="mb-5">
        <h2 style="font-family:'Playfair Display',serif;font-size:2.2rem;font-weight:700;color:#3E1F0D;text-align:center;">Pourquoi choisir HairRoots ?</h2>
        <div class="gold-line mx-auto"></div>
        <div class="row g-4 mt-2">
            <?php
            $avantages = [
                ['icon'=>'assets/icons/livraison.png', 'titre'=>'Livraison rapide',       'texte'=>'Livraison sous 48h partout en France',            'bg'=>'#FFF3E0'],
                ['icon'=>'assets/icons/expert.png',    'titre'=>'Experts capillaires',    'texte'=>'Coiffeuses professionnelles certifiees',           'bg'=>'#F3E5F5'],
                ['icon'=>'assets/icons/naturel.png',   'titre'=>'Produits naturels',      'texte'=>'Soins a base d\'ingredients naturels de qualite',  'bg'=>'#E8F5E9'],
                ['icon'=>'assets/icons/conseil.png',   'titre'=>'Conseils personnalises', 'texte'=>'Adaptes a votre type de cheveux specifique',       'bg'=>'#FFF8E1'],
            ];
            foreach($avantages as $a): ?>
            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm h-100 text-center p-4"
                     style="border-radius:20px;transition:all 0.3s;"
                     onmouseover="this.style.transform='translateY(-5px)'"
                     onmouseout="this.style.transform='translateY(0)'">
                    <div style="width:70px;height:70px;border-radius:50%;background:<?= $a['bg'] ?>;display:flex;align-items:center;justify-content:center;margin:0 auto 15px;">
                        <img src="<?= $a['icon'] ?>" alt="" style="width:35px;height:35px;object-fit:contain;">
                    </div>
                    <h6 style="color:#3E1F0D;font-weight:700;"><?= $a['titre'] ?></h6>
                    <p class="small text-muted mb-0"><?= $a['texte'] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- BANNER RDV -->
    <section class="mb-5">
        <div class="p-5 text-center text-white rounded-4"
             style="background:linear-gradient(135deg,#3E1F0D,#6B3A2A);position:relative;overflow:hidden;">
            <h2 style="font-family:'Playfair Display',serif;font-size:2rem;">Pret(e) a vous faire chouchouter ?</h2>
            <p style="color:#e8d5b7;font-size:1.05rem;margin:15px 0;">
                Reservez votre rendez-vous en ligne avec l'une de nos coiffeuses expertes
            </p>
            <a href="rendez-vous.php" class="btn btn-gold btn-lg px-5 mt-2">Reserver maintenant</a>
        </div>
    </section>

</div>

<div class="toast-prod" id="toast-prod"></div>
<?php include 'includes/footer.php'; ?>