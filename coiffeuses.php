<?php
require_once 'config/database.php';
$page_title = 'Nos Coiffeuses - HairRoots';

$stmt = $pdo->query("SELECT * FROM coiffeuses WHERE disponible = 1 ORDER BY prenom");
$coiffeuses = $stmt->fetchAll();

include 'includes/header.php';
?>
<style>
.coiffeuses-page{background:linear-gradient(135deg,#FDF0E8,#FDEBD0,#F5E6D3);min-height:80vh;padding:50px 0}
.page-hero{text-align:center;margin-bottom:50px}
.page-hero h1{font-family:'Playfair Display',serif;font-size:2.8rem;font-weight:900;color:#3E1F0D}
.page-hero p{color:#6B3A2A;font-size:1.05rem;margin-top:10px;max-width:600px;margin-left:auto;margin-right:auto}
.gold-line{width:60px;height:3px;background:linear-gradient(90deg,#C9A84C,#C1622F);border-radius:2px;margin:15px auto}

/* CARTE COIFFEUSE */
.coiffeuse-card{background:#fff;border-radius:24px;box-shadow:0 8px 30px rgba(62,31,13,0.08);border:1px solid #F5E6D3;overflow:hidden;transition:all 0.4s;height:100%}
.coiffeuse-card:hover{transform:translateY(-10px);box-shadow:0 20px 50px rgba(62,31,13,0.15)}
.coiffeuse-img-wrap{position:relative;height:280px;overflow:hidden}
.coiffeuse-img-wrap img{width:100%;height:100%;object-fit:cover;object-position:top;transition:transform 0.4s}
.coiffeuse-card:hover .coiffeuse-img-wrap img{transform:scale(1.05)}
.coiffeuse-img-initiales{width:100%;height:280px;background:linear-gradient(135deg,#3E1F0D,#6B3A2A);display:flex;align-items:center;justify-content:center;font-size:5rem;font-weight:900;color:#C9A84C;font-family:'Playfair Display',serif}
.coiffeuse-badge{position:absolute;top:15px;right:15px;background:linear-gradient(135deg,#C9A84C,#b8942e);color:#3E1F0D;padding:5px 14px;border-radius:20px;font-size:0.75rem;font-weight:700}
.coiffeuse-body{padding:25px}
.coiffeuse-name{font-family:'Playfair Display',serif;font-size:1.4rem;font-weight:900;color:#3E1F0D;margin-bottom:5px}
.coiffeuse-spec{color:#C1622F;font-weight:600;font-size:0.88rem;margin-bottom:12px}
.coiffeuse-bio{color:#6B3A2A;font-size:0.87rem;line-height:1.7;margin-bottom:15px}
.coiffeuse-tags{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:20px}
.coiffeuse-tag{background:#F5E6D3;color:#6B3A2A;padding:4px 12px;border-radius:10px;font-size:0.75rem;font-weight:600}
.coiffeuse-exp{display:flex;align-items:center;gap:8px;color:#9a7c5c;font-size:0.82rem;margin-bottom:20px}
.coiffeuse-exp strong{color:#3E1F0D}
.btn-rdv-coiffeuse{background:linear-gradient(135deg,#C9A84C,#b8942e);color:#3E1F0D;border:none;border-radius:12px;padding:12px 20px;font-weight:700;font-size:0.9rem;width:100%;text-decoration:none;display:block;text-align:center;transition:all 0.3s}
.btn-rdv-coiffeuse:hover{background:linear-gradient(135deg,#C1622F,#a0491f);color:#fff;transform:translateY(-2px);box-shadow:0 8px 20px rgba(193,98,47,0.3)}

/* STATS BANNER */
.stats-banner{background:linear-gradient(135deg,#3E1F0D,#6B3A2A);border-radius:20px;padding:30px;margin-bottom:50px}
.stat-item{text-align:center}
.stat-num{font-family:'Playfair Display',serif;font-size:2.2rem;font-weight:900;color:#C9A84C}
.stat-lbl{color:rgba(255,255,255,0.7);font-size:0.85rem;margin-top:3px}

/* CTA BAS */
.cta-section{background:linear-gradient(135deg,#C9A84C,#C1622F);border-radius:20px;padding:40px;text-align:center;margin-top:50px}
</style>

<div class="coiffeuses-page">
<div class="container">

    <!-- HERO -->
    <div class="page-hero">
        <h1>💼 Nos Coiffeuses Expertes</h1>
        <div class="gold-line"></div>
        <p>Des professionnelles passionnees, formees pour sublimer tous les types de cheveux</p>
    </div>

    <!-- STATS -->
    <div class="stats-banner mb-5">
        <div class="row g-4">
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <div class="stat-num"><?= count($coiffeuses) ?>+</div>
                    <div class="stat-lbl">Coiffeuses expertes</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <div class="stat-num">500+</div>
                    <div class="stat-lbl">Clients satisfaits</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <div class="stat-num">4</div>
                    <div class="stat-lbl">Types de cheveux</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <div class="stat-num">10+</div>
                    <div class="stat-lbl">Prestations disponibles</div>
                </div>
            </div>
        </div>
    </div>

    <!-- LISTE COIFFEUSES -->
    <?php if(count($coiffeuses) > 0): ?>
    <div class="row g-4">
        <?php foreach($coiffeuses as $c):
            $types = !empty($c['types_cheveux']) ? explode(',', $c['types_cheveux']) : [];
        ?>
        <div class="col-lg-4 col-md-6">
            <div class="coiffeuse-card">

                <!-- IMAGE -->
                <div class="coiffeuse-img-wrap">
                    <?php if(!empty($c['photo'])): ?>
                        <img src="/ecommerce/<?= htmlspecialchars($c['photo']) ?>"
                             alt="<?= htmlspecialchars($c['prenom'].' '.$c['nom']) ?>">
                    <?php else: ?>
                        <div class="coiffeuse-img-initiales">
                            <?= strtoupper(substr($c['prenom'],0,1).substr($c['nom'],0,1)) ?>
                        </div>
                    <?php endif; ?>
                    <?php if($c['disponible']): ?>
                        <span class="coiffeuse-badge">✅ Disponible</span>
                    <?php endif; ?>
                </div>

                <!-- INFOS -->
                <div class="coiffeuse-body">
                    <h3 class="coiffeuse-name"><?= htmlspecialchars($c['prenom'].' '.$c['nom']) ?></h3>
                    <div class="coiffeuse-spec">✂️ <?= htmlspecialchars($c['specialite']) ?></div>

                    <?php if(!empty($c['bio'])): ?>
                        <p class="coiffeuse-bio"><?= htmlspecialchars($c['bio']) ?></p>
                    <?php endif; ?>

                    <!-- TYPES DE CHEVEUX -->
                    <?php if(count($types) > 0): ?>
                    <div class="coiffeuse-tags">
                        <?php foreach($types as $t): ?>
                        <span class="coiffeuse-tag">
                            <?php
                            $icons = ['Boucles'=>'🌀','Crepus'=>'✨','Lisses'=>'💫','Ondules'=>'🌊','Tresses'=>'🎀','Colorations'=>'🎨','Enfants'=>'🧒','Soins'=>'🌿'];
                            $t = trim($t);
                            $icon = '';
                            foreach($icons as $k=>$v) { if(stripos($t,$k)!==false){$icon=$v;break;} }
                            echo $icon.' '.htmlspecialchars($t);
                            ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- EXPERIENCE -->
                    <?php if(!empty($c['experience']) || !empty($c['annees_experience'])): ?>
                    <div class="coiffeuse-exp">
                        <span>⭐</span>
                        <span><strong><?= htmlspecialchars($c['annees_experience'] ?? $c['experience'] ?? '') ?> ans</strong> d'experience</span>
                    </div>
                    <?php endif; ?>

                    <!-- BOUTON RDV -->
                    <a href="rendez-vous.php?coiffeuse=<?= $c['id'] ?>" class="btn-rdv-coiffeuse">
                        📅 Prendre RDV avec <?= htmlspecialchars($c['prenom']) ?>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php else: ?>
    <div class="text-center py-5">
        <div style="font-size:4rem;margin-bottom:20px">💼</div>
        <h4 style="color:#3E1F0D">Aucune coiffeuse disponible pour le moment</h4>
        <p style="color:#9a7c5c">Revenez bientot !</p>
    </div>
    <?php endif; ?>

    <!-- CTA SECTION -->
    <div class="cta-section">
        <h2 style="font-family:'Playfair Display',serif;color:#fff;font-size:1.8rem;font-weight:900;margin-bottom:10px">
            Prete a vous faire chouchouter ? 💆‍♀️
        </h2>
        <p style="color:rgba(255,255,255,0.85);margin-bottom:25px;font-size:1rem">
            Reservez votre seance en quelques clics
        </p>
        <a href="rendez-vous.php"
           style="background:#fff;color:#C1622F;padding:14px 40px;border-radius:14px;font-weight:800;font-size:1rem;text-decoration:none;display:inline-block;transition:all 0.3s"
           onmouseover="this.style.background='#3E1F0D';this.style.color='#C9A84C'"
           onmouseout="this.style.background='#fff';this.style.color='#C1622F'">
            📅 Prendre rendez-vous
        </a>
    </div>

</div>
</div>
<?php include 'includes/footer.php'; ?>