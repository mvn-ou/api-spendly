<?php
session_start([
    'cookie_lifetime' => 2592000,
    'cookie_secure' => false,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
]);

require_once "cors.php";

require_once "config.php";

if (!isset($_SESSION['id_utilisateur'])) {
    http_response_code(401);
    echo json_encode(["error" => "Utilisateur non connecté"]);
    exit;
}

$user_id = $_SESSION['id_utilisateur'];

try {
    $stmt = $connexion->prepare("
        SELECT t.id, t.type, t.montant, t.nom, t.categorie_id, c.nom AS categorie, t.date_transaction
        FROM transactions t
        LEFT JOIN categories c ON t.categorie_id = c.id
        WHERE t.utilisateur_id = ?
        ORDER BY t.date_transaction DESC
        LIMIT 6
    ");
    $stmt->execute([$user_id]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($transactions);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Erreur serveur: " . $e->getMessage()]);
}
?>