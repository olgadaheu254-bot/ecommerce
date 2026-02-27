<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/database.php';

// Initialiser le panier s'il n'existe pas
if(!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

$response = array('success' => false, 'message' => '', 'cart_count' => 0);

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    if($product_id > 0 && $quantity > 0) {
        // Vérifier que le produit existe
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND active = 1");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if($product) {
            // Vérifier le stock
            if($product['stock'] >= $quantity) {
                // Ajouter au panier
                if(isset($_SESSION['cart'][$product_id])) {
                    $_SESSION['cart'][$product_id] += $quantity;
                } else {
                    $_SESSION['cart'][$product_id] = $quantity;
                }
                
                $response['success'] = true;
                $response['message'] = 'Produit ajouté au panier';
                $response['cart_count'] = count($_SESSION['cart']);
            } else {
                $response['message'] = 'Stock insuffisant';
            }
        } else {
            $response['message'] = 'Produit introuvable';
        }
    } else {
        $response['message'] = 'Données invalides';
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>