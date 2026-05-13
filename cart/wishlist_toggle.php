<?php
/**
 * cart/wishlist_toggle.php
 * Gere l'ajout et la suppression des favoris en AJAX
 * Retourne du JSON
 */
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Connectez-vous pour ajouter des favoris']);
    exit;
}

$user_id    = $_SESSION['user_id'];
$product_id = (int)($_POST['product_id'] ?? 0);

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Produit invalide']);
    exit;
}

// Verifier si deja en favori
$stmt = $pdo->prepare("SELECT id FROM wishlists WHERE user_id = ? AND product_id = ?");
$stmt->execute([$user_id, $product_id]);
$existing = $stmt->fetch();

if ($existing) {
    // Supprimer des favoris
    $pdo->prepare("DELETE FROM wishlists WHERE user_id = ? AND product_id = ?")->execute([$user_id, $product_id]);
    echo json_encode(['success' => true, 'action' => 'removed', 'message' => 'Retire des favoris']);
} else {
    // Ajouter aux favoris
    $pdo->prepare("INSERT INTO wishlists (user_id, product_id) VALUES (?, ?)")->execute([$user_id, $product_id]);
    echo json_encode(['success' => true, 'action' => 'added', 'message' => 'Ajoute aux favoris']);
}