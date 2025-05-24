<?php
session_start([
    'cookie_lifetime' => 2592000, // 30 jours
    'cookie_secure' => false, // false en local, true en prod avec HTTPS
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
]);
require_once "../cors.php";

// Détruire toutes les variables de session
$_SESSION = [];

// Supprimer le cookie de session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Détruire la session
session_destroy();

// Renvoyer une réponse JSON
echo json_encode(['message' => 'Déconnexion réussie'], JSON_UNESCAPED_UNICODE);
?>