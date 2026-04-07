<?php
require_once 'config/database.php';
$page_title = 'Prendre Rendez-vous - HairRoots';

// Recuperer les coiffeuses disponibles
$stmt = $pdo->query("SELECT * FROM coiffeuses WHERE disponible = 1 ORDER BY prenom");
$coiffeuses = $stmt->fetchAll();

$success = ''; $error = '';

// Pre-remplir si connecte
$user = null;
if(isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
}

// TRAITEMENT FORMULAIRE
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $coiffeuse_id    = (int)$_POST['coiffeuse_id'];
    $nom_client      = trim($_POST['nom_client']);
    $prenom_client   = trim($_POST['prenom_client']);
    $email           = trim($_POST['email']);
    $telephone       = trim($_POST['telephone']);
    $date_rdv        = $_POST['date_rdv'];
    $heure_rdv       = $_POST['heure_rdv'];
    $type_prestation = $_POST['type_prestation'];
    $type_cheveux    = $_POST['type_cheveux'];
    $message         = trim($_POST['message']);
    $user_id         = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

    if(empty($nom_client)||empty($prenom_client)||empty($email)||empty($telephone)||empty($date_rdv)||empty($heure_rdv)||empty($type_prestation)||empty($type_cheveux)) {
        $error = "Veuillez remplir tous les champs obligatoires.";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Adresse email invalide.";
    } elseif(strtotime($date_rdv) < strtotime('today')) {
        $error = "La date du rendez-vous doit etre dans le futur.";
    } else {
        // Verifier si creneau disponible
        $stmt = $pdo->prepare("SELECT id FROM appointments WHERE coiffeuse_id=? AND date_rdv=? AND heure_rdv=? AND statut != 'annule'");
        $stmt->execute([$coiffeuse_id, $date_rdv, $heure_rdv]);
        if($stmt->fetch()) {
            $error = "Ce creneau est deja pris. Veuillez choisir un autre horaire.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO appointments (user_id,coiffeuse_id,nom_client,prenom_client,email,telephone,date_rdv,heure_rdv,type_prestation,type_cheveux,message,statut) VALUES (?,?,?,?,?,?,?,?,?,?,?,'en attente')");
            if($stmt->execute([$user_id,$coiffeuse_id,$nom_client,$prenom_client,$email,$telephone,$date_rdv,$heure_rdv,$type_prestation,$type_cheveux,$message])) {
                $success = true;
            } else {
                $error = "Erreur lors de la reservation. Veuillez reessayer.";
            }
        }
    }
}

include 'includes/header.php';
?>
<style>
.rdv-page{background:linear-gradient(135deg,#FDF0E8,#FDEBD0,#F5E6D3);min-height:80vh;padding:50px 0}
.rdv-hero{text-align:center;margin-bottom:45px}
.rdv-hero h1{font-family:'Playfair Display',serif;font-size:2.8rem;font-weight:900;color:#3E1F0D}
.rdv-hero p{color:#6B3A2A;font-size:1.05rem;margin-top:10px}
.rdv-card{background:#fff;border-radius:24px;box-shadow:0 8px 40px rgba(62,31,13,0.1);border:1px solid #F5E6D3;overflow:hidden}
.rdv-card-header{background:linear-gradient(135deg,#3E1F0D,#6B3A2A);padding:22px 30px;display:flex;align-items:center;gap:12px}
.rdv-card-header h4{font-family:'Playfair Display',serif;color:#C9A84C;font-weight:700;margin:0}
.rdv-card-body{padding:30px}
.rdv-label{font-weight:600;font-size:0.85rem;color:#3E1F0D;margin-bottom:6px;display:block}
.rdv-input{border:2px solid #F5E6D3;border-radius:12px;padding:12px 15px;font-size:0.92rem;transition:all 0.3s;background:#FDFAF7;width:100%}
.rdv-input:focus{border-color:#C9A84C;box-shadow:0 0 0 3px rgba(201,168,76,0.12);outline:none;background:#fff}
.rdv-section{font-family:'Playfair Display',serif;font-size:1rem;font-weight:700;color:#3E1F0D;margin:25px 0 15px;padding-bottom:8px;border-bottom:2px solid #F5E6D3;display:flex;align-items:center;gap:8px}

/* COIFFEUSES */
.coiffeuse-radio{display:none}
.coiffeuse-card{border:2px solid #F5E6D3;border-radius:16px;padding:15px;cursor:pointer;transition:all 0.3s;background:#FDFAF7;text-align:center;display:block;margin:0;user-select:none}
.coiffeuse-card:hover{border-color:#C9A84C;transform:translateY(-3px);box-shadow:0 8px 20px rgba(201,168,76,0.15)}
.coiffeuse-radio:checked + .coiffeuse-card{border-color:#C9A84C;background:linear-gradient(135deg,#FFFDF5,#FFF8E8);box-shadow:0 8px 20px rgba(201,168,76,0.2)}
.coiffeuse-card img{width:70px;height:70px;border-radius:50%;object-fit:cover;object-position:top;border:3px solid #F5E6D3;margin-bottom:8px}
.coiffeuse-card .c-init{width:70px;height:70px;border-radius:50%;background:linear-gradient(135deg,#C9A84C,#C1622F);display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:900;color:#fff;margin:0 auto 8px;font-family:'Playfair Display',serif}
.coiffeuse-card .c-name{font-weight:700;color:#3E1F0D;font-size:0.88rem}
.coiffeuse-card .c-spec{color:#9a7c5c;font-size:0.75rem;margin-top:2px}

/* HORAIRES */
.heure-btn{border:2px solid #F5E6D3;border-radius:10px;padding:8px 14px;cursor:pointer;transition:all 0.3s;background:#FDFAF7;font-size:0.85rem;font-weight:600;color:#3E1F0D}
.heure-btn:hover{border-color:#C9A84C;background:#FFFDF5}
.heure-btn.selected{border-color:#C9A84C;background:linear-gradient(135deg,#C9A84C,#b8942e);color:#3E1F0D}
.heure-btn.taken{border-color:#fce4e4;background:#fce4e4;color:#c62828;cursor:not-allowed;opacity:0.6}

/* TYPES */
.type-btn{border:2px solid #F5E6D3;border-radius:25px;padding:8px 18px;cursor:pointer;transition:all 0.3s;background:#FDFAF7;font-size:0.85rem;font-weight:600;color:#3E1F0D;white-space:nowrap}
.type-btn:hover{border-color:#C9A84C;background:#FFFDF5}
.type-btn.selected{border-color:#C1622F;background:linear-gradient(135deg,#C1622F,#a0491f);color:#fff}

/* BTN */
.btn-rdv{background:linear-gradient(135deg,#C9A84C,#b8942e);color:#3E1F0D;border:none;border-radius:14px;padding:16px;font-size:1.05rem;font-weight:800;width:100%;transition:all 0.3s;cursor:pointer;letter-spacing:0.3px}
.btn-rdv:hover{background:linear-gradient(135deg,#C1622F,#a0491f);color:#fff;transform:translateY(-2px);box-shadow:0 10px 25px rgba(193,98,47,0.3)}

/* SUCCES */
.success-box{text-align:center;padding:60px 30px}
.success-icon{font-size:5rem;margin-bottom:20px;animation:pop 0.5s ease}
@keyframes pop{0%{transform:scale(0)}70%{transform:scale(1.15)}100%{transform:scale(1)}}

/* ALERT */
.alert-rdv{border-radius:12px;padding:14px 18px;margin-bottom:20px;font-size:0.9rem;border:none}
.alert-rdv.error{background:#fce4e4;color:#c62828;border-left:4px solid #c62828}

/* SIDEBAR INFO */
.rdv-info-card{background:#fff;border-radius:20px;box-shadow:0 4px 20px rgba(62,31,13,0.06);border:1px solid #F5E6D3;padding:25px;margin-bottom:20px}
.rdv-info-item{display:flex;gap:12px;margin-bottom:18px;align-items:flex-start}
.rdv-info-icon{width:40px;height:40px;border-radius:10px;background:#F5E6D3;display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0}
</style>

<div class="rdv-page">
<div class="container">

<div class="rdv-hero">
    <h1> Prendre Rendez-vous</h1>
    <p>Reservez votre seance avec l'une de nos coiffeuses expertes</p>
</div>

<?php if($success): ?>
<!-- CONFIRMATION -->
<div class="rdv-card" style="max-width:600px;margin:0 auto">
    <div class="rdv-card-body">
        <div class="success-box">
            <div class="success-icon"></div>
            <h2 style="font-family:'Playfair Display',serif;color:#3E1F0D;font-size:2rem">Rendez-vous confirme !</h2>
            <p style="color:#6B3A2A;font-size:1rem;margin:10px 0 25px">Votre reservation a bien ete enregistree. Nous vous contacterons pour confirmer votre creneau.</p>
            <div style="background:linear-gradient(135deg,#F5E6D3,#FDEBD0);border-radius:14px;padding:20px;margin-bottom:25px;text-align:left">
                <p style="color:#9a7c5c;font-size:0.82rem;margin:0 0 3px;font-weight:600">RECAPITULATIF</p>
                <p style="color:#3E1F0D;font-weight:700;margin:5px 0">👤 <?= htmlspecialchars($_POST['prenom_client'].' '.$_POST['nom_client']) ?></p>
                <p style="color:#6B3A2A;margin:5px 0"><?= date('d/m/Y', strtotime($_POST['date_rdv'])) ?> a <?= htmlspecialchars($_POST['heure_rdv']) ?></p>
                <p style="color:#6B3A2A;margin:5px 0"> <?= htmlspecialchars($_POST['type_prestation']) ?></p>
                <p style="color:#6B3A2A;margin:5px 0"> Cheveux <?= htmlspecialchars($_POST['type_cheveux']) ?></p>
            </div>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="rendez-vous.php" style="background:linear-gradient(135deg,#C9A84C,#b8942e);color:#3E1F0D;padding:12px 30px;border-radius:12px;font-weight:700;text-decoration:none">Nouveau RDV</a>
                <a href="index.php" style="background:#F5E6D3;color:#3E1F0D;padding:12px 30px;border-radius:12px;font-weight:700;text-decoration:none">Retour accueil</a>
            </div>
        </div>
    </div>
</div>

<?php else: ?>

<div class="row g-4">
<div class="col-lg-8">
<div class="rdv-card">
    <div class="rdv-card-header">
        <i class="bi bi-calendar-check" style="color:#C9A84C;font-size:1.3rem"></i>
        <h4>Reserver votre seance</h4>
    </div>
    <div class="rdv-card-body">

    <?php if($error): ?><div class="alert-rdv error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="POST" action="" id="rdv-form">

        <!-- CHOIX COIFFEUSE -->
        <div class="rdv-section"> Choisir votre coiffeuse</div>
        <div class="row g-3 mb-3">
            <?php foreach($coiffeuses as $c): ?>
            <div class="col-md-4 col-6">
                <input type="radio" name="coiffeuse_choice" id="c<?= $c['id'] ?>" value="<?= $c['id'] ?>" class="coiffeuse-radio"
                       <?= (isset($_POST['coiffeuse_id'])&&$_POST['coiffeuse_id']==$c['id'])||(count($coiffeuses)>0&&$c['id']==$coiffeuses[0]['id']&&!isset($_POST['coiffeuse_id']))?'checked':'' ?>
                       onchange="document.getElementById('coiffeuse_id').value=this.value">
                <label for="c<?= $c['id'] ?>" class="coiffeuse-card">
                    <?php if(!empty($c['photo'])): ?>
                        <img src="/ecommerce/<?= htmlspecialchars($c['photo']) ?>" alt="<?= htmlspecialchars($c['prenom']) ?>">
                    <?php else: ?>
                        <div class="c-init"><?= strtoupper(substr($c['prenom'],0,1).substr($c['nom'],0,1)) ?></div>
                    <?php endif; ?>
                    <div class="c-name"><?= htmlspecialchars($c['prenom'].' '.$c['nom']) ?></div>
                    <div class="c-spec"><?= htmlspecialchars($c['specialite']) ?></div>
                </label>
            </div>
            <?php endforeach; ?>
        </div>
        <input type="hidden" name="coiffeuse_id" id="coiffeuse_id" value="<?= isset($_POST['coiffeuse_id'])?htmlspecialchars($_POST['coiffeuse_id']):(count($coiffeuses)>0?$coiffeuses[0]['id']:'') ?>">

        <!-- TYPE PRESTATION -->
        <div class="rdv-section"> Type de prestation</div>
        <div class="d-flex flex-wrap gap-2 mb-3">
            <?php
            $prestations = ['Tresse','Box Braids','Wash and Go','Lissage','Coloration','Soin capillaire','Coupe','Balayage'];
            foreach($prestations as $p): $sel = isset($_POST['type_prestation'])&&$_POST['type_prestation']==$p;?>
            <button type="button" class="type-btn <?= $sel?'selected':'' ?>"
                    onclick="selectType(this, 'prestation')"><?= $p ?></button>
            <?php endforeach; ?>
        </div>
        <input type="hidden" name="type_prestation" id="type_prestation" value="<?= isset($_POST['type_prestation'])?htmlspecialchars($_POST['type_prestation']):'' ?>">

        <!-- TYPE CHEVEUX -->
        <div class="rdv-section"> Type de cheveux</div>
        <div class="d-flex flex-wrap gap-2 mb-3">
            <?php
            $types = [' Boucles',' Crepus',' Lisses',' Ondules','Mixtes'];
            foreach($types as $t): $sel = isset($_POST['type_cheveux'])&&$_POST['type_cheveux']==$t; ?>
            <button type="button" class="type-btn <?= $sel?'selected':'' ?>"
                    onclick="selectType(this, 'cheveux')"><?= $t ?></button>
            <?php endforeach; ?>
        </div>
        <input type="hidden" name="type_cheveux" id="type_cheveux" value="<?= isset($_POST['type_cheveux'])?htmlspecialchars($_POST['type_cheveux']):'' ?>">

        <!-- DATE & HEURE -->
        <div class="rdv-section">Date et heure</div>
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="rdv-label">Date *</label>
                <input type="date" class="rdv-input" name="date_rdv" id="date_rdv"
                       min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                       value="<?= isset($_POST['date_rdv'])?htmlspecialchars($_POST['date_rdv']):'' ?>"
                       onchange="loadHoraires()" required>
            </div>
            <div class="col-md-6">
                <label class="rdv-label">Heure *</label>
                <input type="hidden" name="heure_rdv" id="heure_rdv" value="<?= isset($_POST['heure_rdv'])?htmlspecialchars($_POST['heure_rdv']):'' ?>">
                <div class="d-flex flex-wrap gap-2" id="horaires-grid">
                    <?php
                    $horaires = ['09:00','09:30','10:00','10:30','11:00','11:30','14:00','14:30','15:00','15:30','16:00','16:30','17:00','17:30'];
                    foreach($horaires as $h): $sel = isset($_POST['heure_rdv'])&&$_POST['heure_rdv']==$h; ?>
                    <button type="button" class="heure-btn <?= $sel?'selected':'' ?>"
                            onclick="selectHeure(this, '<?= $h ?>')"><?= $h ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- INFOS CLIENT -->
        <div class="rdv-section">👤 Vos informations</div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="rdv-label">Prenom *</label>
                <input type="text" class="rdv-input" name="prenom_client"
                       value="<?= isset($_POST['prenom_client'])?htmlspecialchars($_POST['prenom_client']):(isset($user)?htmlspecialchars($user['first_name']):'') ?>"
                       placeholder="Votre prenom" required>
            </div>
            <div class="col-md-6">
                <label class="rdv-label">Nom *</label>
                <input type="text" class="rdv-input" name="nom_client"
                       value="<?= isset($_POST['nom_client'])?htmlspecialchars($_POST['nom_client']):(isset($user)?htmlspecialchars($user['last_name']):'') ?>"
                       placeholder="Votre nom" required>
            </div>
            <div class="col-md-6">
                <label class="rdv-label">Email *</label>
                <input type="email" class="rdv-input" name="email"
                       value="<?= isset($_POST['email'])?htmlspecialchars($_POST['email']):(isset($user)?htmlspecialchars($user['email']):'') ?>"
                       placeholder="votre@email.com" required>
            </div>
            <div class="col-md-6">
                <label class="rdv-label">Telephone *</label>
                <input type="tel" class="rdv-input" name="telephone"
                       value="<?= isset($_POST['telephone'])?htmlspecialchars($_POST['telephone']):(isset($user)?htmlspecialchars($user['phone']??''):'') ?>"
                       placeholder="+33 6 00 00 00 00" required>
            </div>
            <div class="col-12">
                <label class="rdv-label">Message (optionnel)</label>
                <textarea class="rdv-input" name="message" rows="3"
                          placeholder="Precisions sur votre coiffure, allergies, questions..."><?= isset($_POST['message'])?htmlspecialchars($_POST['message']):'' ?></textarea>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn-rdv">Confirmer mon rendez-vous</button>
        </div>

    </form>
    </div>
</div>
</div>

<!-- SIDEBAR -->
<div class="col-lg-4">
    <div class="rdv-info-card">
        <h5 style="font-family:'Playfair Display',serif;color:#3E1F0D;margin-bottom:20px">ℹInformations utiles</h5>
        <div class="rdv-info-item">
            <div class="rdv-info-icon"></div>
            <div><strong style="color:#3E1F0D;font-size:0.88rem">Horaires</strong><br><span style="color:#6B3A2A;font-size:0.82rem">Lun-Sam : 9h00 - 18h00<br>Dimanche : Ferme</span></div>
        </div>
        <div class="rdv-info-item">
            <div class="rdv-info-icon"></div>
            <div><strong style="color:#3E1F0D;font-size:0.88rem">Adresse</strong><br><span style="color:#6B3A2A;font-size:0.82rem">123 Rue des Cheveux<br>75001 Paris</span></div>
        </div>
        <div class="rdv-info-item">
            <div class="rdv-info-icon"></div>
            <div><strong style="color:#3E1F0D;font-size:0.88rem">Contact</strong><br><span style="color:#6B3A2A;font-size:0.82rem">01 23 45 67 89<br>contact@hairroots.fr</span></div>
        </div>
        <div class="rdv-info-item">
            <div class="rdv-info-icon"></div>
            <div><strong style="color:#3E1F0D;font-size:0.88rem">Annulation</strong><br><span style="color:#6B3A2A;font-size:0.82rem">Annulation gratuite jusqu'a 24h avant le RDV</span></div>
        </div>
    </div>

    <!-- COIFFEUSES -->
    <?php if(count($coiffeuses)>0): ?>
    <div class="rdv-info-card">
        <h5 style="font-family:'Playfair Display',serif;color:#3E1F0D;margin-bottom:15px">Nos expertes</h5>
        <?php foreach($coiffeuses as $c): ?>
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;padding-bottom:12px;border-bottom:1px solid #F5E6D3">
            <?php if(!empty($c['photo'])): ?>
                <img src="/ecommerce/<?= htmlspecialchars($c['photo']) ?>" style="width:45px;height:45px;border-radius:50%;object-fit:cover;object-position:top;border:2px solid #F5E6D3">
            <?php else: ?>
                <div style="width:45px;height:45px;border-radius:50%;background:linear-gradient(135deg,#C9A84C,#C1622F);display:flex;align-items:center;justify-content:center;font-weight:900;color:#fff;font-size:0.9rem;flex-shrink:0"><?= strtoupper(substr($c['prenom'],0,1).substr($c['nom'],0,1)) ?></div>
            <?php endif; ?>
            <div>
                <div style="font-weight:700;color:#3E1F0D;font-size:0.88rem"><?= htmlspecialchars($c['prenom'].' '.$c['nom']) ?></div>
                <div style="color:#9a7c5c;font-size:0.75rem"><?= htmlspecialchars($c['specialite']) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
</div>
<?php endif; ?>

</div></div>

<script>
// Selection coiffeuse via radio buttons natifs
function selectType(el, group) {
    el.closest('.d-flex').querySelectorAll('.type-btn').forEach(b=>b.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('type_'+group).value = el.textContent.trim();
}
function selectHeure(el, heure) {
    if(el.classList.contains('taken')) return;
    document.querySelectorAll('.heure-btn').forEach(b=>b.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('heure_rdv').value = heure;
}
function loadHoraires() {
    // Grise le dimanche
    const date = new Date(document.getElementById('date_rdv').value);
    const day = date.getDay();
    if(day === 0) {
        document.querySelectorAll('.heure-btn').forEach(b=>{b.classList.add('taken');b.classList.remove('selected');});
        document.getElementById('heure_rdv').value = '';
        alert('Nous sommes fermes le dimanche. Veuillez choisir une autre date.');
    } else {
        document.querySelectorAll('.heure-btn').forEach(b=>b.classList.remove('taken'));
    }
}
// La premiere coiffeuse est selectionnee par defaut via l'attribut checked
</script>
<?php include 'includes/footer.php'; ?>