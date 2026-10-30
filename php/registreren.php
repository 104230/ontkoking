<?php
session_start();
require_once __DIR__ . '/../config/database.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/registreren.html');
    exit;
}
$naam = trim($_POST['naam'] ?? '');
$email = trim($_POST['email'] ?? '');
$wachtwoord = $_POST['wachtwoord'] ?? '';
if ($naam === '' || $email === '' || $wachtwoord === '') {
    exit('Vul alle velden in.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit('Vul een geldig e-mailadres in.');
}
if (strlen($wachtwoord) < 8) {
    exit('Je wachtwoord moet minimaal 8 tekens bevatten.');
}
$wachtwoordHash = password_hash($wachtwoord, PASSWORD_DEFAULT);
try {
    $sql = 'INSERT INTO gebruikers (naam, email, wachtwoord)
            VALUES (:naam, :email, :wachtwoord)';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':naam' => $naam,
        ':email' => $email,
        ':wachtwoord' => $wachtwoordHash
    ]);
    session_regenerate_id(true);
    $_SESSION['gebruiker_id'] = (int) $pdo->lastInsertId();
    $_SESSION['gebruiker_naam'] = $naam;
    header('Location: ../pages/homepage.html');
    exit;
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        exit('Dit e-mailadres is al geregistreerd.');
    }
    error_log($e->getMessage());
    http_response_code(500);
    exit('Registreren is niet gelukt. Probeer het later opnieuw.');
}
?>