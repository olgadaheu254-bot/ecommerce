<?php
require_once 'config/database.php';
$page_title = 'Inspirations Coiffures - HairRoots';

// FILTRES
$filtre_type   = isset($_GET['type'])   ? $_GET['type']   : '';
$filtre_genre  = isset($_GET['genre'])  ? $_GET['genre']  : '';
$filtre_diff   = isset($_GET['diff'])   ? $_GET['diff']   : '';

$where = []; $params = [];
if($filtre_type)  { $where[] = "type_cheveux = ?";  $params[] = $filtre_type; }
if($filtre_genre) { $where[] = "genre = ?";          $params[] = $filtre_genre; }
if($filtre_diff)  { $where[] = "difficulte = ?";     $params[] = $filtre_diff; }
$where_sql = count($where) ? 'WHERE '.implode(' AND ',$where) : '';

$stmt = $pdo->prepare("SELECT * FROM coiffures_modeles $where_sql ORDER BY tendance DESC, nom ASC");
$stmt->execute($params);
$modeles = $stmt->fetchAll();

// Tendances uniquement
$stmt_tendance = $pdo->query("SELECT * FROM coiffures_modeles WHERE tendance = 1 LIMIT 4");
$tendances = $stmt_tendance->fetchAll();

include 'includes/header.php';
?>
<style>
.inspi-page{background:#FDF8F2;min-height:80vh;padding:50px 0}
.page-hero{text-align:center;margin-bottom:45px}
.page-hero h1{font-family:'Playfair Display',serif;font-size:2.8rem;font-weight:900;color:#3E1F0D}
.page-hero p{color:#6B3A2A;font-size:1.05rem;margin-top:10px}
.gold-line{width:60px;height:3px;background:linear-gradient(90deg,#C9A84C,#C1622F);border-radius:2px;margin:15px auto}

/* FILTRES */
.filter-bar{background:#fff;border-radius:16px;box-shadow:0 4px 15px rgba(62,31,13,0.06);border:1px solid #F5E6D3;padding:20px 25px;margin-bottom:35px;display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.filter-btn{border:2px solid #F5E6D3;border-radius:25px;padding:8px 18px;cursor:pointer;background:#FDFAF7;font-size:0.85rem;font-weight:600;color:#3E1F0D;text-decoration:none;transition:all 0.3s;white-space:nowrap}
.filter-btn:hover{border-color:#C9A84C;background:#FFFDF5;color:#3E1F0D}
.filter-btn.active{border-color:#C1622F;background:linear-gradient(135deg,#C1622F,#a0491f);color:#fff}
.filter-sep{width:1px;height:30px;background:#F5E6D3}

/* TENDANCES */
.tendances-section{margin-bottom:50px}
.tendances-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:15px}
.tendances-grid .big{grid-row:span 2}
.tendance-card{position:relative;border-radius:20px;overflow:hidden;cursor:pointer;transition:all 0.4s}
.tendance-card:hover{transform:scale(1.02);box-shadow:0 15px 40px rgba(62,31,13,0.2)}
.tendance-card img{width:100%;height:100%;object-fit:cover;object-position:top;display:block}
.tendance-card .t-img-ph{width:100%;height:100%;background:linear-gradient(135deg,#3E1F0D,#6B3A2A);display:flex;align-items:center;justify-content:center;font-size:4rem}
.tendance-overlay{position:absolute;bottom:0;left:0;right:0;background:linear-gradient(transparent,rgba(62,31,13,0.92));padding:30px 20px 20px}
.tendance-badge{position:absolute;top:15px;left:15px;background:linear-gradient(135deg,#C9A84C,#b8942e);color:#3E1F0D;padding:4px 14px;border-radius:12px;font-size:0.72rem;font-weight:700}

/* GRILLE MODELES */
.modele-card{background:#fff;border-radius:20px;box-shadow:0 4px 20px rgba(62,31,13,0.06);border:1px solid #F5E6D3;overflow:hidden;transition:all 0.4s;height:100%}
.modele-card:hover{transform:translateY(-8px);box-shadow:0 15px 40px rgba(62,31,13,0.12)}
.modele-img-wrap{height:220px;overflow:hidden;position:relative}
.modele-img-wrap img{width:100%;height:100%;object-fit:cover;object-position:top;transition:transform 0.4s}
.modele-card:hover .modele-img-wrap img{transform:scale(1.08)}
.modele-img-ph{width:100%;height:220px;background:linear-gradient(135deg,#F5E6D3,#FDEBD0);display:flex;align-items:center;justify-content:center;font-size:3.5rem}
.modele-body{padding:18px}
.modele-name{font-family:'Playfair Display',serif;font-weight:700;color:#3E1F0D;font-size:1rem;margin-bottom:8px}
.modele-tags{display:flex;flex-wrap:wrap;gap:5px;margin-bottom:10px}
.modele-tag{padding:3px 10px;border-radius:8px;font-size:0.72rem;font-weight:600}
.tag-type{background:#F5E6D3;color:#6B3A2A}
.tag-genre{background:#E8F5E9;color:#2E7D32}
.tag-tendance{background:linear-gradient(135deg,#C9A84C,#b8942e);color:#3E1F0D}
.modele-info{display:flex;justify-content:space-between;align-items:center;margin-top:12px;padding-top:12px;border-top:1px solid #F5E6D3}
.modele-prix{font-weight:800;color:#C1622F;font-size:1rem}
.modele-duree{color:#9a7c5c;font-size:0.78rem}
.diff-badge{padding:3px 10px;border-radius:8px;font-size:0.72rem;font-weight:600}
.diff-facile{background:#E8F5E9;color:#2E7D32}
.diff-moyen{background:#FFF8E1;color:#F57F17}
.diff-difficile{background:#FCE4E4;color:#C62828}
.btn-rdv-inspi{background:linear-gradient(135deg,#C9A84C,#b8942e);color:#3E1F0D;border:none;border-radius:10px;padding:9px 15px;font-weight:700;font-size:0.82rem;width:100%;text-decoration:none;display:block;text-align:center;transition:all 0.3s;margin-top:12px}
.btn-rdv-inspi:hover{background:linear-gradient(135deg,#C1622F,#a0491f);color:#fff}

/* EMPTY */
.empty-inspi{text-align:center;padding:60px 20px;background:#fff;border-radius:20px;border:1px solid #F5E6D3}
</style>

<div class="inspi-page">
<div class="container">

    <!-- HERO -->
    <div class="page-hero">
        <h1>💇‍♀️ Inspirations Coiffures</h1>
        <div class="gold-line"></div>
        <p>Trouvez le style parfait pour vos cheveux parmi nos modeles tendance</p>
    </div>

    <!-- TENDANCES (si pas de filtre actif) -->
    <?php if(!$filtre_type && !$filtre_genre && !$filtre_diff && count($tendances) > 0): ?>
    <div class="tendances-section">
        <h2 style="font-family:'Playfair Display',serif;color:#3E1F0D;font-size:1.6rem;font-weight:700;margin-bottom:20px">
            🔥 Tendances du moment
        </h2>
        <div class="row g-3">
            <?php foreach($tendances as $i => $t): ?>
            <div class="col-md-<?= $i===0?'6':'3' ?>">
                <div class="tendance-card" style="height:<?= $i===0?'400px':'190px' ?>">
                    <?php if(!empty($t['photo'])): ?>
                        <img src="<?= htmlspecialchars($t['photo']) ?>" alt="<?= htmlspecialchars($t['nom']) ?>">
                    <?php else: ?>
                        <div class="t-img-ph" style="height:100%">
                            <?php $icons=['Bouclés'=>'🌀','Crépus'=>'✨','Lisses'=>'💫','Ondulés'=>'🌊']; echo $icons[$t['type_cheveux']]??'💇'; ?>
                        </div>
                    <?php endif; ?>
                    <span class="tendance-badge">🔥 Tendance</span>
                    <div class="tendance-overlay">
                        <h5 style="color:#fff;font-family:'Playfair Display',serif;font-weight:700;margin-bottom:5px"><?= htmlspecialchars($t['nom']) ?></h5>
                        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                            <span style="background:rgba(201,168,76,0.3);color:#C9A84C;padding:2px 10px;border-radius:8px;font-size:0.72rem;font-weight:600"><?= htmlspecialchars($t['type_cheveux']) ?></span>
                            <span style="color:rgba(255,255,255,0.7);font-size:0.78rem">A partir de <?= number_format($t['prix_estimation'],2) ?>€</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- FILTRES -->
    <div class="filter-bar">
        <span style="font-weight:700;color:#3E1F0D;font-size:0.88rem;white-space:nowrap">Filtrer :</span>

        <!-- Type cheveux -->
        <a href="coiffures.php" class="filter-btn <?= !$filtre_type?'active':'' ?>">Tous</a>
        <?php foreach(['Bouclés'=>'🌀','Crépus'=>'✨','Lisses'=>'💫','Ondulés'=>'🌊'] as $type=>$icon): ?>
        <a href="?type=<?= urlencode($type) ?><?= $filtre_genre?'&genre='.urlencode($filtre_genre):'' ?><?= $filtre_diff?'&diff='.urlencode($filtre_diff):'' ?>"
           class="filter-btn <?= $filtre_type===$type?'active':'' ?>"><?= $icon.' '.$type ?></a>
        <?php endforeach; ?>

        <div class="filter-sep"></div>

        <!-- Genre -->
        <?php foreach(['Femme'=>'👩','Homme'=>'👨','Enfant'=>'🧒','Mixte'=>'🌍'] as $genre=>$icon): ?>
        <a href="?<?= $filtre_type?'type='.urlencode($filtre_type).'&':'' ?>genre=<?= urlencode($genre) ?><?= $filtre_diff?'&diff='.urlencode($filtre_diff):'' ?>"
           class="filter-btn <?= $filtre_genre===$genre?'active':'' ?>"><?= $icon.' '.$genre ?></a>
        <?php endforeach; ?>

        <div class="filter-sep"></div>

        <!-- Difficulte -->
        <?php foreach(['Facile'=>'🟢','Moyen'=>'🟡','Difficile'=>'🔴'] as $diff=>$icon): ?>
        <a href="?<?= $filtre_type?'type='.urlencode($filtre_type).'&':'' ?><?= $filtre_genre?'genre='.urlencode($filtre_genre).'&':'' ?>diff=<?= urlencode($diff) ?>"
           class="filter-btn <?= $filtre_diff===$diff?'active':'' ?>"><?= $icon.' '.$diff ?></a>
        <?php endforeach; ?>
    </div>

    <!-- COMPTEUR -->
    <div style="margin-bottom:20px">
        <span style="color:#6B3A2A;font-size:0.88rem;font-weight:600">
            <?= count($modeles) ?> coiffure<?= count($modeles)>1?'s':'' ?> trouvee<?= count($modeles)>1?'s':'' ?>
            <?php if($filtre_type): ?> · <span style="color:#C1622F"><?= htmlspecialchars($filtre_type) ?></span><?php endif; ?>
            <?php if($filtre_genre): ?> · <span style="color:#C1622F"><?= htmlspecialchars($filtre_genre) ?></span><?php endif; ?>
            <?php if($filtre_diff): ?> · <span style="color:#C1622F"><?= htmlspecialchars($filtre_diff) ?></span><?php endif; ?>
        </span>
        <?php if($filtre_type||$filtre_genre||$filtre_diff): ?>
        <a href="coiffures.php" style="color:#C9A84C;font-size:0.82rem;margin-left:10px;text-decoration:none;font-weight:600">✕ Effacer les filtres</a>
        <?php endif; ?>
    </div>

    <!-- GRILLE MODELES -->
    <?php if(count($modeles) > 0): ?>
    <div class="row g-4">
        <?php foreach($modeles as $m):
            $diff_class = $m['difficulte']==='Facile'?'diff-facile':($m['difficulte']==='Difficile'?'diff-difficile':'diff-moyen');
        ?>
        <div class="col-lg-3 col-md-4 col-sm-6">
            <div class="modele-card">
                <div class="modele-img-wrap">
                    <?php if(!empty($m['photo'])): ?>
                        <img src="<?= htmlspecialchars($m['photo']) ?>" alt="<?= htmlspecialchars($m['nom']) ?>">
                    <?php else: ?>
                        <div class="modele-img-ph">
                            <?php $icons=['Bouclés'=>'🌀','Crépus'=>'✨','Lisses'=>'💫','Ondulés'=>'🌊']; echo $icons[$m['type_cheveux']]??'💇'; ?>
                        </div>
                    <?php endif; ?>
                    <?php if($m['tendance']): ?>
                        <div style="position:absolute;top:10px;right:10px;background:linear-gradient(135deg,#C9A84C,#b8942e);color:#3E1F0D;padding:3px 10px;border-radius:10px;font-size:0.7rem;font-weight:700">🔥 Tendance</div>
                    <?php endif; ?>
                </div>
                <div class="modele-body">
                    <h5 class="modele-name"><?= htmlspecialchars($m['nom']) ?></h5>
                    <?php if(!empty($m['description'])): ?>
                        <p style="color:#6B3A2A;font-size:0.82rem;line-height:1.6;margin-bottom:10px"><?= htmlspecialchars(substr($m['description'],0,90)) ?>...</p>
                    <?php endif; ?>
                    <div class="modele-tags">
                        <span class="modele-tag tag-type"><?= htmlspecialchars($m['type_cheveux']) ?></span>
                        <?php if(!empty($m['genre'])): ?><span class="modele-tag tag-genre"><?= htmlspecialchars($m['genre']) ?></span><?php endif; ?>
                        <?php if(!empty($m['difficulte'])): ?><span class="diff-badge <?= $diff_class ?>"><?= htmlspecialchars($m['difficulte']) ?></span><?php endif; ?>
                    </div>
                    <div class="modele-info">
                        <span class="modele-prix">A partir de <?= number_format($m['prix_estimation'],2) ?>€</span>
                        <span class="modele-duree">⏱ <?= htmlspecialchars($m['duree_realisation']) ?></span>
                    </div>
                    <a href="rendez-vous.php?prestation=<?= urlencode($m['nom']) ?>" class="btn-rdv-inspi">
                        📅 Reserver ce style
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php else: ?>
    <div class="empty-inspi">
        <div style="font-size:4rem;margin-bottom:15px">💇‍♀️</div>
        <h4 style="color:#3E1F0D">Aucun modele trouve</h4>
        <p style="color:#9a7c5c">Essayez d'autres filtres</p>
        <a href="coiffures.php" style="background:linear-gradient(135deg,#C9A84C,#b8942e);color:#3E1F0D;padding:12px 30px;border-radius:12px;font-weight:700;text-decoration:none;display:inline-block;margin-top:15px">Voir tous les modeles</a>
    </div>
    <?php endif; ?>

</div>
</div>
<?php include 'includes/footer.php'; ?>