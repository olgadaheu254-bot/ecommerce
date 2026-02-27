<?php
require_once '../config/database.php';
$page_title = 'Mon Profil - MonShop';

// Vérifier si l'utilisateur est connecté
if(!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=/ecommerce/user/profile.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Récupérer les informations de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Traitement de la mise à jour du profil
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $postal_code = trim($_POST['postal_code']);
    
    // Validation
    if(empty($first_name) || empty($last_name) || empty($email)) {
        $error = "Les champs prénom, nom et email sont obligatoires.";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email invalide.";
    } else {
        // Vérifier si l'email est déjà utilisé par un autre utilisateur
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        
        if($stmt->fetch()) {
            $error = "Cet email est déjà utilisé.";
        } else {
            // Mettre à jour les informations
            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, city = ?, postal_code = ? WHERE id = ?");
            
            if($stmt->execute([$first_name, $last_name, $email, $phone, $address, $city, $postal_code, $user_id])) {
                $success = "Profil mis à jour avec succès !";
                // Recharger les données
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
                
                // Mettre à jour la session
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
            } else {
                $error = "Erreur lors de la mise à jour.";
            }
        }
    }
}

// Traitement du changement de mot de passe
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if(empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "Tous les champs sont obligatoires.";
    } elseif(!password_verify($current_password, $user['password'])) {
        $error = "Mot de passe actuel incorrect.";
    } elseif(strlen($new_password) < 6) {
        $error = "Le nouveau mot de passe doit contenir au moins 6 caractères.";
    } elseif($new_password !== $confirm_password) {
        $error = "Les nouveaux mots de passe ne correspondent pas.";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        
        if($stmt->execute([$hashed_password, $user_id])) {
            $success = "Mot de passe changé avec succès !";
        } else {
            $error = "Erreur lors du changement de mot de passe.";
        }
    }
}

// Récupérer les commandes de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$recent_orders = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="container my-5">
    <h1 class="mb-4"><i class="bi bi-person-circle"></i> Mon Profil</h1>
    
    <?php if($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Informations personnelles -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-person-badge"></i> Informations personnelles</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">Prénom *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Nom *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Nom d'utilisateur</label>
                            <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                            <small class="text-muted">Le nom d'utilisateur ne peut pas être modifié</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Adresse</label>
                            <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">Ville</label>
                                <input type="text" class="form-control" id="city" name="city" 
                                       value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="postal_code" class="form-label">Code postal</label>
                                <input type="text" class="form-control" id="postal_code" name="postal_code" 
                                       value="<?php echo htmlspecialchars($user['postal_code'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn btn-primary w-100">
                            <i class="bi bi-save"></i> Mettre à jour le profil
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Changer le mot de passe -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-warning">
                    <h5 class="mb-0"><i class="bi bi-key"></i> Changer le mot de passe</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Mot de passe actuel</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Nouveau mot de passe</label>
                            <input type="password" class="form-control" id="new_password" name="new_password">
                            <small class="text-muted">Au moins 6 caractères</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>
                        
                        <button type="submit" name="change_password" class="btn btn-warning w-100">
                            <i class="bi bi-shield-lock"></i> Changer le mot de passe
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Informations du compte -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Informations du compte</h5>
                </div>
                <div class="card-body">
                    <p><strong>Membre depuis :</strong> <?php echo date('d/m/Y', strtotime($user['created_at'])); ?></p>
                    <p><strong>Rôle :</strong> 
                        <?php if($user['role'] === 'admin'): ?>
                            <span class="badge bg-danger">Administrateur</span>
                        <?php else: ?>
                            <span class="badge bg-primary">Utilisateur</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Commandes récentes -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-bag-check"></i> Mes commandes récentes</h5>
                </div>
                <div class="card-body">
                    <?php if(count($recent_orders) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>N° Commande</th>
                                        <th>Date</th>
                                        <th>Montant</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($recent_orders as $order): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                            <td><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></td>
                                            <td><strong><?php echo number_format($order['total_amount'], 2); ?> €</strong></td>
                                            <td>
                                                <?php 
                                                $status_badges = [
                                                    'pending' => 'warning',
                                                    'processing' => 'info',
                                                    'shipped' => 'primary',
                                                    'delivered' => 'success',
                                                    'cancelled' => 'danger'
                                                ];
                                                $status_labels = [
                                                    'pending' => 'En attente',
                                                    'processing' => 'En traitement',
                                                    'shipped' => 'Expédiée',
                                                    'delivered' => 'Livrée',
                                                    'cancelled' => 'Annulée'
                                                ];
                                                $badge_class = $status_badges[$order['status']] ?? 'secondary';
                                                $label = $status_labels[$order['status']] ?? $order['status'];
                                                ?>
                                                <span class="badge bg-<?php echo $badge_class; ?>">
                                                    <?php echo $label; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="order-details.php?id=<?php echo $order['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i> Voir détails
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox display-1 text-muted"></i>
                            <p class="mt-3">Vous n'avez pas encore de commandes.</p>
                            <a href="/mon-ecommerce/products/index.php" class="btn btn-primary">
                                <i class="bi bi-cart-plus"></i> Commencer à acheter
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>