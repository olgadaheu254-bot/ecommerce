<?php
require_once '../config/database.php';
$page_title = 'Mon Profil - HairRoots';

if(!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$user_id = $_SESSION['user_id'];
$success = ''; $error = '';

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]); $user = $stmt->fetch();

// UPLOAD PHOTO
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_photo'])) {
    if(isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        if(!in_array($_FILES['photo']['type'], $allowed)) { $error = "Format non autorisé (JPG, PNG, WEBP)."; }
        elseif($_FILES['photo']['size'] > 5*1024*1024) { $error = "Photo trop volumineuse (max 5MB)."; }
        else {
            if(!empty($user['photo']) && file_exists('../'.$user['photo'])) unlink('../'.$user['photo']);
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $filename = 'avatar_'.$user_id.'_'.time().'.'.$ext;
            $dest = '../assets/uploads/avatars/'.$filename;
            if(move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
                $photo_path = 'assets/uploads/avatars/'.$filename;
                $pdo->prepare("UPDATE users SET photo=? WHERE id=?")->execute([$photo_path, $user_id]);
                $_SESSION['photo'] = $photo_path;
                $success = "Photo de profil mise a jour !";
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?"); $stmt->execute([$user_id]); $user = $stmt->fetch();
            } else { $error = "Erreur upload. Verifiez les permissions du dossier avatars."; }
        }
    } else { $error = "Veuillez selectionner une photo."; }
}

// SUPPRIMER PHOTO
if(isset($_GET['delete_photo'])) {
    if(!empty($user['photo']) && file_exists('../'.$user['photo'])) unlink('../'.$user['photo']);
    $pdo->prepare("UPDATE users SET photo=NULL WHERE id=?")->execute([$user_id]);
    $success = "Photo supprimee.";
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?"); $stmt->execute([$user_id]); $user = $stmt->fetch();
}

// MISE A JOUR PROFIL
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']); $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']); $phone = trim($_POST['phone']);
    if(empty($first_name)||empty($last_name)||empty($email)) { $error = "Prenom, nom et email sont obligatoires."; }
    elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) { $error = "Email invalide."; }
    else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email=? AND id!=?"); $stmt->execute([$email,$user_id]);
        if($stmt->fetch()) { $error = "Cet email est deja utilise."; }
        else {
            $stmt = $pdo->prepare("UPDATE users SET first_name=?,last_name=?,email=?,phone=? WHERE id=?");
            if($stmt->execute([$first_name,$last_name,$email,$phone,$user_id])) {
                $success = "Profil mis a jour !"; $_SESSION['first_name']=$first_name; $_SESSION['last_name']=$last_name;
                $stmt=$pdo->prepare("SELECT * FROM users WHERE id=?"); $stmt->execute([$user_id]); $user=$stmt->fetch();
            } else { $error = "Erreur mise a jour."; }
        }
    }
}

// CHANGEMENT MOT DE PASSE
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current=$_POST['current_password']; $new=$_POST['new_password']; $confirm=$_POST['confirm_password'];
    if(empty($current)||empty($new)||empty($confirm)) { $error = "Tous les champs sont obligatoires."; }
    elseif(!password_verify($current,$user['password'])) { $error = "Mot de passe actuel incorrect."; }
    elseif(strlen($new)<6) { $error = "Minimum 6 caracteres."; }
    elseif($new!==$confirm) { $error = "Les mots de passe ne correspondent pas."; }
    else {
        if($pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($new,PASSWORD_DEFAULT),$user_id]))
            { $success = "Mot de passe change !"; }
        else { $error = "Erreur."; }
    }
}

// AJOUTER ADRESSE
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_address'])) {
    $nom=trim($_POST['addr_nom']); $prenom=trim($_POST['addr_prenom']);
    $adresse=trim($_POST['addr_adresse']); $complement=trim($_POST['addr_complement']);
    $cp=trim($_POST['addr_code_postal']); $ville=trim($_POST['addr_ville']);
    $pays=trim($_POST['addr_pays']); $tel=trim($_POST['addr_telephone']);
    $principale=isset($_POST['addr_principale'])?1:0;
    if(empty($nom)||empty($prenom)||empty($adresse)||empty($cp)||empty($ville)) { $error="Champs obligatoires manquants."; }
    else {
        if($principale) $pdo->prepare("UPDATE user_addresses SET est_principale=0 WHERE user_id=?")->execute([$user_id]);
        $stmt=$pdo->prepare("INSERT INTO user_addresses (user_id,nom,prenom,adresse,complement,code_postal,ville,pays,telephone,est_principale) VALUES(?,?,?,?,?,?,?,?,?,?)");
        if($stmt->execute([$user_id,$nom,$prenom,$adresse,$complement,$cp,$ville,$pays,$tel,$principale])) { $success="Adresse ajoutee !"; }
        else { $error="Erreur ajout adresse."; }
    }
}

// SUPPRIMER ADRESSE
if(isset($_GET['delete_address'])) {
    $pdo->prepare("DELETE FROM user_addresses WHERE id=? AND user_id=?")->execute([(int)$_GET['delete_address'],$user_id]);
    $success = "Adresse supprimee.";
}

// ADRESSE PRINCIPALE
if(isset($_GET['set_principale'])) {
    $pdo->prepare("UPDATE user_addresses SET est_principale=0 WHERE user_id=?")->execute([$user_id]);
    $pdo->prepare("UPDATE user_addresses SET est_principale=1 WHERE id=? AND user_id=?")->execute([(int)$_GET['set_principale'],$user_id]);
    $success = "Adresse principale mise a jour !";
}

$stmt=$pdo->prepare("SELECT * FROM user_addresses WHERE user_id=? ORDER BY est_principale DESC,created_at DESC");
$stmt->execute([$user_id]); $addresses=$stmt->fetchAll();

$stmt=$pdo->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]); $recent_orders=$stmt->fetchAll();

function getInitiales($f,$l){return strtoupper(substr($f,0,1).substr($l,0,1));}
function getAvatarColor($n){$c=['#C1622F','#C9A84C','#6B3A2A','#3E1F0D','#8B4513','#A0522D'];return $c[ord($n[0])%count($c)];}

include '../includes/header.php';
?>
<style>
.profile-page{background:#FDF8F2;min-height:80vh;padding:40px 0}
.avatar-circle{width:100px;height:100px;border-radius:50%;object-fit:cover;border:4px solid rgba(201,168,76,0.5);display:block;margin:0 auto}
.avatar-rounded{width:130px;height:130px;border-radius:20px;object-fit:cover;border:4px solid rgba(201,168,76,0.4);display:block;margin:0 auto}
.avatar-init-circle{width:100px;height:100px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:900;color:#fff;margin:0 auto;font-family:'Playfair Display',serif}
.avatar-init-rounded{width:130px;height:130px;border-radius:20px;display:flex;align-items:center;justify-content:center;font-size:2.5rem;font-weight:900;color:#fff;margin:0 auto;font-family:'Playfair Display',serif}
.profile-sidebar{background:linear-gradient(135deg,#3E1F0D,#6B3A2A);border-radius:20px;padding:35px 25px;text-align:center;color:#fff;position:sticky;top:80px}
.profile-name{font-family:'Playfair Display',serif;font-size:1.1rem;color:#C9A84C;font-weight:700;margin-top:12px}
.profile-username{color:#c4a882;font-size:0.82rem}
.profile-since{color:rgba(255,255,255,0.5);font-size:0.75rem;margin-top:3px}
.pnav{display:flex;align-items:center;gap:10px;padding:11px 15px;border-radius:12px;color:#e8d5b7;text-decoration:none;font-size:0.88rem;font-weight:500;margin-bottom:4px;transition:all 0.3s;cursor:pointer;border:none;background:transparent;width:100%;text-align:left}
.pnav:hover,.pnav.active{background:rgba(201,168,76,0.15);color:#C9A84C}
.pcard{background:#fff;border-radius:20px;box-shadow:0 4px 20px rgba(62,31,13,0.06);border:1px solid #F5E6D3;margin-bottom:25px;overflow:hidden}
.pcard-header{background:linear-gradient(135deg,#F5E6D3,#FDEBD0);padding:18px 25px;border-bottom:1px solid #F0D9C0;display:flex;align-items:center;gap:10px}
.pcard-header h5{font-family:'Playfair Display',serif;color:#3E1F0D;font-weight:700;margin:0;font-size:1.1rem}
.pcard-body{padding:25px}
.pinput{border:2px solid #F5E6D3;border-radius:10px;padding:10px 15px;font-size:0.9rem;transition:all 0.3s;background:#FDFAF7;width:100%}
.pinput:focus{border-color:#C9A84C;box-shadow:0 0 0 3px rgba(201,168,76,0.12);outline:none;background:#fff}
.pinput:disabled{background:#F5E6D3;color:#9a7c5c;cursor:not-allowed}
.plabel{font-weight:600;font-size:0.82rem;color:#3E1F0D;margin-bottom:5px;display:block}
.pbtn{background:linear-gradient(135deg,#C9A84C,#b8942e);color:#3E1F0D;border:none;border-radius:10px;padding:11px 25px;font-weight:700;font-size:0.9rem;transition:all 0.3s;cursor:pointer}
.pbtn:hover{background:linear-gradient(135deg,#C1622F,#a0491f);color:#fff;transform:translateY(-2px)}
.pbtn-danger{background:#fce4e4;color:#c62828;border:none;border-radius:8px;padding:6px 14px;font-size:0.8rem;font-weight:600;transition:all 0.3s;cursor:pointer;text-decoration:none;display:inline-block}
.pbtn-danger:hover{background:#c62828;color:#fff}
.pbtn-success{background:#e8f5e9;color:#2e7d32;border:none;border-radius:8px;padding:6px 14px;font-size:0.8rem;font-weight:600;transition:all 0.3s;text-decoration:none;display:inline-block}
.pbtn-success:hover{background:#2e7d32;color:#fff}
.addr-card{border:2px solid #F5E6D3;border-radius:14px;padding:18px;margin-bottom:15px;transition:all 0.3s;background:#FDFAF7}
.addr-card:hover{border-color:#C9A84C;box-shadow:0 5px 15px rgba(201,168,76,0.1)}
.addr-card.principale{border-color:#C9A84C;background:linear-gradient(135deg,#FFFDF5,#FFF8E8)}
.badge-p{background:linear-gradient(135deg,#C9A84C,#b8942e);color:#3E1F0D;padding:3px 12px;border-radius:12px;font-size:0.75rem;font-weight:700}
.ptab{display:none}.ptab.active{display:block}
.order-row{background:#FDFAF7;border-radius:10px;padding:15px;margin-bottom:10px;border:1px solid #F5E6D3;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px}
.alert-hr{border-radius:12px;padding:12px 18px;margin-bottom:20px;font-size:0.9rem;border:none}
.alert-hr.success{background:#e8f5e9;color:#2e7d32;border-left:4px solid #2e7d32}
.alert-hr.error{background:#fce4e4;color:#c62828;border-left:4px solid #c62828}
.upload-zone{border:2px dashed #C9A84C;border-radius:16px;padding:25px;text-align:center;cursor:pointer;transition:all 0.3s;background:#FDFAF7}
.upload-zone:hover{background:#FFF8E8;border-color:#C1622F}
</style>

<div class="profile-page"><div class="container">
<?php if($success): ?><div class="alert-hr success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if($error):   ?><div class="alert-hr error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="row g-4">
<div class="col-lg-3">
<div class="profile-sidebar">
<?php if(!empty($user['photo'])): ?>
<img src="/ecommerce/<?= htmlspecialchars($user['photo']) ?>" alt="Photo" class="avatar-circle">
<?php else: ?>
<div class="avatar-init-circle" style="background:<?= getAvatarColor($user['first_name']) ?>"><?= getInitiales($user['first_name'],$user['last_name']) ?></div>
<?php endif; ?>
<div class="profile-name"><?= htmlspecialchars($user['first_name'].' '.$user['last_name']) ?></div>
<div class="profile-username">@<?= htmlspecialchars($user['username']) ?></div>
<div class="profile-since">Membre depuis <?= date('d/m/Y',strtotime($user['created_at'])) ?></div>
<div class="mt-3">
<button class="pnav active" onclick="showTab('t-photo',this)"><i class="bi bi-camera"></i> Ma photo</button>
<button class="pnav" onclick="showTab('t-infos',this)"><i class="bi bi-person"></i> Mes informations</button>
<button class="pnav" onclick="showTab('t-adresses',this)"><i class="bi bi-geo-alt"></i> Mes adresses <span class="ms-auto badge" style="background:rgba(201,168,76,0.2);color:#C9A84C"><?= count($addresses) ?></span></button>
<button class="pnav" onclick="showTab('t-password',this)"><i class="bi bi-shield-lock"></i> Mot de passe</button>
<button class="pnav" onclick="showTab('t-commandes',this)"><i class="bi bi-bag-check"></i> Commandes <span class="ms-auto badge" style="background:rgba(201,168,76,0.2);color:#C9A84C"><?= count($recent_orders) ?></span></button>
<a href="logout.php" class="pnav mt-3" style="color:#ff6b6b"><i class="bi bi-box-arrow-right"></i> Deconnexion</a>
</div></div></div>

<div class="col-lg-9">

<!-- PHOTO -->
<div id="t-photo" class="ptab active">
<div class="pcard"><div class="pcard-header"><i class="bi bi-camera" style="color:#C9A84C;font-size:1.2rem"></i><h5>Ma photo de profil</h5></div>
<div class="pcard-body"><div class="row align-items-center g-4">
<div class="col-md-4 text-center">
<p class="plabel text-center mb-3">Photo actuelle</p>
<?php if(!empty($user['photo'])): ?>
<img src="/ecommerce/<?= htmlspecialchars($user['photo']) ?>" alt="Photo" class="avatar-rounded">
<a href="?delete_photo=1" class="pbtn-danger d-inline-block mt-3" onclick="return confirm('Supprimer votre photo ?')">Supprimer</a>
<?php else: ?>
<div class="avatar-init-rounded" style="background:<?= getAvatarColor($user['first_name']) ?>"><?= getInitiales($user['first_name'],$user['last_name']) ?></div>
<p class="small text-muted mt-2">Pas encore de photo</p>
<?php endif; ?>
</div>
<div class="col-md-8">
<form method="POST" action="" enctype="multipart/form-data">
<div class="upload-zone" onclick="document.getElementById('photo-input').click()">
<img id="photo-preview" src="" alt="Apercu" style="width:120px;height:120px;border-radius:15px;object-fit:cover;border:3px solid #C9A84C;margin:0 auto 10px;display:none">
<div id="upload-ph"><div style="font-size:3rem;margin-bottom:10px">📸</div>
<p style="color:#3E1F0D;font-weight:600;margin:0">Cliquez pour choisir votre photo</p>
<p class="small text-muted mt-1">ou glissez-deposez ici</p>
<p class="small" style="color:#C9A84C">JPG, PNG, WEBP - Max 5MB</p></div>
<input type="file" id="photo-input" name="photo" accept="image/*" style="display:none" onchange="previewPhoto(this)">
</div>
<div id="photo-actions" style="display:none;margin-top:15px;text-align:center">
<button type="submit" name="update_photo" class="pbtn">Enregistrer cette photo</button>
<button type="button" class="pbtn-danger ms-2" onclick="resetPhoto()">Annuler</button>
</div>
<div class="mt-3 p-3 rounded-3" style="background:#F5E6D3">
<p class="small mb-1" style="color:#3E1F0D;font-weight:600">Conseils :</p>
<ul class="small mb-0" style="color:#6B3A2A"><li>Photo bien eclairee, visage centre</li><li>Fond neutre recommande</li><li>Format carre ideal 400x400px</li></ul>
</div>
</form></div></div></div></div></div>

<!-- INFOS -->
<div id="t-infos" class="ptab">
<div class="pcard"><div class="pcard-header"><i class="bi bi-person-badge" style="color:#C9A84C;font-size:1.2rem"></i><h5>Mes informations personnelles</h5></div>
<div class="pcard-body"><form method="POST" action=""><div class="row g-3">
<div class="col-md-6"><label class="plabel">Prenom *</label><input type="text" class="pinput" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required></div>
<div class="col-md-6"><label class="plabel">Nom *</label><input type="text" class="pinput" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required></div>
<div class="col-md-6"><label class="plabel">Nom d'utilisateur</label><input type="text" class="pinput" value="<?= htmlspecialchars($user['username']) ?>" disabled><small style="color:#9a7c5c;font-size:0.78rem">Non modifiable</small></div>
<div class="col-md-6"><label class="plabel">Email *</label><input type="email" class="pinput" name="email" value="<?= htmlspecialchars($user['email']) ?>" required></div>
<div class="col-md-6"><label class="plabel">Telephone</label><input type="tel" class="pinput" name="phone" value="<?= htmlspecialchars($user['phone']??'') ?>" placeholder="+33 6 00 00 00 00"></div>
</div><div class="mt-4"><button type="submit" name="update_profile" class="pbtn">Mettre a jour mon profil</button></div>
</form></div></div></div>

<!-- ADRESSES -->
<div id="t-adresses" class="ptab">
<div class="pcard"><div class="pcard-header"><i class="bi bi-geo-alt" style="color:#C9A84C;font-size:1.2rem"></i><h5>Mes adresses de livraison</h5>
<button class="pbtn ms-auto" style="padding:7px 16px;font-size:0.82rem" onclick="document.getElementById('addr-form').style.display='block';this.style.display='none'">+ Nouvelle adresse</button>
</div>
<div class="pcard-body">
<div id="addr-form" style="display:none;background:#F5E6D3;border-radius:14px;padding:20px;margin-bottom:20px">
<h6 style="font-family:'Playfair Display',serif;color:#3E1F0D;margin-bottom:15px">Nouvelle adresse</h6>
<form method="POST" action=""><div class="row g-3">
<div class="col-md-6"><label class="plabel">Prenom *</label><input type="text" class="pinput" name="addr_prenom" required></div>
<div class="col-md-6"><label class="plabel">Nom *</label><input type="text" class="pinput" name="addr_nom" required></div>
<div class="col-12"><label class="plabel">Adresse *</label><input type="text" class="pinput" name="addr_adresse" placeholder="Numero et nom de rue" required></div>
<div class="col-12"><label class="plabel">Complement</label><input type="text" class="pinput" name="addr_complement" placeholder="Appartement, batiment..."></div>
<div class="col-md-4"><label class="plabel">Code postal *</label><input type="text" class="pinput" name="addr_code_postal" required></div>
<div class="col-md-4"><label class="plabel">Ville *</label><input type="text" class="pinput" name="addr_ville" required></div>
<div class="col-md-4"><label class="plabel">Pays</label><input type="text" class="pinput" name="addr_pays" value="France"></div>
<div class="col-md-6"><label class="plabel">Telephone</label><input type="tel" class="pinput" name="addr_telephone"></div>
<div class="col-md-6 d-flex align-items-end"><div class="form-check">
<input class="form-check-input" type="checkbox" name="addr_principale" id="ap" style="border-color:#C9A84C">
<label class="form-check-label" for="ap" style="color:#3E1F0D;font-weight:600;font-size:0.88rem">Adresse principale</label>
</div></div>
</div>
<div class="d-flex gap-2 mt-4">
<button type="submit" name="add_address" class="pbtn">Ajouter</button>
<button type="button" class="pbtn-danger" onclick="document.getElementById('addr-form').style.display='none'">Annuler</button>
</div></form></div>

<?php if(count($addresses)>0): foreach($addresses as $a): ?>
<div class="addr-card <?= $a['est_principale']?'principale':'' ?>">
<div class="d-flex align-items-center gap-2 mb-2">
<strong style="color:#3E1F0D"><?= htmlspecialchars($a['prenom'].' '.$a['nom']) ?></strong>
<?php if($a['est_principale']): ?><span class="badge-p">Principale</span><?php endif; ?>
</div>
<div style="color:#6B3A2A;font-size:0.88rem;line-height:1.7">
<?= htmlspecialchars($a['adresse']) ?><br>
<?php if(!empty($a['complement'])): ?><?= htmlspecialchars($a['complement']) ?><br><?php endif; ?>
<?= htmlspecialchars($a['code_postal'].' '.$a['ville'].', '.$a['pays']) ?>
<?php if(!empty($a['telephone'])): ?><br><?= htmlspecialchars($a['telephone']) ?><?php endif; ?>
</div>
<div class="d-flex gap-2 mt-3">
<?php if(!$a['est_principale']): ?><a href="?set_principale=<?= $a['id'] ?>" class="pbtn-success">Definir principale</a><?php endif; ?>
<a href="?delete_address=<?= $a['id'] ?>" class="pbtn-danger" onclick="return confirm('Supprimer cette adresse ?')">Supprimer</a>
</div></div>
<?php endforeach; else: ?>
<div class="text-center py-4"><div style="font-size:3rem"></div><p style="color:#9a7c5c;margin-top:10px">Pas encore d'adresse.</p></div>
<?php endif; ?>
</div></div></div>

<!-- MOT DE PASSE -->
<div id="t-password" class="ptab">
<div class="pcard"><div class="pcard-header"><i class="bi bi-shield-lock" style="color:#C9A84C;font-size:1.2rem"></i><h5>Changer mon mot de passe</h5></div>
<div class="pcard-body"><form method="POST" action=""><div class="row g-3">
<div class="col-12"><label class="plabel">Mot de passe actuel</label><input type="password" class="pinput" name="current_password" placeholder="Votre mot de passe actuel"></div>
<div class="col-md-6"><label class="plabel">Nouveau mot de passe</label><input type="password" class="pinput" name="new_password" placeholder="Min. 6 caracteres"></div>
<div class="col-md-6"><label class="plabel">Confirmer</label><input type="password" class="pinput" name="confirm_password" placeholder="Repetez le mot de passe"></div>
</div><div class="mt-4"><button type="submit" name="change_password" class="pbtn">Changer mon mot de passe</button></div>
</form></div></div></div>

<!-- COMMANDES -->
<div id="t-commandes" class="ptab">
<div class="pcard"><div class="pcard-header"><i class="bi bi-bag-check" style="color:#C9A84C;font-size:1.2rem"></i><h5>Mes commandes recentes</h5></div>
<div class="pcard-body">
<?php if(count($recent_orders)>0):
$sl=['pending'=>['En attente','#FFF8E1','#F57F17'],'processing'=>['En traitement','#E3F2FD','#1565C0'],'shipped'=>['Expediee','#F3E5F5','#6A1B9A'],'delivered'=>['Livree','#E8F5E9','#2E7D32'],'cancelled'=>['Annulee','#FCE4E4','#C62828']];
foreach($recent_orders as $o): $s=$sl[$o['status']]??['?','#F5E6D3','#6B3A2A']; ?>
<div class="order-row">
<div><div style="font-weight:700;color:#3E1F0D;font-size:0.9rem"><?= htmlspecialchars($o['order_number']) ?></div><div style="color:#9a7c5c;font-size:0.82rem"><?= date('d/m/Y',strtotime($o['created_at'])) ?></div></div>
<div style="font-weight:800;color:#C1622F"><?= number_format($o['total_amount'],2) ?> EUR</div>
<span style="background:<?= $s[1] ?>;color:<?= $s[2] ?>;padding:5px 14px;border-radius:12px;font-size:0.8rem;font-weight:600"><?= $s[0] ?></span>
<a href="order-details.php?id=<?= $o['id'] ?>" style="background:#F5E6D3;color:#3E1F0D;padding:7px 16px;border-radius:10px;font-size:0.82rem;font-weight:600;text-decoration:none">Voir</a>
</div>
<?php endforeach; else: ?>
<div class="text-center py-5"><div style="font-size:3.5rem"></div><h6 style="color:#3E1F0D;margin-top:15px">Pas encore de commandes</h6>
<a href="/ecommerce/products/index.php" class="pbtn" style="display:inline-block;margin-top:10px;text-decoration:none">Voir les produits</a></div>
<?php endif; ?>
</div></div></div>

</div></div></div></div>

<script>
function showTab(id,btn){
    document.querySelectorAll('.ptab').forEach(t=>t.classList.remove('active'));
    document.querySelectorAll('.pnav').forEach(b=>b.classList.remove('active'));
    document.getElementById(id).classList.add('active'); btn.classList.add('active');
}
function previewPhoto(input){
    if(input.files&&input.files[0]){
        const r=new FileReader();
        r.onload=e=>{
            const p=document.getElementById('photo-preview');
            p.src=e.target.result; p.style.display='block';
            document.getElementById('upload-ph').style.display='none';
            document.getElementById('photo-actions').style.display='block';
        };
        r.readAsDataURL(input.files[0]);
    }
}
function resetPhoto(){
    document.getElementById('photo-input').value='';
    document.getElementById('photo-preview').style.display='none';
    document.getElementById('upload-ph').style.display='block';
    document.getElementById('photo-actions').style.display='none';
}
const zone=document.querySelector('.upload-zone');
if(zone){
    zone.addEventListener('dragover',e=>{e.preventDefault();zone.style.borderColor='#C1622F';});
    zone.addEventListener('dragleave',()=>{zone.style.borderColor='#C9A84C';});
    zone.addEventListener('drop',e=>{e.preventDefault();zone.style.borderColor='#C9A84C';const f=e.dataTransfer.files[0];if(f){document.getElementById('photo-input').files=e.dataTransfer.files;previewPhoto(document.getElementById('photo-input'));}});
}
</script>
<?php include '../includes/footer.php'; ?>