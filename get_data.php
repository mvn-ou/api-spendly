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
    echo json_encode(['success' => false, 'error' => 'Utilisateur non connecté.']);
    exit;
}

$mois = isset($_GET['mois']) ? $_GET['mois'] : null;

if (!$mois) {
    http_response_code(400);
    echo json_encode(["error" => "Paramètre 'mois' manquant"]);
    exit();
}

$monthMapping = [
    "janvier 2025" => "2025-01",
    "février 2025" => "2025-02",
    "mars 2025" => "2025-03",
    "avril 2025" => "2025-04",
    "mai 2025" => "2025-05",
    "juin 2025" => "2025-06",
    "juillet 2025" => "2025-07",
    "août 2025" => "2025-08",
    "septembre 2025" => "2025-09",
    "octobre 2025" => "2025-10",
    "novembre 2025" => "2025-11",
    "décembre 2025" => "2025-12",
    "janvier 2026" => "2026-01",
    "février 2026" => "2026-02",
    "mars 2026" => "2026-03",
    "avril 2026" => "2026-04",
    "mai 2026" => "2026-05",
];

$monthForQuery = isset($monthMapping[strtolower($mois)]) ? $monthMapping[strtolower($mois)] : null;

if (!$monthForQuery) {
    http_response_code(400);
    echo json_encode(["error" => "Mois invalide"]);
    exit();
}

try {
    // Requête principale (revenu, dépense, différence)
    $sql = "SELECT
        u.id AS utilisateur_id,
        u.nom_utilisateur AS nom_utilisateur,
        SUM(CASE WHEN t.type = 'revenu' THEN t.montant ELSE 0 END) AS revenu_total,
        SUM(CASE WHEN t.type = 'depense' THEN t.montant ELSE 0 END) AS depenses_total,
        (SUM(CASE WHEN t.type = 'revenu' THEN t.montant ELSE 0 END) - SUM(CASE WHEN t.type = 'depense' THEN t.montant ELSE 0 END)) AS difference_revenu_depenses
    FROM
        users u
    LEFT JOIN
        transactions t ON u.id = t.utilisateur_id
    WHERE
        u.id = :user_id AND DATE_FORMAT(t.date_transaction, '%Y-%m') = :mois
    GROUP BY
        u.id, u.nom_utilisateur";

    $stmt = $connexion->prepare($sql);
    $stmt->bindParam(':user_id', $_SESSION['id_utilisateur'], PDO::PARAM_INT);
    $stmt->bindParam(':mois', $monthForQuery, PDO::PARAM_STR);
    $stmt->execute();
    $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $ligne = $resultats[0] ?? null;

    // Requête supplémentaire : dépenses par catégorie
    $sqlCategories = "SELECT
                        c.nom AS categorie,
                        SUM(t.montant) AS montant_total
                    FROM transactions t
                    JOIN categories c ON t.categorie_id = c.id
                    WHERE t.utilisateur_id = :user_id
                        AND t.type = 'depense'
                        AND DATE_FORMAT(t.date_transaction, '%Y-%m') = :mois
                    GROUP BY c.nom";

    $stmtCat = $connexion->prepare($sqlCategories);
    $stmtCat->bindParam(':user_id', $_SESSION['id_utilisateur'], PDO::PARAM_INT);
    $stmtCat->bindParam(':mois', $monthForQuery, PDO::PARAM_STR);
    $stmtCat->execute();
    $depensesParCategorie = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

    $data = [
        "nom_utilisateur" => $ligne["nom_utilisateur"] ?? null,
        "depenses" => $ligne["depenses_total"] ?? 0,
        "revenues" => $ligne["revenu_total"] ?? 0,
        "difference" => $ligne["difference_revenu_depenses"] ?? 0,
        "depenses_par_categorie" => $depensesParCategorie
    ];

    http_response_code(200);
    echo json_encode($data);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Erreur de base de données : " . $e->getMessage()]);
} finally {
    $connexion = null;
}

?>
