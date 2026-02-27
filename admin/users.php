<?php
require_once '../config/database.php';
$page_title = 'Gestion des Utilisateurs - Admin';

// Vérifier si l'utilisateur est admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /ecommerce/user/login.php');
    exit;
}

// Traitement du changement de rôle
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $user_id = (int)$_POST['user_id'];
    $new_role = $_POST['role'];
    
    // Ne pas permettre de modifier son propre rôle
    if($user_id != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        if($stmt->execute([$new_role, $user_id])) {
            $_SESSION['success_message'] = "Rôle de l'utilisateur mis à jour avec succès.";
        }
    } else {
        $_SESSION['error_message'] = "Vous ne pouvez pas modifier votre propre rôle.";
    }
    header('Location: users.php');
    exit;
}

// Traitement de la suppression d'un utilisateur
if(isset($_GET['delete'])) {
    $user_id = (int)$_GET['delete'];
    
    // Ne pas permettre de se supprimer soi-même
    if($user_id != $_SESSION['user_id']) {
        try {
            $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if($user) {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                if($stmt->execute([$user_id])) {
                    $_SESSION['success_message'] = "L'utilisateur '" . htmlspecialchars($user['username']) . "' a été supprimé.";
                }
            }
        } catch(PDOException $e) {
            $_SESSION['error_message'] = "Erreur : Cet utilisateur ne peut pas être supprimé (commandes liées).";
        }
    } else {
        $_SESSION['error_message'] = "Vous ne pouvez pas supprimer votre propre compte.";
    }
    header('Location: users.php');
    exit;
}

// Récupérer tous les utilisateurs avec stats
$stmt = $pdo->query("
    SELECT 
        u.*,
        COUNT(DISTINCT o.id) as total_orders,
        COALESCE(SUM(o.total_amount), 0) as total_spent
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$users = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="container-fluid my-4">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-2 d-md-block bg-light sidebar">
            <div class="position-sticky">
                <h5 class="p-3 mb-0 bg-primary text-white">
                    <i class="bi bi-speedometer2"></i> Admin Panel
                </h5>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="bi bi-house-door"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">
                            <i class="bi bi-box-seam"></i> Produits
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">
                            <i class="bi bi-cart-check"></i> Commandes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="users.php">
                            <i class="bi bi-people"></i> Utilisateurs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/ecommerce/index.php">
                            <i class="bi bi-arrow-left"></i> Retour au site
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Contenu principal -->
        <main class="col-md-10 ms-sm-auto px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-people"></i> Gestion des Utilisateurs</h1>
                <div class="text-muted">
                    <?php echo count($users); ?> utilisateur(s) au total
                </div>
            </div>

            <!-- Messages -->
            <?php if(isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle-fill"></i> <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if(isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Statistiques rapides -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <h6 class="card-title">Total Utilisateurs</h6>
                            <h2><?php echo count($users); ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <h6 class="card-title">Administrateurs</h6>
                            <h2><?php echo count(array_filter($users, function($u) { return $u['role'] === 'admin'; })); ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <h6 class="card-title">Utilisateurs</h6>
                            <h2><?php echo count(array_filter($users, function($u) { return $u['role'] === 'user'; })); ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Liste des utilisateurs -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Utilisateur</th>
                                    <th>Email</th>
                                    <th>Rôle</th>
                                    <th>Commandes</th>
                                    <th>Total dépensé</th>
                                    <th>Inscrit le</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($users as $user): ?>
                                    <tr <?php echo $user['id'] == $_SESSION['user_id'] ? 'class="table-warning"' : ''; ?>>
                                        <td><?php echo $user['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                            </small>
                                            <?php if($user['id'] == $_SESSION['user_id']): ?>
                                                <span class="badge bg-warning">Vous</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <form method="POST" action="" class="d-inline">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <select name="role" class="form-select form-select-sm" 
                                                        onchange="<?php echo $user['id'] == $_SESSION['user_id'] ? 'return false;' : 'this.form.submit();'; ?>"
                                                        <?php echo $user['id'] == $_SESSION['user_id'] ? 'disabled' : ''; ?>>
                                                    <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>Utilisateur</option>
                                                    <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                </select>
                                                <input type="hidden" name="update_role" value="1">
                                            </form>
                                        </td>
                                        <td>
                                            <?php if($user['total_orders'] > 0): ?>
                                                <span class="badge bg-primary"><?php echo $user['total_orders']; ?> commande(s)</span>
                                            <?php else: ?>
                                                <span class="text-muted">Aucune</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo number_format($user['total_spent'], 2); ?> €</strong>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-info" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#userModal<?php echo $user['id']; ?>">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <?php if($user['id'] != $_SESSION['user_id']): ?>
                                                    <a href="users.php?delete=<?php echo $user['id']; ?>" 
                                                       class="btn btn-sm btn-danger"
                                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Modal pour les détails de l'utilisateur -->
                                    <div class="modal fade" id="userModal<?php echo $user['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Détails - <?php echo htmlspecialchars($user['username']); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <h6>Informations personnelles</h6>
                                                    <p>
                                                        <strong>Nom complet :</strong> <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?><br>
                                                        <strong>Email :</strong> <?php echo htmlspecialchars($user['email']); ?><br>
                                                        <strong>Téléphone :</strong> <?php echo htmlspecialchars($user['phone'] ?? 'Non renseigné'); ?><br>
                                                        <strong>Rôle :</strong> 
                                                        <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                                                            <?php echo $user['role'] === 'admin' ? 'Administrateur' : 'Utilisateur'; ?>
                                                        </span>
                                                    </p>
                                                    
                                                    <hr>
                                                    
                                                    <h6>Adresse</h6>
                                                    <p>
                                                        <?php if($user['address']): ?>
                                                            <?php echo nl2br(htmlspecialchars($user['address'])); ?><br>
                                                            <?php echo htmlspecialchars($user['postal_code'] . ' ' . $user['city']); ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">Non renseignée</span>
                                                        <?php endif; ?>
                                                    </p>
                                                    
                                                    <hr>
                                                    
                                                    <h6>Statistiques</h6>
                                                    <p>
                                                        <strong>Inscrit le :</strong> <?php echo date('d/m/Y à H:i', strtotime($user['created_at'])); ?><br>
                                                        <strong>Dernière modification :</strong> <?php echo date('d/m/Y à H:i', strtotime($user['updated_at'])); ?><br>
                                                        <strong>Nombre de commandes :</strong> <?php echo $user['total_orders']; ?><br>
                                                        <strong>Total dépensé :</strong> <?php echo number_format($user['total_spent'], 2); ?> €
                                                    </p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
.sidebar {
    position: sticky;
    top: 0;
    height: 100vh;
    overflow-y: auto;
}

.sidebar .nav-link {
    color: #333;
    padding: 10px 20px;
}

.sidebar .nav-link:hover,
.sidebar .nav-link.active {
    background-color: #e9ecef;
    color: #0d6efd;
}
</style>

<?php include '../includes/footer.php'; ?>