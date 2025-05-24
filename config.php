

<?php
require_once 'cors.php';

$host = "localhost";
$user = "root";
$password = "";
$dbname = "spendly";

// $host = "sql101.yzz.me";
// $user = "yzzme_39069521";
// $password = "rQRZKmJXhsbY";
// $dbname = "yzzme_39069521_spendly";

try {
    $connexion = new PDO("mysql:host=$host;dbname=$dbname", $user, $password);
    $connexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de connexion à la base de données']);
    exit;
}
?>