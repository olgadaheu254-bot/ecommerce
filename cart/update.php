<?php
session_start();
require_once '../config/database.php';

$response = array('success' => false, 'message' => '');

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
    
    if($product_id > 0 && $quantity > 0) {
        // Vérifier le stock
        $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if($product && $product['stock'] >= $quantity) {
            $_SESSION['cart'][$product_id] = $quantity;
            $response['success'] = true;
            $response['message'] = 'Quantité mise à jour';
        } else {
            $response['message'] = 'Stock insuffisant';
        }
    } else {
        $response['message'] = 'Données invalides';
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>