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
    // Récupérer toutes les catégories distinctes
    $requeteCategories = $connexion->prepare("
        SELECT DISTINCT c.nom, c.id
        FROM categories c
        ORDER BY c.id;
    ");

    if (!$requeteCategories->execute()) {
        http_response_code(500);
        echo json_encode(['error' => "Erreur lors de l'exécution de la requête des catégories"]);
        return;
    }

    $categories = $requeteCategories->fetchAll(PDO::FETCH_ASSOC);

    // Envoyer la réponse JSON
    http_response_code(200);
    echo json_encode($categories);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => "Connexion échouée: " . $e->getMessage()]);
}
?>