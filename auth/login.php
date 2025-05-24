<?php
session_start([
    'cookie_lifetime' => 2592000, // 30 jours
    'cookie_secure' => false, // Si HTTPS activé
    'cookie_httponly' => true, // Protéger les cookies de l'accès JS
    'cookie_samesite' => 'Strict', // Protection CSRF
]);

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type"); // Peut être problématique avec FormData
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header('Content-Type: application/json');

// Gérer les requêtes OPTIONS préflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "../config.php"; 






try {
    $donnee = json_decode(file_get_contents("php://input"), true);
    if (!$donnee) {
        throw new Exception("Données JSON invalides");
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(["message" => "Données JSON invalides"]);
    exit;
}

if (!empty($donnee['email']) && !empty($donnee['password'])) {
    $email = trim($donnee['email']);
    $password = $donnee['password'];

    $requete = $connexion->prepare("
        SELECT id, nom_utilisateur, email, mot_de_passe
        FROM users
        WHERE email = :email OR nom_utilisateur = :email
    ");
    $requete->bindParam(':email', $email);
    $requete->bindParam(':email', $email);
    $requete->execute();
    $utilisateur = $requete->fetch(PDO::FETCH_ASSOC);

    if ($utilisateur && password_verify($password, $utilisateur['mot_de_passe'])) {
        $_SESSION['id_utilisateur'] = $utilisateur['id'];
        $_SESSION['nom_utilisateur'] = $utilisateur['nom_utilisateur'];
        $_SESSION['email'] = $utilisateur['email'];
        $_SESSION["connected"] = true;

        http_response_code(200);
        echo json_encode([
            "message" => "Connexion réussie",
            "id_utilisateur" => $utilisateur['id'],
            "nom_utilisateur" => $utilisateur['nom_utilisateur'],
            "email" => $utilisateur['email']
        ]);
    } else {
        http_response_code(401);
        echo json_encode(["message" => "Email ou mot de passe incorrect"]);
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "Veuillez remplir tous les champs"]);
}
?>
