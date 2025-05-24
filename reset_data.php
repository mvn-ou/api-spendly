<?php
session_start([
    'cookie_lifetime' => 2592000, // 30 jours
    'cookie_secure' => false,     // À mettre à true si HTTPS
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
]);

require_once "cors.php";

// ✅ Vérification de session AVANT usage
if (!isset($_SESSION['id_utilisateur'])) {
    http_response_code(401);
    echo json_encode(["error" => "Utilisateur non connecté"]);
    exit;
}

$utilisateur_id = $_SESSION['id_utilisateur'];

require_once "config.php"; // $connexion doit être défini ici

try {
    $query = "DELETE FROM transactions WHERE utilisateur_id = :user_id";
    $stmt = $connexion->prepare($query);
    $stmt->bindParam(":user_id", $utilisateur_id, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        http_response_code(200);
        echo json_encode(["message" => "Les données ont été réinitialisées avec succès"]);
    } else {
        http_response_code(200);
        echo json_encode(["message" => "Aucune donnée à réinitialiser pour cet utilisateur"]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Erreur lors de la réinitialisation des données : " . $e->getMessage()]);
}
?>
