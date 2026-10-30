<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/inlog.html');
    exit;
}

$email = trim($_POST['email'] ?? '');
$wachtwoord = $_POST['password'] ?? '';

if ($email === '' || $wachtwoord === '') {
    header('Location: ../pages/inlog.html?error=leeg');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT id, naam, email, wachtwoord FROM gebruikers WHERE email = :email'
);
$stmt->execute(['email' => $email]);
$gebruiker = $stmt->fetch();

if (!$gebruiker || !password_verify($wachtwoord, $gebruiker['wachtwoord'])) {
    header('Location: ../pages/inlog.html?error=ongeldig');
    exit;
}

session_regenerate_id(true);
$_SESSION['gebruiker_id'] = (int) $gebruiker['id'];
$_SESSION['gebruiker_naam'] = $gebruiker['naam'];

header('Location: ../pages/homepage.html');
exit;
?>
