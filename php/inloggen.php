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
    'SELECT id, naam, email, wachtwoord, rol, geblokkeerd
     FROM gebruikers
     WHERE email = :email'
);

$stmt->execute(['email' => $email]);
$gebruiker = $stmt->fetch();

if (
    !$gebruiker ||
    !password_verify($wachtwoord, $gebruiker['wachtwoord'])
) {
    header('Location: ../pages/inlog.html?error=ongeldig');
    exit;
}

if ((int) $gebruiker['geblokkeerd'] === 1) {
    header('Location: ../pages/inlog.html?error=geblokkeerd');
    exit;
}


session_regenerate_id(true);
$_SESSION['gebruiker_id'] = $gebruiker['id'];
$_SESSION['gebruiker_naam'] = $gebruiker['naam'];
$_SESSION['gebruiker_rol'] = $gebruiker['rol'];

if ($gebruiker['rol'] === 'admin') {
    header("Location: ../admin/admin.php");
} else {
    header("Location: ../pages/homepage.html");
}
exit;
?>