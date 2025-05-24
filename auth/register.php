<?php
session_start([
    'cookie_lifetime' => 2592000, // 30 jours
    'cookie_secure' => false, // Si HTTPS activé
    'cookie_httponly' => true, // Protéger les cookies de l'accès JS
    'cookie_samesite' => 'Strict', // Protection CSRF
]);

require_once "../cors.php";


    require_once "../config.php"; 

    

$donnee = json_decode(file_get_contents("php://input"), true);

if (isset($donnee["nom_utilisateur"]) && !empty($donnee["nom_utilisateur"]) && isset($donnee["email"]) && !empty($donnee["email"]) && isset($donnee["password"]) && !empty($donnee["password"])) {

//cette partie pour recupérer les données du formulaire
    $username = trim($donnee["nom_utilisateur"]);
    $email = trim($donnee["email"]);
    $password = $donnee["password"];
    // $condition_acceptees = isset($donnee["condition_acceptees"]);


//correspondance du mot de passe
// if( $password !== $password_verif) {
//     echo json_encode(["error" => "Les mots de passe ne correspondent pas."]);
//     exit();
// }

//vérifier si l'email est déjà utilisé 
$requete_verif = $connexion->prepare("
    SELECT COUNT(*) FROM users 
    WHERE email = :email OR nom_utilisateur = :username
");
$requete_verif->bindParam(":email",$email);
$requete_verif->bindParam(":username",$username);
$requete_verif->execute();
if($requete_verif->fetchColumn() > 0) {
    http_response_code(409); // Code de statut pour "Conflit"
    echo json_encode(['error' => 'Nom d\'utilisateur ou email déjà existant']);
    exit();
}

//enregistrement du nouvel utilisateur dans la base de donnée

    //hasher le mot de passe
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $requete = $connexion->prepare("
        INSERT INTO users
        (nom_utilisateur, email, mot_de_passe)
        VALUES
        (:username, :email, :password)
    ");
    //paramètre de  la requète
    $requete->bindParam(':username',$username);
    $requete->bindParam(':email',$email);
    $requete->bindParam(':password',$password_hash);

    if($requete->execute()) {
        http_response_code(201); // Code de statut pour "Créé"

        $_SESSION['id_utilisateur'] = $connexion->lastInsertId(); // Récupérer l'ID du nouvel utilisateur
        $_SESSION['nom_utilisateur'] = $username;
        $_SESSION['email'] = $email;
        $_SESSION['connected'] = true;
        echo json_encode(['message' => 'Inscription réussie et connecté', 'user_id' => $_SESSION['id_utilisateur'], 'username' => $_SESSION['nom_utilisateur'], 'email' => $_SESSION['email']]);

    }else {
        http_response_code(500); // Code de statut pour "Erreur interne du serveur"
        echo json_encode(['error' => 'Erreur lors de l\'enregistrement de l\'utilisateur']);
    }


}else {
    http_response_code(400);
    $errors = [];
    if (!isset($donnee['nom_utilisateur']) || empty($donnee['nom_utilisateur'])) $errors[] = 'Nom d\'utilisateur requis';
    if (!isset($donnee['password']) || empty($donnee['password'])) $errors[] = 'Mot de passe requis';
    if (!isset($donnee['email']) || !filter_var($donnee['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalide';
    echo json_encode(['error' => 'Données d\'inscription invalides', 'details' => $errors]);
}


?>