<?php
require_once 'auth_admin.php';
require_once '../config/database.php';
$page_title = 'Gestion Coiffeuses - HairRoots Admin';

$success = ''; $error = '';

// SUPPRIMER COIFFEUSE
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Vérifier si elle a des RDV
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE coiffeuse_id = ?");
    $stmt->execute([$id]);
    $nb_rdv = $stmt->fetchColumn();

    if($nb_rdv > 0) {
        $error = "Impossible de supprimer : cette coiffeuse a $nb_rdv rendez-vous lié(s). Désactivez-la plutôt.";
    } else {
        $pdo->prepare("DELETE FROM coiffeuses WHERE id=?")->execute([$id]);
        $success = "Coiffeuse supprimée avec succès.";
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
    $id                 = isset($_POST['coiffeuse_id']) ? (int)$_POST['coiffeuse_id'] : 0;
    $prenom             = trim($_POST['prenom']);
    $nom                = trim($_POST['nom']);
    $specialite         = trim($_POST['specialite']);
    $bio                = trim($_POST['bio']);
    $annees_experience  = (int)$_POST['annees_experience'];
    $disponible         = isset($_POST['disponible']) ? 1 : 0;
    $photo              = trim($_POST['photo']);
    $types_cheveux      = isset($_POST['types_cheveux']) ? implode(',', $_POST['types_cheveux']) : '';

    if(empty($prenom) || empty($nom) || empty($specialite)) {
        $error = "Prénom, nom et spécialité sont obligatoires.";
    } else {
        if($id > 0) {
            $stmt = $pdo->prepare("UPDATE coiffeuses SET prenom=?, nom=?, specialite=?, bio=?, annees_experience=?, disponible=?, photo=?, types_cheveux=? WHERE id=?");
            if($stmt->execute([$prenom, $nom, $specialite, $bio, $annees_experience, $disponible, $photo, $types_cheveux, $id])) {
                $success = "Coiffeuse mise à jour avec succès !";
            } else {
                $error = "Erreur lors de la mise à jour.";
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO coiffeuses (prenom, nom, specialite, bio, annees_experience, disponible, photo, types_cheveux) VALUES (?,?,?,?,?,?,?,?)");
            if($stmt->execute([$prenom, $nom, $specialite, $bio, $annees_experience, $disponible, $photo, $types_cheveux])) {
                $success = "Coiffeuse ajoutée avec succès !";
            } else {
                $error = "Erreur lors de l'ajout.";
            }
        }
    }
}

// COIFFEUSE À MODIFIER
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

// LISTE COIFFEUSES
$coiffeuses = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM appointments a WHERE a.coiffeuse_id = c.id) as nb_rdv FROM coiffeuses c ORDER BY c.prenom")->fetchAll();

$types_disponibles = ['Bouclés', 'Crépus', 'Lisses', 'Ondulés', 'Tresses', 'Colorations', 'Enfants', 'Soins'];

include 'header_admin.php';
?>
<style>
.admin-page{background:#FDF8F2;min-height:80vh;padding:30px 0}
.dash-header{background:linear-gradient(135deg,#3E1F0D,#6B3A2A);border-radius:20px;padding:22px 28px;margin-bottom:25px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:15px}
.dash-header h1{font-family:'Playfair Display',serif;color:#C9A84C;font-size:1.6rem;font-weight:900;margin:0}
.dash-nav{display:flex;gap:8px;flex-wrap:wrap}
.dash-nav-btn{background:rgba(201,168,76,0.15);color:#C9A84C;border:1px solid rgba(201,168,76,0.3);border-radius:10px;padding:7px 14px;font-size:0.8rem;font-weight:600;text-decoration:none;transition:all 0.3s}
.dash-nav-btn:hover,.dash-nav-btn.active{background:#C9A84C;color:#3E1F0D}
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
</style>

<div class="admin-page"><div class="container">

<div class="dash-header">
    <div>
        <h1> Gestion des Coiffeuses</h1>
        <p style="color:rgba(255,255,255,0.6);margin:4px 0 0;font-size:0.82rem"><?= count($coiffeuses) ?> coiffeuse(s) au total</p>
    </div>
    <div class="dash-nav">
        <a href="index.php" class="dash-nav-btn"> Dashboard</a>
        <a href="products.php" class="dash-nav-btn"> Produits</a>
        <a href="orders.php" class="dash-nav-btn"> Commandes</a>
        <a href="users.php" class="dash-nav-btn"> Utilisateurs</a>
        <a href="appointments.php" class="dash-nav-btn"> RDV</a>
        <a href="coiffeuses.php" class="dash-nav-btn active"> Coiffeuses</a>
    </div>
</div>

<?php if($success): ?><div class="alert-hr success"> <?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if($error):   ?><div class="alert-hr error"> <?= htmlspecialchars($error) ?></div><?php endif; ?>

<!-- FORMULAIRE AJOUT / MODIFICATION -->
<div class="dash-card">
    <div class="dash-card-header">
        <h5><?= $edit ? ' Modifier la coiffeuse' : ' Ajouter une coiffeuse' ?></h5>
        <?php if($edit): ?>
            <a href="coiffeuses.php" class="pbtn pbtn-sm">+ Nouvelle coiffeuse</a>
        <?php endif; ?>
    </div>
    <div class="dash-card-body">
        <form method="POST" action="">
            <?php if($edit): ?>
                <input type="hidden" name="coiffeuse_id" value="<?= $edit['id'] ?>">
            <?php endif; ?>

            <div class="row g-3">
                <!-- PRENOM -->
                <div class="col-md-3">
                    <label class="plabel">Prénom *</label>
                    <input type="text" class="pinput" name="prenom"
                           value="<?= htmlspecialchars($edit['prenom']??'') ?>"
                           placeholder="Ex: Aminata" required>
                </div>

                <!-- NOM -->
                <div class="col-md-3">
                    <label class="plabel">Nom *</label>
                    <input type="text" class="pinput" name="nom"
                           value="<?= htmlspecialchars($edit['nom']??'') ?>"
                           placeholder="Ex: Diallo" required>
                </div>

                <!-- SPECIALITE -->
                <div class="col-md-4">
                    <label class="plabel">Spécialité *</label>
                    <input type="text" class="pinput" name="specialite"
                           value="<?= htmlspecialchars($edit['specialite']??'') ?>"
                           placeholder="Ex: Experte en tresses africaines" required>
                </div>

                <!-- EXPERIENCE -->
                <div class="col-md-2">
                    <label class="plabel">Années d'expérience</label>
                    <input type="number" class="pinput" name="annees_experience"
                           min="0" max="50"
                           value="<?= $edit['annees_experience']??0 ?>">
                </div>

                <!-- PHOTO URL -->
                <div class="col-md-8">
                    <label class="plabel">URL de la photo</label>
                    <input type="text" class="pinput" name="photo"
                           value="<?= htmlspecialchars($edit['photo']??'') ?>"
                           placeholder="Ex: assets/images/coiffeuses/aminata.jpg">
                </div>

                <!-- DISPONIBILITE -->
                <div class="col-md-4 d-flex align-items-end pb-1">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="disponible" id="disponible"
                               <?= ($edit['disponible']??1) ? 'checked' : '' ?>
                               style="border-color:#C9A84C;accent-color:#C9A84C">
                        <label class="form-check-label" for="disponible"
                               style="color:#3E1F0D;font-weight:600;font-size:0.85rem">
                             Disponible pour les RDV
                        </label>
                    </div>
                </div>

                <!-- BIO -->
                <div class="col-12">
                    <label class="plabel">Biographie</label>
                    <textarea class="pinput" name="bio" rows="3"
                              placeholder="Décrivez le parcours et la personnalité de la coiffeuse..."><?= htmlspecialchars($edit['bio']??'') ?></textarea>
                </div>

                <!-- TYPES DE CHEVEUX -->
                <div class="col-12">
                    <label class="plabel">Types de cheveux maîtrisés</label>
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

                <!-- BOUTON SUBMIT -->
                <div class="col-12 text-end">
                    <button type="submit" class="pbtn">
                        <?= $edit ? ' Mettre à jour' : ' Ajouter la coiffeuse' ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- LISTE DES COIFFEUSES -->
<div class="dash-card">
    <div class="dash-card-header">
        <h5> Liste des coiffeuses (<?= count($coiffeuses) ?>)</h5>
    </div>
    <div style="overflow-x:auto">
    <?php if(count($coiffeuses) > 0): ?>
    <table class="ptable">
        <thead><tr>
            <th>Photo</th>
            <th>Nom</th>
            <th>Spécialité</th>
            <th>Types de cheveux</th>
            <th>Expérience</th>
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
                    <div class="coif-initiales">
                        <?= strtoupper(substr($c['prenom'],0,1).substr($c['nom'],0,1)) ?>
                    </div>
                <?php endif; ?>
            </td>
            <td>
                <div style="font-weight:700"><?= htmlspecialchars($c['prenom'].' '.$c['nom']) ?></div>
            </td>
            <td style="color:#6B3A2A;font-size:0.83rem"><?= htmlspecialchars($c['specialite']) ?></td>
            <td>
                <?php foreach($types as $t): ?>
                    <span class="type-tag"><?= htmlspecialchars($t) ?></span>
                <?php endforeach; ?>
            </td>
            <td style="text-align:center;font-weight:700">
                <?= $c['annees_experience'] ?? 0 ?> ans
            </td>
            <td style="text-align:center;font-weight:700;color:#C1622F">
                <?= $c['nb_rdv'] ?>
            </td>
            <td>
                <?php if($c['disponible']): ?>
                    <span class="badge-dispo"> Disponible</span>
                <?php else: ?>
                    <span class="badge-indispo"> Indisponible</span>
                <?php endif; ?>
            </td>
            <td>
                <div style="display:flex;gap:5px;flex-wrap:wrap">
                    <!-- MODIFIER -->
                    <a href="?edit=<?= $c['id'] ?>" class="pbtn-info"> Modifier</a>

                    <!-- TOGGLE DISPO -->
                    <a href="?toggle=<?= $c['id'] ?>"
                       class="<?= $c['disponible'] ? 'pbtn-warning' : 'pbtn-success' ?>"
                       onclick="return confirm('<?= $c['disponible'] ? 'Rendre indisponible ?' : 'Rendre disponible ?' ?>')">
                        <?= $c['disponible'] ? '⏸ Désactiver' : '▶ Activer' ?>
                    </a>

                    <!-- SUPPRIMER -->
                    <?php if($c['nb_rdv'] == 0): ?>
                        <a href="?delete=<?= $c['id'] ?>" class="pbtn-danger"
                           onclick="return confirm('Supprimer définitivement cette coiffeuse ?')">
                             Supprimer
                        </a>
                    <?php else: ?>
                        <span style="background:#F5E6D3;color:#9a7c5c;border-radius:8px;padding:6px 12px;font-size:0.75rem;font-weight:600"
                              title="<?= $c['nb_rdv'] ?> RDV lié(s) — désactivez plutôt">
                             <?= $c['nb_rdv'] ?> RDV
                        </span>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div style="text-align:center;padding:40px;color:#9a7c5c">
        <div style="font-size:3rem;margin-bottom:15px"></div>
        <h5 style="color:#3E1F0D">Aucune coiffeuse pour le moment</h5>
        <p>Ajoutez votre première coiffeuse avec le formulaire ci-dessus</p>
    </div>
    <?php endif; ?>
    </div>
</div>

</div></div>

<script>
// Mettre à jour le style des checkboxes au clic
document.querySelectorAll('.checkbox-item input').forEach(cb => {
    cb.addEventListener('change', function() {
        this.closest('.checkbox-item').classList.toggle('checked', this.checked);
    });
});
</script>

<?php include 'footer_admin.php'; ?>