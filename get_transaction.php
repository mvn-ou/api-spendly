<?php
session_start([
    'cookie_lifetime' => 2592000, // 30 jours
    'cookie_secure' => false, // false en local, true en prod avec HTTPS
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
]);
require_once "cors.php";

require_once "config.php";

try {
    // Récupérer toutes les transactions en fonction de l'id de l'utilisateur connecté
    $requeteTransactions = $connexion->prepare("
        SELECT t.*, c.nom AS categorie
        FROM transactions t
        JOIN categories c ON t.categorie_id = c.id
        WHERE t.utilisateur_id = :user_id
        ORDER BY t.id DESC;
    ");

    if (!$requeteTransactions->execute(['user_id' => $_SESSION['id_utilisateur']])) {
        http_response_code(500);
        echo json_encode(['error' => "Erreur lors de l'exécution de la requête des transactions"]);
        return;
    }

    $categories = $requeteTransactions->fetchAll(PDO::FETCH_ASSOC);

    // Envoyer la réponse JSON
    http_response_code(200);
    echo json_encode($categories);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => "Connexion échouée: " . $e->getMessage()]);
}
?>