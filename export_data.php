<?php
require_once "cors.php";

session_start();

require_once "config.php"; // Assuming this connects to your database

// Check if the user is connected
if (!isset($_SESSION['id_utilisateur'])) {
    echo json_encode(['success' => false, 'error' => 'Utilisateur non connecté.']);
    exit;
}

$utilisateur_id = $_SESSION['id_utilisateur'];

try {
    // Query to get monthly revenues, expenses, and their difference
    $sql = "SELECT
                DATE_FORMAT(t.date_transaction, '%Y-%m') AS mois,
                SUM(CASE WHEN t.type = 'revenu' THEN t.montant ELSE 0 END) AS revenus,
                SUM(CASE WHEN t.type = 'depense' THEN t.montant ELSE 0 END) AS depenses,
                SUM(CASE WHEN t.type = 'revenu' THEN t.montant ELSE 0 END) - SUM(CASE WHEN t.type = 'depense' THEN t.montant ELSE 0 END) AS difference
            FROM
                transactions t
            WHERE
                t.utilisateur_id = :user_id
            GROUP BY
                DATE_FORMAT(t.date_transaction, '%Y-%m')
            ORDER BY
                mois";

    $stmt = $connexion->prepare($sql);
    $stmt->bindParam(':user_id', $utilisateur_id, PDO::PARAM_INT);
    $stmt->execute();

    $monthlyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Query to get monthly expenses by category
    $SqlDepenseParCategorie = "SELECT
                                DATE_FORMAT(t.date_transaction, '%Y-%m') AS mois,
                                c.nom AS categorie,
                                SUM(t.montant) AS montant
                            FROM
                                transactions t
                            INNER JOIN
                                categories c ON t.categorie_id = c.id
                            WHERE
                                t.utilisateur_id = :user_id
                                AND t.type = 'depense'
                            GROUP BY
                                DATE_FORMAT(t.date_transaction, '%Y-%m'), c.nom
                            ORDER BY
                                mois, categorie";

    $stmtDepenseParCategorie = $connexion->prepare($SqlDepenseParCategorie);
    $stmtDepenseParCategorie->bindParam(':user_id', $utilisateur_id, PDO::PARAM_INT);
    $stmtDepenseParCategorie->execute();

    $monthlyExpensesByCategory = $stmtDepenseParCategorie->fetchAll(PDO::FETCH_ASSOC);

    // Query to get monthly revenues by category
    $sqlRevenuesByCategory = "SELECT
                                DATE_FORMAT(t.date_transaction, '%Y-%m') AS mois,
                                c.nom AS categorie,
                                SUM(t.montant) AS montant
                            FROM
                                transactions t
                            INNER JOIN
                                categories c ON t.categorie_id = c.id
                            WHERE
                                t.utilisateur_id = :user_id
                                AND t.type = 'revenu'
                            GROUP BY
                                DATE_FORMAT(t.date_transaction, '%Y-%m'), c.nom
                            ORDER BY
                                mois, categorie";

    $stmtRevenuesByCategory = $connexion->prepare($sqlRevenuesByCategory);
    $stmtRevenuesByCategory->bindParam(':user_id', $utilisateur_id, PDO::PARAM_INT);
    $stmtRevenuesByCategory->execute();

    $monthlyRevenuesByCategory = $stmtRevenuesByCategory->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'monthlyStats' => $monthlyStats,
        'monthlyExpensesByCategory' => $monthlyExpensesByCategory,
        'monthlyRevenuesByCategory' => $monthlyRevenuesByCategory
    ]);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(array("error" => "Erreur de base de données : " . $e->getMessage()));
} finally {
    $connexion = null;
}
?>
