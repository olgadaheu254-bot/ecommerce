<?php
require_once '../config/database.php';

// Vérifier si l'utilisateur est admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /mon-ecommerce/user/login.php');
    exit;
}

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($product_id > 0) {
    try {
        // Vérifier si le produit existe
        $stmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if($product) {
            // Supprimer le produit
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            
            if($stmt->execute([$product_id])) {
                $_SESSION['success_message'] = "Le produit '" . htmlspecialchars($product['name']) . "' a été supprimé avec succès.";
            } else {
                $_SESSION['error_message'] = "Erreur lors de la suppression du produit.";
            }
        } else {
            $_SESSION['error_message'] = "Produit introuvable.";
        }
    } catch(PDOException $e) {
        $_SESSION['error_message'] = "Erreur : Ce produit ne peut pas être supprimé car il est lié à des commandes.";
    }
}

header('Location: products.php');
exit;
?>