<?php
require_once '../config/database.php';
$page_title = 'Mon Profil - MonShop';

// Vérifier si l'utilisateur est connecté
if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Récupérer les informations de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Récupérer les commandes
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$recent_orders = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="container my-5">
    <h1 class="mb-4"><i class="bi bi-person-circle"></i> Mon Profil</h1>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Informations personnelles</h5>
                </div>
                <div class="card-body">
                    <p><strong>Nom :</strong> <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
                    <p><strong>Username :</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                    <p><strong>Email :</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><strong>Téléphone :</strong> <?php echo htmlspecialchars($user['phone'] ?? 'Non renseigné'); ?></p>
                    <p><strong>Membre depuis :</strong> <?php echo date('d/m/Y', strtotime($user['created_at'])); ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Mes commandes récentes</h5>
                </div>
                <div class="card-body">
                    <?php if(count($recent_orders) > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>N° Commande</th>
                                    <th>Date</th>
                                    <th>Montant</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recent_orders as $order): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></td>
                                        <td><?php echo number_format($order['total_amount'], 2); ?> €</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>Vous n'avez pas encore de commandes.</p>
                        <a href="/ecommerce/products/index.php" class="btn btn-primary">Commencer à acheter</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>