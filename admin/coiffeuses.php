<?php
require_once 'auth_admin.php';
require_once '../config/database.php';
$page_title = 'Gestion Coiffeuses - HairRoots Admin';

$success = ''; $error = '';

// SUPPRIMER COIFFEUSE
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE coiffeuse_id = ?");
    $stmt->execute([$id]);
    $nb_rdv = $stmt->fetchColumn();
    if($nb_rdv > 0) {
        $error = "Impossible de supprimer : cette coiffeuse a $nb_rdv rendez-vous lie(s). Desactivez-la plutot.";
    } else {
        $pdo->prepare("DELETE FROM coiffeuses WHERE id=?")->execute([$id]);
        $success = "Coiffeuse supprimee avec succes.";
    }
}

// TOGGLE DISPONIBILITE
if(isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $pdo->prepare("UPDATE coiffeuses SET disponible = NOT disponible WHERE id=?")->execute([$id]);
    header('Location: coiffeuses.php'); exit;
}

// AJOUTER / MODIFIER
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id                = isset($_POST['coiffeuse_id']) ? (int)$_POST['coiffeuse_id'] : 0;
    $prenom            = trim($_POST['prenom']);
    $nom               = trim($_POST['nom']);
    $specialite        = trim($_POST['specialite']);
    $bio               = trim($_POST['bio']);
    $annees_experience = (int)$_POST['annees_experience'];
    $disponible        = isset($_POST['disponible']) ? 1 : 0;
    $types_cheveux     = isset($_POST['types_cheveux']) ? implode(',', $_POST['types_cheveux']) : '';
    $photo             = trim($_POST['photo']);

    if(!empty($_FILES['photo_upload']['name'])) {
        $upload_dir = '../assets/images/coiffeuses/';
        if(!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $extension = strtolower(pathinfo($_FILES['photo_upload']['name'], PATHINFO_EXTENSION));
        $formats_autorises = ['jpg', 'jpeg', 'png', 'webp'];
        if(in_array($extension, $formats_autorises)) {
            $nom_fichier = 'coiffeuse_' . time() . '_' . rand(100,999) . '.' . $extension;
            if(move_uploaded_file($_FILES['photo_upload']['tmp_name'], $upload_dir . $nom_fichier)) {
                $photo = 'assets/images/coiffeuses/' . $nom_fichier;
            } else {
                $error = "Erreur lors de l'upload de la photo.";
            }
        } else {
            $error = "Format non autorise. Utilisez JPG, PNG ou WEBP.";
        }
    }

    if(empty($error)) {
        if(empty($prenom) || empty($nom) || empty($specialite)) {
            $error = "Prenom, nom et specialite sont obligatoires.";
        } else {
            if($id > 0) {
                $stmt = $pdo->prepare("UPDATE coiffeuses SET prenom=?, nom=?, specialite=?, bio=?, annees_experience=?, disponible=?, photo=?, types_cheveux=? WHERE id=?");
                if($stmt->execute([$prenom, $nom, $specialite, $bio, $annees_experience, $disponible, $photo, $types_cheveux, $id])) {
                    $success = "Coiffeuse mise a jour avec succes !";
                } else { $error = "Erreur lors de la mise a jour."; }
            } else {
                $stmt = $pdo->prepare("INSERT INTO coiffeuses (prenom, nom, specialite, bio, annees_experience, disponible, photo, types_cheveux) VALUES (?,?,?,?,?,?,?,?)");
                if($stmt->execute([$prenom, $nom, $specialite, $bio, $annees_experience, $disponible, $photo, $types_cheveux])) {
                    $success = "Coiffeuse ajoutee avec succes !";
                } else { $error = "Erreur lors de l'ajout."; }
            }
        }
    }
}

// COIFFEUSE A MODIFIER
$edit = null;
$edit_types = [];
if(isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM coiffeuses WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
    if($edit && !empty($edit['types_cheveux'])) {
        $edit_types = array_map('trim', explode(',', $edit['types_cheveux']));
    }
}

$coiffeuses = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM appointments a WHERE a.coiffeuse_id = c.id) as nb_rdv FROM coiffeuses c ORDER BY c.prenom")->fetchAll();
$types_disponibles = ['Boucles', 'Crepus', 'Lisses', 'Ondules', 'Tresses', 'Colorations', 'Enfants', 'Soins'];

include 'header_admin.php';
?>
<style>
.admin-page{background:#FDF8F2;min-height:80vh;padding:30px 0}
.dash-card{background:#fff;border-radius:18px;box-shadow:0 4px 20px rgba(62,31,13,0.06);border:1px solid #F5E6D3;overflow:hidden;margin-bottom:25px}
.dash-card-header{background:linear-gradient(135deg,#F5E6D3,#FDEBD0);padding:16px 22px;border-bottom:1px solid #F0D9C0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px}
.dash-card-header h5{font-family:'Playfair Display',serif;color:#3E1F0D;font-weight:700;margin:0;font-size:1rem}
.dash-card-body{padding:22px}
.pinput{border:2px solid #F5E6D3;border-radius:10px;padding:10px 14px;font-size:0.88rem;transition:all 0.3s;background:#FDFAF7;width:100%}
.pinput:focus{border-color:#C9A84C;box-shadow:0 0 0 3px rgba(201,168,76,0.1);outline:none;background:#fff}
.plabel{font-weight:600;font-size:0.82rem;color:#3E1F0D;margin-bottom:5px;display:block}
.pbtn{background:linear-gradient(135deg,#C9A84C,#b8942e);color:#3E1F0D;border:none;border-radius:10px;padding:10px 22px;font-weight:700;font-size:0.88rem;cursor:pointer;transition:all 0.3s;text-decoration:none;display:inline-block}
.pbtn:hover{background:linear-gradient(135deg,#C1622F,#a0491f);color:#fff}
.pbtn-sm{padding:6px 14px;font-size:0.78rem}
.pbtn-danger{background:#fce4e4;color:#c62828;border:none;border-radius:8px;padding:6px 12px;font-size:0.78rem;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block;transition:all 0.2s}
.pbtn-danger:hover{background:#c62828;color:#fff}
.pbtn-info{background:#E3F2FD;color:#1565C0;border:none;border-radius:8px;padding:6px 12px;font-size:0.78rem;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block;transition:all 0.2s}
.pbtn-info:hover{background:#1565C0;color:#fff}
.pbtn-warning{background:#FFF8E1;color:#F57F17;border:none;border-radius:8px;padding:6px 12px;font-size:0.78rem;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block;transition:all 0.2s}
.pbtn-warning:hover{background:#F57F17;color:#fff}
.pbtn-success{background:#e8f5e9;color:#2e7d32;border:none;border-radius:8px;padding:6px 12px;font-size:0.78rem;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block;transition:all 0.2s}
.pbtn-success:hover{background:#2e7d32;color:#fff}
.ptable{width:100%;border-collapse:collapse}
.ptable th{padding:11px 14px;font-weight:700;font-size:0.78rem;color:#9a7c5c;text-align:left;border-bottom:2px solid #F5E6D3;background:#FDFAF7;text-transform:uppercase}
.ptable td{padding:12px 14px;font-size:0.85rem;color:#3E1F0D;border-bottom:1px solid #F5E6D3;vertical-align:middle}
.ptable tr:last-child td{border-bottom:none}
.ptable tr:hover td{background:#FDFAF7}
.alert-hr{border-radius:12px;padding:12px 18px;margin-bottom:20px;font-size:0.9rem;border:none}
.alert-hr.success{background:#e8f5e9;color:#2e7d32;border-left:4px solid #2e7d32}
.alert-hr.error{background:#fce4e4;color:#c62828;border-left:4px solid #c62828}
.coif-img{width:48px;height:48px;border-radius:50%;object-fit:cover;border:2px solid #F5E6D3}
.coif-initiales{width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,#3E1F0D,#6B3A2A);display:flex;align-items:center;justify-content:center;font-weight:900;color:#C9A84C;font-family:'Playfair Display',serif;font-size:1rem;flex-shrink:0}
.badge-dispo{background:#e8f5e9;color:#2e7d32;padding:3px 10px;border-radius:8px;font-size:0.72rem;font-weight:700}
.badge-indispo{background:#fce4e4;color:#c62828;padding:3px 10px;border-radius:8px;font-size:0.72rem;font-weight:700}
.type-tag{background:#F5E6D3;color:#6B3A2A;padding:2px 8px;border-radius:6px;font-size:0.7rem;font-weight:600;display:inline-block;margin:2px}
.checkbox-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:8px}
.checkbox-item{display:flex;align-items:center;gap:8px;background:#FDFAF7;border:2px solid #F5E6D3;border-radius:10px;padding:8px 12px;cursor:pointer;transition:all 0.2s}
.checkbox-item:hover{border-color:#C9A84C;background:#FFFDF5}
.checkbox-item input[type=checkbox]{accent-color:#C9A84C;width:16px;height:16px}
.checkbox-item.checked{border-color:#C9A84C;background:#FFFDF5}
.photo-upload-zone{border:2px dashed #C9A84C;border-radius:14px;padding:20px;background:#FFFDF5;text-align:center;cursor:pointer;transition:all 0.3s;position:relative}
.photo-upload-zone:hover{background:#FFF8E0;border-color:#b8942e}
.photo-upload-zone input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%}
.photo-preview{width:80px;height:80px;border-radius:12px;object-fit:cover;border:3px solid #C9A84C;margin-bottom:10px}
.photo-upload-text{color:#6B3A2A;font-size:0.85rem;font-weight:600}
.photo-upload-hint{color:#9a7c5c;font-size:0.75rem;margin-top:4px}
</style>

<div class="admin-page"><div class="container">

<?php if($success): ?><div class="alert-hr success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if($error):   ?><div class="alert-hr error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<!-- FORMULAIRE -->
<div class="dash-card">
    <div class="dash-card-header">
        <h5><?= $edit ? 'Modifier la coiffeuse' : 'Ajouter une coiffeuse' ?></h5>
        <?php if($edit): ?>
            <a href="coiffeuses.php" class="pbtn pbtn-sm">+ Nouvelle coiffeuse</a>
        <?php endif; ?>
    </div>
    <div class="dash-card-body">
        <form method="POST" action="" enctype="multipart/form-data">
            <?php if($edit): ?>
                <input type="hidden" name="coiffeuse_id" value="<?= $edit['id'] ?>">
            <?php endif; ?>
            <input type="hidden" name="photo" value="<?= htmlspecialchars($edit['photo']??'') ?>">

            <div class="row g-3">
                <div class="col-md-3">
                    <label class="plabel">Prenom *</label>
                    <input type="text" class="pinput" name="prenom" value="<?= htmlspecialchars($edit['prenom']??'') ?>" placeholder="Ex: Aminata" required>
                </div>
                <div class="col-md-3">
                    <label class="plabel">Nom *</label>
                    <input type="text" class="pinput" name="nom" value="<?= htmlspecialchars($edit['nom']??'') ?>" placeholder="Ex: Diallo" required>
                </div>
                <div class="col-md-4">
                    <label class="plabel">Specialite *</label>
                    <input type="text" class="pinput" name="specialite" value="<?= htmlspecialchars($edit['specialite']??'') ?>" placeholder="Ex: Experte en tresses africaines" required>
                </div>
                <div class="col-md-2">
                    <label class="plabel">Annees d'experience</label>
                    <input type="number" class="pinput" name="annees_experience" min="0" max="50" value="<?= $edit['annees_experience']??0 ?>">
                </div>

                <div class="col-md-8">
                    <label class="plabel">Photo de la coiffeuse</label>
                    <div class="photo-upload-zone" id="uploadZone">
                        <input type="file" name="photo_upload" accept="image/jpeg,image/png,image/webp" id="photoInput" onchange="previewPhoto(this)">
                        <?php if(!empty($edit['photo'])): ?>
                            <img src="/ecommerce/<?= htmlspecialchars($edit['photo']) ?>" class="photo-preview" id="photoPreview">
                            <div class="photo-upload-text">Cliquer pour changer la photo</div>
                        <?php else: ?>
                            <img src="" class="photo-preview" id="photoPreview" style="display:none">
                            <div class="photo-upload-text">Cliquer pour uploader une photo</div>
                        <?php endif; ?>
                        <div class="photo-upload-hint">JPG, PNG ou WEBP — Max 5 Mo</div>
                    </div>
                </div>

                <div class="col-md-4 d-flex align-items-end pb-1">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="disponible" id="disponible"
                               <?= ($edit['disponible']??1) ? 'checked' : '' ?>
                               style="border-color:#C9A84C;accent-color:#C9A84C">
                        <label class="form-check-label" for="disponible" style="color:#3E1F0D;font-weight:600;font-size:0.85rem">
                            Disponible pour les RDV
                        </label>
                    </div>
                </div>

                <div class="col-12">
                    <label class="plabel">Biographie</label>
                    <textarea class="pinput" name="bio" rows="3" placeholder="Decrivez le parcours..."><?= htmlspecialchars($edit['bio']??'') ?></textarea>
                </div>

                <div class="col-12">
                    <label class="plabel">Types de cheveux maitrise</label>
                    <div class="checkbox-grid">
                        <?php foreach($types_disponibles as $type): ?>
                        <label class="checkbox-item <?= in_array($type, $edit_types)?'checked':'' ?>">
                            <input type="checkbox" name="types_cheveux[]" value="<?= $type ?>"
                                   <?= in_array($type, $edit_types)?'checked':'' ?>>
                            <?= $type ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="col-12 text-end">
                    <button type="submit" class="pbtn">
                        <?= $edit ? 'Mettre a jour' : 'Ajouter la coiffeuse' ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- LISTE -->
<div class="dash-card">
    <div class="dash-card-header">
        <h5>Liste des coiffeuses (<?= count($coiffeuses) ?>)</h5>
    </div>
    <div style="overflow-x:auto">
    <?php if(count($coiffeuses) > 0): ?>
    <table class="ptable">
        <thead><tr>
            <th>Photo</th>
            <th>Nom</th>
            <th>Specialite</th>
            <th>Types de cheveux</th>
            <th>Experience</th>
            <th>RDV</th>
            <th>Statut</th>
            <th>Actions</th>
        </tr></thead>
        <tbody>
        <?php foreach($coiffeuses as $c):
            $types = !empty($c['types_cheveux']) ? array_map('trim', explode(',', $c['types_cheveux'])) : [];
        ?>
        <tr>
            <td>
                <?php if(!empty($c['photo'])): ?>
                    <img src="/ecommerce/<?= htmlspecialchars($c['photo']) ?>" class="coif-img">
                <?php else: ?>
                    <div class="coif-initiales"><?= strtoupper(substr($c['prenom'],0,1).substr($c['nom'],0,1)) ?></div>
                <?php endif; ?>
            </td>
            <td><div style="font-weight:700"><?= htmlspecialchars($c['prenom'].' '.$c['nom']) ?></div></td>
            <td style="color:#6B3A2A;font-size:0.83rem"><?= htmlspecialchars($c['specialite']) ?></td>
            <td><?php foreach($types as $t): ?><span class="type-tag"><?= htmlspecialchars($t) ?></span><?php endforeach; ?></td>
            <td style="text-align:center;font-weight:700"><?= $c['annees_experience'] ?? 0 ?> ans</td>
            <td style="text-align:center;font-weight:700;color:#C1622F"><?= $c['nb_rdv'] ?></td>
            <td>
                <?php if($c['disponible']): ?>
                    <span class="badge-dispo">Disponible</span>
                <?php else: ?>
                    <span class="badge-indispo">Indisponible</span>
                <?php endif; ?>
            </td>
            <td>
                <div style="display:flex;gap:5px;flex-wrap:wrap">
                    <a href="?edit=<?= $c['id'] ?>" class="pbtn-info">Modifier</a>
                    <a href="?toggle=<?= $c['id'] ?>"
                       class="<?= $c['disponible'] ? 'pbtn-warning' : 'pbtn-success' ?>"
                       onclick="return confirm('<?= $c['disponible'] ? 'Rendre indisponible ?' : 'Rendre disponible ?' ?>')">
                        <?= $c['disponible'] ? 'Desactiver' : 'Activer' ?>
                    </a>
                    <?php if($c['nb_rdv'] == 0): ?>
                        <a href="?delete=<?= $c['id'] ?>" class="pbtn-danger"
                           onclick="return confirm('Supprimer cette coiffeuse ?')">Supprimer</a>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div style="text-align:center;padding:40px;color:#9a7c5c">
        <h5 style="color:#3E1F0D">Aucune coiffeuse pour le moment</h5>
    </div>
    <?php endif; ?>
    </div>
</div>

</div></div>

<script>
document.querySelectorAll('.checkbox-item input').forEach(cb => {
    cb.addEventListener('change', function() {
        this.closest('.checkbox-item').classList.toggle('checked', this.checked);
    });
});

function previewPhoto(input) {
    const preview = document.getElementById('photoPreview');
    const zone = document.getElementById('uploadZone');
    if(input.files && input.files[0]) {
        if(input.files[0].size > 5 * 1024 * 1024) {
            alert('La photo est trop grande. Maximum 5 Mo.');
            input.value = '';
            return;
        }
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            const textEl = zone.querySelector('.photo-upload-text');
            if(textEl) textEl.textContent = 'Photo selectionnee — cliquer pour changer';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include 'footer_admin.php'; ?>