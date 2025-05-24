<?php
session_start([
    'cookie_lifetime' => 2592000, // 30 jours
    'cookie_secure' => false, // ← FAUX en local (true en prod HTTPS)
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
]);

require_once "../cors.php";

require_once "../config.php";

$id_utilisateur = $_SESSION['id_utilisateur'] ?? null;

if ($id_utilisateur) {
    echo json_encode([
        'connected' => true,
        'user_id' => $id_utilisateur,
        'username' => $_SESSION['nom_utilisateur'] ?? null
    ]);
} else {
    http_response_code(401);
    echo json_encode([
        'connected' => false,
        'error' => 'Non autorisé. Veuillez vous connecter.'
    ]);
}
?>
