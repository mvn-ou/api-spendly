<?php
session_start();


require_once "cors.php";

require_once "config.php"; // Assuming this connects to your database

// Check if the user is connected
if (!isset($_SESSION['id_utilisateur'])) {
    echo json_encode(['success' => false, 'error' => 'Utilisateur non connecté.']);
    exit;
}

$utilisateur_id = $_SESSION['id_utilisateur'];

try {
    $sql = "SELECT cree_le FROM users WHERE id = :user_id";
    $stmt = $connexion->prepare($sql);
    $stmt->bindParam(':user_id', $utilisateur_id, PDO::PARAM_INT);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $dateInscription = new DateTime($user['cree_le']);
        $aujourdHui = new DateTime();
        $anciennete = $aujourdHui->diff($dateInscription)->days;

        echo json_encode(['success' => true, 'anciennete' => $anciennete]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Utilisateur non trouvé.']);
    }

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(array("error" => "Erreur de base de données : " . $e->getMessage()));
} finally {
    $connexion = null;
}
?>
