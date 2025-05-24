<?php
session_start([
    'cookie_lifetime' => 2592000,
    'cookie_secure' => false,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
]);

require_once "cors.php";

if (!isset($_SESSION['id_utilisateur'])) {
    http_response_code(401);
    echo json_encode(["error" => "Utilisateur non connecté"]);
    exit;
}

require_once "config.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['transaction_id'])) {
    http_response_code(400);
    echo json_encode(["error" => "ID de transaction manquant"]);
    exit;
}

$transaction_id = $data['transaction_id'];
$utilisateur_id = $_SESSION['id_utilisateur'];

try {
    // Vérifie que la transaction appartient bien à cet utilisateur
    $check = $connexion->prepare("SELECT id FROM transactions WHERE id = :id AND utilisateur_id = :uid");
    $check->execute([
        ':id' => $transaction_id,
        ':uid' => $utilisateur_id
    ]);

    if ($check->rowCount() === 0) {
        http_response_code(403);
        echo json_encode(["error" => "Transaction non trouvée ou non autorisée"]);
        exit;
    }

    $stmt = $connexion->prepare("DELETE FROM transactions WHERE id = :id AND utilisateur_id = :uid");
    $stmt->execute([
        ':id' => $transaction_id,
        ':uid' => $utilisateur_id
    ]);

    http_response_code(200);
    echo json_encode(["message" => "Transaction supprimée avec succès"]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Erreur serveur : " . $e->getMessage()]);
}
