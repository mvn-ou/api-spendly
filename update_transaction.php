<?php
// Configuration de la session
session_start([
    'cookie_lifetime' => 2592000,
    'cookie_secure' => false,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
]);

require_once "cors.php";

require_once "config.php";

// Vérifier la session
if (!isset($_SESSION['id_utilisateur'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Utilisateur non connecté"]);
    exit;
}

// Récupérer les données envoyées dans la requête
$input = json_decode(file_get_contents("php://input"), true);

// Vérifier si le JSON est valide
if ($input === null) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Requête JSON invalide"]);
    exit;
}

// Journaliser les données reçues pour débogage
error_log("Données reçues: " . json_encode($input));

// Valider la présence de chaque champ
$missingFields = [];
if (!isset($input['id']) || empty(trim($input['id']))) $missingFields[] = 'id';
if (!isset($input['type']) || empty(trim($input['type']))) $missingFields[] = 'type';
if (!isset($input['montant']) || $input['montant'] === '') $missingFields[] = 'montant';
if (!isset($input['nom']) || empty(trim($input['nom']))) $missingFields[] = 'nom';
if (!isset($input['categorie_id']) || $input['categorie_id'] === '') $missingFields[] = 'categorie_id';
if (!isset($input['date']) || empty(trim($input['date']))) $missingFields[] = 'date';

if (!empty($missingFields)) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Données incomplètes : " . implode(', ', $missingFields) . " manquant(s)"]);
    exit;
}

// Extraire et valider les données
$transaction_id = filter_var($input['id'], FILTER_SANITIZE_STRING);
$type = filter_var($input['type'], FILTER_SANITIZE_STRING);
$montant = filter_var($input['montant'], FILTER_VALIDATE_FLOAT);
$nom = filter_var($input['nom'], FILTER_SANITIZE_STRING);
$categorie_id = filter_var($input['categorie_id'], FILTER_VALIDATE_INT);
$date_transaction = filter_var($input['date'], FILTER_SANITIZE_STRING);
$user_id = $_SESSION['id_utilisateur'];

// Journaliser les données après validation
error_log("Données validées: transaction_id=$transaction_id, type=$type, montant=$montant, nom=$nom, categorie_id=$categorie_id, date_transaction=$date_transaction, user_id=$user_id");

// Validations supplémentaires
if (!in_array($type, ['revenu', 'depense'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Type de transaction invalide"]);
    exit;
}

if ($montant === false || $montant <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Le montant doit être supérieur à 0"]);
    exit;
}

if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date_transaction)) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Format de date invalide (YYYY-MM-DD requis)"]);
    exit;
}

if ($categorie_id === false || $categorie_id <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "ID de catégorie invalide"]);
    exit;
}

// Connexion à la base de données
try {
    // Vérifier que la catégorie existe
    $stmt = $connexion->prepare("SELECT id FROM categories WHERE id = ?");
    $stmt->execute([$categorie_id]);
    if (!$stmt->fetch()) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Catégorie inexistante"]);
        exit;
    }

    // Vérifier que la transaction appartient à l'utilisateur
    $stmt = $connexion->prepare("SELECT id FROM transactions WHERE id = ? AND utilisateur_id = ?");
    $stmt->execute([$transaction_id, $user_id]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(["success" => false, "error" => "Vous n'êtes pas autorisé à modifier cette transaction"]);
        exit;
    }

    // Mettre à jour la transaction avec categorie_id
    $stmt = $connexion->prepare("
        UPDATE transactions 
        SET type = ?, montant = ?, nom = ?, categorie_id = ?, date_transaction = ?
        WHERE id = ? AND utilisateur_id = ?
    ");
    $success = $stmt->execute([$type, $montant, $nom, $categorie_id, $date_transaction, $transaction_id, $user_id]);

    if ($success) {
        http_response_code(200);
        echo json_encode(["success" => true, "message" => "Transaction mise à jour avec succès"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Échec de la mise à jour de la transaction"]);
    }
} catch (PDOException $e) {
    error_log("Erreur PDO: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Erreur serveur: " . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Erreur générale: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Erreur inattendue: " . $e->getMessage()]);
}
?>