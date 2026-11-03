<?php
session_start();

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['gebruiker_id'])) {
    header('Location: ../pages/inlog.html');
    exit;
}

if (($_SESSION['gebruiker_rol'] ?? '') !== 'admin') {
    header('Location: ../pages/homepage.html');
    exit;
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin.php');
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';

if (
    !isset($_SESSION['csrf_token']) ||
    !is_string($csrfToken) ||
    !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    http_response_code(403);
    exit('Ongeldige aanvraag. Vernieuw de pagina en probeer opnieuw.');
}


$gebruikerId = filter_input(INPUT_POST, 'gebruiker_id', FILTER_VALIDATE_INT);
$actie = $_POST['actie'] ?? '';

if (!$gebruikerId || !is_string($actie)) {
    header('Location: admin.php');
    exit;
}


if ($gebruikerId === (int) $_SESSION['gebruiker_id']) {
    exit('Je kunt je eigen account niet aanpassen.');
}

try {
    
    $stmt = $pdo->prepare(
        'SELECT id, rol FROM gebruikers WHERE id = :id'
    );

    $stmt->execute(['id' => $gebruikerId]);
    $doelGebruiker = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doelGebruiker) {
        header('Location: admin.php');
        exit;
    }

    switch ($actie) {
        case 'blokkeren':
            $stmt = $pdo->prepare(
                'UPDATE gebruikers
                 SET geblokkeerd = 1
                 WHERE id = :id'
            );

            $stmt->execute(['id' => $gebruikerId]);
            break;

        case 'deblokkeren':
            $stmt = $pdo->prepare(
                'UPDATE gebruikers
                 SET geblokkeerd = 0
                 WHERE id = :id'
            );

            $stmt->execute(['id' => $gebruikerId]);
            break;

        case 'promote':
            $stmt = $pdo->prepare(
                "UPDATE gebruikers
                 SET rol = 'admin'
                 WHERE id = :id"
            );

            $stmt->execute(['id' => $gebruikerId]);
            break;

        case 'demote':
            
            if ($doelGebruiker['rol'] === 'admin') {
                $stmt = $pdo->query(
                    "SELECT COUNT(*)
                     FROM gebruikers
                     WHERE rol = 'admin'"
                );

                $aantalAdmins = (int) $stmt->fetchColumn();

                if ($aantalAdmins <= 1) {
                    exit('Je kunt de laatste admin niet verwijderen.');
                }
            }

            $stmt = $pdo->prepare(
                "UPDATE gebruikers
                 SET rol = 'gebruiker'
                 WHERE id = :id"
            );

            $stmt->execute(['id' => $gebruikerId]);
            break;

        default:
            http_response_code(400);
            exit('Ongeldige actie.');
    }

    header('Location: admin.php');
    exit;

} catch (PDOException $e) {
    error_log($e->getMessage());

    http_response_code(500);
    exit('Er is iets misgegaan. Probeer het opnieuw.');
}