<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/toevoeg.html');
    exit;
}

if (empty($_SESSION['gebruiker_id'])) {
    header('Location: ../pages/inlog.html');
    exit;
}

$naam = trim($_POST['naam'] ?? '');
$categorie = trim($_POST['categorie'] ?? '');
$bereidingstijd = filter_input(INPUT_POST, 'bereidingstijd', FILTER_VALIDATE_INT);
$aantal_personen = filter_input(INPUT_POST, 'aantal_personen', FILTER_VALIDATE_INT);
$ingredienten = trim($_POST['ingredienten'] ?? '');
$bereiding = trim($_POST['bereiding'] ?? '');

$toegestaneCategorieen = ['Ontbijt', 'Lunch', 'Avondeten', 'Dessert'];

if (
    $naam === '' ||
    !in_array($categorie, $toegestaneCategorieen, true) ||
    !$bereidingstijd || $bereidingstijd < 1 || $bereidingstijd > 1440 ||
    !$aantal_personen || $aantal_personen < 1 || $aantal_personen > 100 ||
    $ingredienten === '' ||
    $bereiding === ''
) {
    exit('Controleer alle velden en probeer opnieuw.');
}

$afbeeldingPad = null;
$uploadMap = __DIR__ . '/../uploads/';

if (isset($_FILES['afbeelding']) && $_FILES['afbeelding']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['afbeelding']['error'] !== UPLOAD_ERR_OK) {
        exit('De foto kon niet worden geüpload.');
    }

    if ($_FILES['afbeelding']['size'] > 5 * 1024 * 1024) {
        exit('De foto mag maximaal 5 MB zijn.');
    }

    $toegestaneTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($_FILES['afbeelding']['tmp_name']);

    if (!isset($toegestaneTypes[$mimeType])) {
        exit('Gebruik een JPG-, PNG- of WEBP-afbeelding.');
    }

    if (!is_dir($uploadMap) && !mkdir($uploadMap, 0755, true)) {
        exit('De uploadmap kon niet worden aangemaakt.');
    }

    $bestandsnaam = bin2hex(random_bytes(16)) . '.' . $toegestaneTypes[$mimeType];
    $bestemmingsPad = $uploadMap . $bestandsnaam;

    if (!move_uploaded_file($_FILES['afbeelding']['tmp_name'], $bestemmingsPad)) {
        exit('De foto kon niet worden opgeslagen.');
    }

    $afbeeldingPad = '../uploads/' . $bestandsnaam;
}

try {
    $sql = 'INSERT INTO recepten
        (gebruiker_id, naam, afbeelding, categorie, bereidingstijd,
         aantal_personen, ingredienten, bereiding)
        VALUES
        (:gebruiker_id, :naam, :afbeelding, :categorie, :bereidingstijd,
         :aantal_personen, :ingredienten, :bereiding)';

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':gebruiker_id' => $_SESSION['gebruiker_id'],
        ':naam' => $naam,
        ':afbeelding' => $afbeeldingPad,
        ':categorie' => $categorie,
        ':bereidingstijd' => $bereidingstijd,
        ':aantal_personen' => $aantal_personen,
        ':ingredienten' => $ingredienten,
        ':bereiding' => $bereiding
    ]);

    header('Location: ../pages/homepage.html?opgeslagen=1');
    exit;

} catch (PDOException $e) {
    if ($afbeeldingPad !== null) {
        $opgeslagenBestand = __DIR__ . '/../uploads/' . basename($afbeeldingPad);
        if (is_file($opgeslagenBestand)) {
            unlink($opgeslagenBestand);
        }
    }

    error_log($e->getMessage());
    http_response_code(500);
    exit('Het recept kon niet worden opgeslagen. Probeer het opnieuw.');
}
?>
