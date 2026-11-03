<?php
session_start();

require_once __DIR__ . '/../config/database.php';

if (
    !isset($_SESSION['gebruiker_id']) ||
    ($_SESSION['gebruiker_rol'] ?? '') !== 'admin'
) {
    header('Location: ../pages/inlog.html');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin.php');
    exit;
}

if (
    !isset($_POST['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])
) {
    exit('Ongeldige aanvraag.');
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    exit('Ongeldig recept.');
}

try {
    $stmt = $pdo->prepare("
        SELECT afbeelding
        FROM recepten
        WHERE id = :id
    ");

    $stmt->execute([':id' => $id]);

    $recept = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$recept) {
        exit('Recept niet gevonden.');
    }

    $stmt = $pdo->prepare("
        DELETE FROM recepten
        WHERE id = :id
    ");

    $stmt->execute([':id' => $id]);

    if (!empty($recept['afbeelding'])) {
        $bestand = basename($recept['afbeelding']);
        $afbeeldingPad = __DIR__ . '/../uploads/' . $bestand;

        if (is_file($afbeeldingPad)) {
            unlink($afbeeldingPad);
        }
    }

    header('Location: admin.php?verwijderd=1');
    exit;

} catch (PDOException $e) {
    error_log($e->getMessage());
    exit('Er ging iets mis bij het verwijderen.');
}