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
    $sql = "SELECT
                c.nom AS name,
                SUM(t.montant) AS value
            FROM
                transactions t
            INNER JOIN -- Use INNER JOIN here because we only care about transactions with a category
                categories c ON t.categorie_id = c.id
            WHERE
                t.utilisateur_id = :user_id
                AND t.type = 'depense'
            GROUP BY
                c.nom
            ORDER BY
                value DESC;"; // Optional: order by amount descending

    $stmt = $connexion->prepare($sql);
    $stmt->bindParam(':user_id', $utilisateur_id, PDO::PARAM_INT);
    $stmt->execute();

    $expensesByCategory = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If no expenses are found, return an empty array
    if (!$expensesByCategory) {
        $expensesByCategory = [];
    }

    echo json_encode($expensesByCategory);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(array("error" => "Erreur de base de données : " . $e->getMessage()));
} finally {
    $connexion = null;
}
?>