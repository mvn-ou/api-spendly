<?php
// cors.php

// Définir les en-têtes par défaut pour permettre les requêtes cross-origin
header("Access-Control-Allow-Origin: https://spendly-je.netlify.app");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type"); // Peut être problématique avec FormData
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header('Content-Type: application/json');

// Gérer les requêtes OPTIONS préflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
?>