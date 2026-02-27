<?php
session_start();

$response = array('success' => false, 'message' => '');

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    
    if($product_id > 0 && isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        $response['success'] = true;
        $response['message'] = 'Produit retiré du panier';
    } else {
        $response['message'] = 'Produit introuvable';
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>