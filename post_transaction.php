<?php
session_start([
    'cookie_lifetime' => 2592000, // 30 jours
    'cookie_secure' => false, // Si HTTPS activé
    'cookie_httponly' => true, // Protéger les cookies de l'accès JS
    'cookie_samesite' => 'Strict', // Protection CSRF
]);

require_once "cors.php";

    require_once "config.php"; 

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_utilisateur'])) {
    echo json_encode(['success' => false, 'error' => 'Utilisateur non connecté.']);
    exit;
}

// Connexion à la base de données
require_once 'config.php'; // adapter selon ton chemin

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Données invalides.']);
    exit;
}

$type = $input['type'] ?? '';
$montant = $input['montant'] ?? 0;
$nom = $input['nom'] ?? '';
$categorie_id = $input['categorie_id'] ?? '';
$date = $input['date'] ?? '';
$user_id = $_SESSION['id_utilisateur'];

// Requête pour insérer la transaction
$sql = "INSERT INTO transactions (utilisateur_id, montant, categorie_id, date_transaction, nom , type ) 
        VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $connexion->prepare($sql);
$result = $stmt->execute([$user_id, $montant, $categorie_id, $date, $nom, $type]);

if ($result) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Erreur lors de l’enregistrement.']);
}
?>
