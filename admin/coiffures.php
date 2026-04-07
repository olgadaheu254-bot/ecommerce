<?php
require_once 'auth_admin.php';
require_once '../config/database.php';

$page_title = 'Modèles Coiffures - Admin HairRoots';
$message = '';
$message_type = '';

// ─── DELETE ───────────────────────────────────────────────
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Supprimer la photo si elle existe
    $stmt = $pdo->prepare("SELECT photo FROM coiffures_modeles WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row && !empty($row['photo']) && file_exists('../' . $row['photo'])) {
        unlink('../' . $row['photo']);
    }
    $pdo->prepare("DELETE FROM coiffures_modeles WHERE id = ?")->execute([$id]);
    $message = "Modèle supprimé avec succès.";
    $message_type = "success";
}

// ─── ADD / EDIT ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id          = isset($_POST['id']) && is_numeric($_POST['id']) ? (int)$_POST['id'] : null;
    $nom         = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type_chev   = $_POST['type_cheveux'] ?? '';
    $genre       = $_POST['genre'] ?? '';
    $difficulte  = $_POST['difficulte'] ?? '';
    $prix        = floatval($_POST['prix_estimation'] ?? 0);
    $duree       = trim($_POST['duree_realisation'] ?? '');
    $tendance    = isset($_POST['tendance']) ? 1 : 0;

    // Upload photo
    $photo_path = $_POST['photo_actuelle'] ?? '';
    if (!empty($_FILES['photo']['name'])) {
        $ext   = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];
        if (in_array($ext, $allowed)) {
            $upload_dir = '../assets/uploads/coiffures/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $filename   = 'coiffure_' . time() . '_' . rand(100,999) . '.' . $ext;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $filename)) {
                // Supprimer l'ancienne photo
                if (!empty($photo_path) && file_exists('../' . $photo_path)) unlink('../' . $photo_path);
                $photo_path = 'assets/uploads/coiffures/' . $filename;
            }
        }
    }

    if ($id) {
        $stmt = $pdo->prepare("UPDATE coiffures_modeles SET nom=?, description=?, type_cheveux=?, genre=?, difficulte=?, prix_estimation=?, duree_realisation=?, tendance=?, photo=? WHERE id=?");
        $stmt->execute([$nom, $description, $type_chev, $genre, $difficulte, $prix, $duree, $tendance, $photo_path, $id]);
        $message = "Modèle mis à jour avec succès.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO coiffures_modeles (nom, description, type_cheveux, genre, difficulte, prix_estimation, duree_realisation, tendance, photo) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$nom, $description, $type_chev, $genre, $difficulte, $prix, $duree, $tendance, $photo_path]);
        $message = "Nouveau modèle ajouté avec succès.";
    }
    $message_type = "success";
}

// ─── FETCH ALL ────────────────────────────────────────────
$modeles = $pdo->query("SELECT * FROM coiffures_modeles ORDER BY tendance DESC, nom ASC")->fetchAll();

// ─── FETCH ONE FOR EDIT ───────────────────────────────────
$edit_modele = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM coiffures_modeles WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit_modele = $stmt->fetch();
}

include 'header_admin.php';
?>

<style>
:root {
    --gold:   #C9A84C;
    --orange: #C1622F;
    --dark:   #3E1F0D;
    --medium: #6B3A2A;
    --light:  #F5E6D3;
    --cream:  #FDF8F2;
    --white:  #FFFFFF;
}

.page-header {
    background: linear-gradient(135deg, var(--dark) 0%, var(--medium) 100%);
    border-radius: 20px;
    padding: 28px 32px;
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 15px;
}
.page-header h1 {
    font-family: 'Playfair Display', serif;
    color: #fff;
    font-size: 1.8rem;
    margin: 0;
}
.page-header p { color: rgba(255,255,255,0.65); margin: 4px 0 0; font-size: 0.88rem; }

/* Stats bar */
.stats-bar {
    display: flex;
    gap: 15px;
    margin-bottom: 25px;
    flex-wrap: wrap;
}
.stat-pill {
    background: var(--white);
    border: 1px solid var(--light);
    border-radius: 14px;
    padding: 12px 22px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.88rem;
    font-weight: 600;
    color: var(--dark);
    box-shadow: 0 2px 8px rgba(62,31,13,0.06);
}
.stat-pill .num { font-size: 1.4rem; font-weight: 800; color: var(--orange); }

/* Table */
.table-card {
    background: var(--white);
    border-radius: 20px;
    border: 1px solid var(--light);
    box-shadow: 0 4px 20px rgba(62,31,13,0.06);
    overflow: hidden;
    margin-bottom: 30px;
}
.table-card-header {
    background: linear-gradient(135deg, #FDF3E7, #FBE8D0);
    padding: 18px 24px;
    border-bottom: 1px solid var(--light);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}
.table-card-header h5 {
    font-family: 'Playfair Display', serif;
    color: var(--dark);
    font-weight: 700;
    margin: 0;
    font-size: 1.1rem;
}
.table-responsive { overflow-x: auto; }
table.coiff-table { width: 100%; border-collapse: collapse; }
table.coiff-table thead tr {
    background: linear-gradient(90deg, #FDF3E7, #FBE8D0);
}
table.coiff-table th {
    padding: 12px 16px;
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--medium);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 2px solid var(--light);
    white-space: nowrap;
}
table.coiff-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #FDF3E7;
    color: var(--dark);
    font-size: 0.88rem;
    vertical-align: middle;
}
table.coiff-table tr:last-child td { border-bottom: none; }
table.coiff-table tr:hover td { background: #FFFDF9; }

/* Thumb */
.coiff-thumb {
    width: 52px; height: 52px;
    border-radius: 12px;
    object-fit: cover;
    object-position: top;
    border: 2px solid var(--light);
}
.coiff-thumb-ph {
    width: 52px; height: 52px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--light), #FDEBD0);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem;
    border: 2px solid var(--light);
}

/* Badges */
.badge-type   { background: #F5E6D3; color: var(--medium); padding: 3px 10px; border-radius: 8px; font-size: 0.72rem; font-weight: 700; }
.badge-genre  { background: #E8F5E9; color: #2E7D32; padding: 3px 10px; border-radius: 8px; font-size: 0.72rem; font-weight: 700; }
.badge-facile { background: #E8F5E9; color: #2E7D32; padding: 3px 10px; border-radius: 8px; font-size: 0.72rem; font-weight: 700; }
.badge-moyen  { background: #FFF8E1; color: #F57F17; padding: 3px 10px; border-radius: 8px; font-size: 0.72rem; font-weight: 700; }
.badge-difficile { background: #FCE4E4; color: #C62828; padding: 3px 10px; border-radius: 8px; font-size: 0.72rem; font-weight: 700; }
.badge-tendance {
    background: linear-gradient(135deg, var(--gold), #b8942e);
    color: var(--dark);
    padding: 3px 10px; border-radius: 8px; font-size: 0.72rem; font-weight: 700;
}

/* Action buttons */
.btn-edit {
    background: linear-gradient(135deg, var(--gold), #b8942e);
    color: var(--dark);
    border: none; border-radius: 9px;
    padding: 7px 14px; font-size: 0.8rem; font-weight: 700;
    text-decoration: none; display: inline-flex; align-items: center; gap: 5px;
    transition: all 0.2s;
}
.btn-edit:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(201,168,76,0.4); color: var(--dark); }
.btn-delete {
    background: #FCE4E4; color: #C62828;
    border: none; border-radius: 9px;
    padding: 7px 14px; font-size: 0.8rem; font-weight: 700;
    text-decoration: none; display: inline-flex; align-items: center; gap: 5px;
    transition: all 0.2s; cursor: pointer;
}
.btn-delete:hover { background: #C62828; color: #fff; transform: translateY(-1px); }
.btn-add {
    background: linear-gradient(135deg, var(--orange), #a0491f);
    color: #fff; border: none; border-radius: 12px;
    padding: 11px 24px; font-size: 0.88rem; font-weight: 700;
    display: inline-flex; align-items: center; gap: 8px;
    text-decoration: none; transition: all 0.3s; cursor: pointer;
}
.btn-add:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(193,98,47,0.35); color: #fff; }

/* Form card */
.form-card {
    background: var(--white);
    border-radius: 20px;
    border: 2px solid var(--light);
    box-shadow: 0 6px 30px rgba(62,31,13,0.08);
    overflow: hidden;
    margin-bottom: 30px;
}
.form-card-header {
    background: linear-gradient(135deg, var(--dark), var(--medium));
    padding: 20px 28px;
    display: flex; align-items: center; justify-content: space-between;
}
.form-card-header h5 {
    font-family: 'Playfair Display', serif;
    color: #fff; margin: 0; font-size: 1.15rem;
}
.form-card-body { padding: 28px; }

.form-label-custom {
    font-weight: 700; font-size: 0.82rem; color: var(--medium);
    text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 6px;
    display: block;
}
.form-control-custom {
    width: 100%; padding: 11px 15px;
    border: 2px solid var(--light); border-radius: 12px;
    background: var(--cream); font-size: 0.9rem; color: var(--dark);
    outline: none; transition: border-color 0.2s;
    font-family: 'Poppins', sans-serif;
}
.form-control-custom:focus { border-color: var(--gold); background: #fff; }
select.form-control-custom { cursor: pointer; }

/* Toggle tendance */
.toggle-wrap { display: flex; align-items: center; gap: 12px; }
.toggle-input { display: none; }
.toggle-label {
    width: 50px; height: 26px;
    background: #ddd; border-radius: 13px;
    position: relative; cursor: pointer; transition: background 0.3s;
}
.toggle-label::after {
    content: ''; position: absolute;
    width: 20px; height: 20px; border-radius: 50%;
    background: #fff; top: 3px; left: 3px;
    transition: left 0.3s; box-shadow: 0 1px 4px rgba(0,0,0,0.2);
}
.toggle-input:checked + .toggle-label { background: linear-gradient(135deg, var(--gold), #b8942e); }
.toggle-input:checked + .toggle-label::after { left: 27px; }

/* Preview photo */
.photo-preview {
    width: 90px; height: 90px; border-radius: 14px;
    object-fit: cover; object-position: top;
    border: 3px solid var(--light); display: none;
}
.photo-preview.show { display: block; }
.photo-ph {
    width: 90px; height: 90px; border-radius: 14px;
    background: linear-gradient(135deg, var(--light), #FDEBD0);
    display: flex; align-items: center; justify-content: center;
    font-size: 2.5rem; border: 3px solid var(--light);
}

/* Alert */
.alert-custom {
    border-radius: 14px; padding: 14px 20px;
    font-weight: 600; font-size: 0.9rem;
    display: flex; align-items: center; gap: 10px; margin-bottom: 22px;
}
.alert-success { background: #E8F5E9; color: #2E7D32; border: 1px solid #C8E6C9; }
.alert-error   { background: #FCE4E4; color: #C62828; border: 1px solid #FFCDD2; }

/* Empty state */
.empty-state {
    text-align: center; padding: 50px 20px;
    color: var(--medium);
}
.empty-state .icon { font-size: 3.5rem; margin-bottom: 12px; }
.empty-state h5 { font-family: 'Playfair Display', serif; color: var(--dark); }

/* Tendance star */
.star-on  { color: var(--gold); font-size: 1.1rem; }
.star-off { color: #ddd;        font-size: 1.1rem; }
</style>

<div class="container-fluid py-4 px-4">

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div>
            <h1> Modèles Coiffures</h1>
            <p>Gérez les inspirations et tendances affichées sur votre site</p>
        </div>
        <a href="#form-section" class="btn-add">
            <i class="bi bi-plus-lg"></i>
            <?= $edit_modele ? 'Modifier le modèle' : 'Ajouter un modèle' ?>
        </a>
    </div>

    <?php if ($message): ?>
    <div class="alert-custom alert-<?= $message_type ?>">
        <i class="bi bi-<?= $message_type === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?>"></i>
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <!-- STATS BAR -->
    <div class="stats-bar">
        <div class="stat-pill">
            <span class="num"><?= count($modeles) ?></span>
            Modèles au total
        </div>
        <div class="stat-pill">
            <span class="num"><?= count(array_filter($modeles, fn($m) => $m['tendance'])) ?></span>
            En tendance 
        </div>
        <?php
        $types = array_count_values(array_column($modeles, 'type_cheveux'));
        foreach ($types as $t => $c):
        ?>
        <div class="stat-pill">
            <span class="num"><?= $c ?></span>
            <?= htmlspecialchars($t) ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- TABLE DES MODELES -->
    <div class="table-card">
        <div class="table-card-header">
            <h5><i class="bi bi-grid-3x3-gap me-2" style="color:var(--gold)"></i>Liste des modèles</h5>
            <span style="color:var(--medium);font-size:0.82rem;font-weight:600"><?= count($modeles) ?> coiffure<?= count($modeles) > 1 ? 's' : '' ?></span>
        </div>
        <div class="table-responsive">
            <?php if (count($modeles) > 0): ?>
            <table class="coiff-table">
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Nom</th>
                        <th>Type</th>
                        <th>Genre</th>
                        <th>Difficulté</th>
                        <th>Prix</th>
                        <th>Durée</th>
                        <th>Tendance</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($modeles as $m): ?>
                    <tr>
                        <td>
                            <?php if (!empty($m['photo'])): ?>
                                <img src="../<?= htmlspecialchars($m['photo']) ?>" class="coiff-thumb" alt="">
                            <?php else: ?>
                                <div class="coiff-thumb-ph">
                                    <?php $icons = ['Bouclés'=>'0.
                                    ','Crépus'=>'','Lisses'=>'','Ondulés'=>'〰️']; echo $icons[$m['type_cheveux']] ?? ''; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong style="font-family:'Playfair Display',serif"><?= htmlspecialchars($m['nom']) ?></strong>
                            <?php if (!empty($m['description'])): ?>
                            <div style="color:#9a7c5c;font-size:0.78rem;margin-top:3px"><?= htmlspecialchars(substr($m['description'], 0, 55)) ?>…</div>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge-type"><?= htmlspecialchars($m['type_cheveux']) ?></span></td>
                        <td><span class="badge-genre"><?= htmlspecialchars($m['genre']) ?></span></td>
                        <td>
                            <span class="badge-<?= strtolower($m['difficulte']) ?>">
                                <?= htmlspecialchars($m['difficulte']) ?>
                            </span>
                        </td>
                        <td><strong style="color:var(--orange)"><?= number_format($m['prix_estimation'], 2) ?>€</strong></td>
                        <td style="color:var(--medium)"><?= htmlspecialchars($m['duree_realisation']) ?></td>
                        <td>
                            <?php if ($m['tendance']): ?>
                                <span class="badge-tendance"> Tendance</span>
                            <?php else: ?>
                                <span style="color:#ccc;font-size:0.8rem">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display:flex;gap:6px;flex-wrap:wrap">
                                <a href="?edit=<?= $m['id'] ?>#form-section" class="btn-edit">
                                    <i class="bi bi-pencil-fill"></i> Éditer
                                </a>
                                <button class="btn-delete"
                                    onclick="if(confirm('Supprimer « <?= htmlspecialchars(addslashes($m['nom'])) ?> » ?')) window.location='?delete=<?= $m['id'] ?>'">
                                    <i class="bi bi-trash-fill"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <div class="icon"></div>
                <h5>Aucun modèle pour le moment</h5>
                <p>Ajoutez votre premier modèle de coiffure ci-dessous.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ─── FORMULAIRE ─────────────────────────────────────── -->
    <div class="form-card" id="form-section">
        <div class="form-card-header">
            <h5>
                <i class="bi bi-<?= $edit_modele ? 'pencil-fill' : 'plus-circle-fill' ?> me-2"></i>
                <?= $edit_modele ? 'Modifier : ' . htmlspecialchars($edit_modele['nom']) : 'Ajouter un nouveau modèle' ?>
            </h5>
            <?php if ($edit_modele): ?>
            <a href="coiffures.php" style="color:rgba(255,255,255,0.7);font-size:0.85rem;text-decoration:none">
                <i class="bi bi-x-lg"></i> Annuler
            </a>
            <?php endif; ?>
        </div>
        <div class="form-card-body">
            <form method="POST" enctype="multipart/form-data">
                <?php if ($edit_modele): ?>
                <input type="hidden" name="id" value="<?= $edit_modele['id'] ?>">
                <input type="hidden" name="photo_actuelle" value="<?= htmlspecialchars($edit_modele['photo'] ?? '') ?>">
                <?php endif; ?>

                <div class="row g-4">

                    <!-- NOM -->
                    <div class="col-md-6">
                        <label class="form-label-custom">Nom de la coiffure *</label>
                        <input type="text" name="nom" class="form-control-custom" required
                            placeholder="Ex: Twist Out Bouclé"
                            value="<?= htmlspecialchars($edit_modele['nom'] ?? '') ?>">
                    </div>

                    <!-- PRIX -->
                    <div class="col-md-3">
                        <label class="form-label-custom">Prix à partir de (€) *</label>
                        <input type="number" name="prix_estimation" class="form-control-custom" required
                            step="0.01" min="0" placeholder="35.00"
                            value="<?= htmlspecialchars($edit_modele['prix_estimation'] ?? '') ?>">
                    </div>

                    <!-- DURÉE -->
                    <div class="col-md-3">
                        <label class="form-label-custom">Durée de réalisation *</label>
                        <input type="text" name="duree_realisation" class="form-control-custom" required
                            placeholder="Ex: 2h30"
                            value="<?= htmlspecialchars($edit_modele['duree_realisation'] ?? '') ?>">
                    </div>

                    <!-- DESCRIPTION -->
                    <div class="col-12">
                        <label class="form-label-custom">Description</label>
                        <textarea name="description" class="form-control-custom" rows="3"
                            placeholder="Décrivez le style, les techniques utilisées..."><?= htmlspecialchars($edit_modele['description'] ?? '') ?></textarea>
                    </div>

                    <!-- TYPE CHEVEUX -->
                    <div class="col-md-4">
                        <label class="form-label-custom">Type de cheveux *</label>
                        <select name="type_cheveux" class="form-control-custom" required>
                            <option value="">— Choisir —</option>
                            <?php foreach (['Bouclés','Crépus','Lisses','Ondulés'] as $t): ?>
                            <option value="<?= $t ?>" <?= ($edit_modele['type_cheveux'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- GENRE -->
                    <div class="col-md-4">
                        <label class="form-label-custom">Genre *</label>
                        <select name="genre" class="form-control-custom" required>
                            <option value="">— Choisir —</option>
                            <?php foreach (['Femme','Homme','Enfant','Mixte'] as $g): ?>
                            <option value="<?= $g ?>" <?= ($edit_modele['genre'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- DIFFICULTÉ -->
                    <div class="col-md-4">
                        <label class="form-label-custom">Difficulté *</label>
                        <select name="difficulte" class="form-control-custom" required>
                            <option value="">— Choisir —</option>
                            <?php foreach (['Facile','Moyen','Difficile'] as $d): ?>
                            <option value="<?= $d ?>" <?= ($edit_modele['difficulte'] ?? '') === $d ? 'selected' : '' ?>><?= $d ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- PHOTO -->
                    <div class="col-md-6">
                        <label class="form-label-custom">Photo</label>
                        <div style="display:flex;align-items:center;gap:15px">
                            <?php if (!empty($edit_modele['photo'])): ?>
                                <img src="../<?= htmlspecialchars($edit_modele['photo']) ?>" class="photo-preview show" id="photoPreview" alt="">
                            <?php else: ?>
                                <div class="photo-ph" id="photoPlaceholder"></div>
                                <img src="" class="photo-preview" id="photoPreview" alt="">
                            <?php endif; ?>
                            <div style="flex:1">
                                <input type="file" name="photo" id="photoInput" accept="image/*"
                                    class="form-control-custom" style="padding:8px"
                                    onchange="previewPhoto(this)">
                                <div style="color:#9a7c5c;font-size:0.75rem;margin-top:5px">JPG, PNG, WEBP — Max recommandé : 2 Mo</div>
                            </div>
                        </div>
                    </div>

                    <!-- TENDANCE -->
                    <div class="col-md-6">
                        <label class="form-label-custom">Mettre en tendance</label>
                        <div class="toggle-wrap" style="margin-top:8px">
                            <input type="checkbox" name="tendance" id="toggleTendance" class="toggle-input"
                                <?= !empty($edit_modele['tendance']) ? 'checked' : '' ?>>
                            <label for="toggleTendance" class="toggle-label"></label>
                            <span style="font-size:0.88rem;color:var(--medium);font-weight:600">
                                Afficher dans "Tendances du moment" 
                            </span>
                        </div>
                        <div style="color:#9a7c5c;font-size:0.78rem;margin-top:8px">
                             Les 4 premières tendances sont affichées sur la page publique.
                        </div>
                    </div>

                    <!-- SUBMIT -->
                    <div class="col-12" style="padding-top:10px;border-top:1px solid var(--light);display:flex;gap:12px;flex-wrap:wrap">
                        <button type="submit" class="btn-add" style="padding:13px 30px">
                            <i class="bi bi-<?= $edit_modele ? 'check-lg' : 'plus-lg' ?>"></i>
                            <?= $edit_modele ? 'Enregistrer les modifications' : 'Ajouter le modèle' ?>
                        </button>
                        <?php if ($edit_modele): ?>
                        <a href="coiffures.php" style="background:#F5E6D3;color:var(--dark);border-radius:12px;padding:13px 24px;font-weight:700;font-size:0.88rem;text-decoration:none;display:flex;align-items:center;gap:8px">
                            <i class="bi bi-x"></i> Annuler
                        </a>
                        <?php endif; ?>
                    </div>

                </div>
            </form>
        </div>
    </div>

</div>

<script>
function previewPhoto(input) {
    const preview = document.getElementById('photoPreview');
    const placeholder = document.getElementById('photoPlaceholder');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.src = e.target.result;
            preview.classList.add('show');
            if (placeholder) placeholder.style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php
// Footer admin si tu en as un
// include 'footer_admin.php';
?>