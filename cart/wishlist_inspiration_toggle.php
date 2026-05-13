<?php
/**
 * cart/wishlist_inspiration_toggle.php
 * Gere l'ajout et la suppression des inspirations en favoris en AJAX
 */
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Connectez-vous pour ajouter des favoris']);
    exit;
}

$user_id   = $_SESSION['user_id'];
$modele_id = (int)($_POST['modele_id'] ?? 0);

if (!$modele_id) {
    echo json_encode(['success' => false, 'message' => 'Inspiration invalide']);
    exit;
}

// Verifier si deja en favori
$stmt = $pdo->prepare("SELECT id FROM wishlist_inspirations WHERE user_id = ? AND modele_id = ?");
$stmt->execute([$user_id, $modele_id]);
$existing = $stmt->fetch();

if ($existing) {
    $pdo->prepare("DELETE FROM wishlist_inspirations WHERE user_id = ? AND modele_id = ?")->execute([$user_id, $modele_id]);
    echo json_encode(['success' => true, 'action' => 'removed', 'message' => 'Retire des favoris']);
} else {
    $pdo->prepare("INSERT INTO wishlist_inspirations (user_id, modele_id) VALUES (?, ?)")->execute([$user_id, $modele_id]);
    echo json_encode(['success' => true, 'action' => 'added', 'message' => 'Ajoute aux favoris']);
}