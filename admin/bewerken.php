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

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
}

if (!$id || $id < 1) {
    exit('Ongeldig recept.');
}

$stmt = $pdo->prepare("SELECT * FROM recepten WHERE id = :id");
$stmt->execute([':id' => $id]);
$recept = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$recept) {
    exit('Recept niet gevonden.');
}

$fout = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        exit('Ongeldige aanvraag.');
    }

    $naam = trim($_POST['naam'] ?? '');
    $categorie = trim($_POST['categorie'] ?? '');
    $bereidingstijd = filter_input(INPUT_POST, 'bereidingstijd', FILTER_VALIDATE_INT);
    $aantal_personen = filter_input(INPUT_POST, 'aantal_personen', FILTER_VALIDATE_INT);
    $ingredienten = trim($_POST['ingredienten'] ?? '');
    $bereiding = trim($_POST['bereiding'] ?? '');

    $categorieen = ['Ontbijt', 'Lunch', 'Avondeten', 'Dessert'];

    if (
        $naam === '' ||
        !in_array($categorie, $categorieen, true) ||
        !$bereidingstijd ||
        $bereidingstijd < 1 ||
        $bereidingstijd > 1440 ||
        !$aantal_personen ||
        $aantal_personen < 1 ||
        $aantal_personen > 100 ||
        $ingredienten === '' ||
        $bereiding === ''
    ) {
        $fout = 'Controleer alle velden.';
    }

    $oudeAfbeelding = $recept['afbeelding'];
    $afbeelding = $oudeAfbeelding;
    $nieuweAfbeeldingPad = null;

    if (
        $fout === '' &&
        isset($_FILES['afbeelding']) &&
        $_FILES['afbeelding']['error'] !== UPLOAD_ERR_NO_FILE
    ) {
        if ($_FILES['afbeelding']['error'] !== UPLOAD_ERR_OK) {
            $fout = 'De afbeelding kon niet worden geüpload.';
        } elseif ($_FILES['afbeelding']['size'] > 5 * 1024 * 1024) {
            $fout = 'De afbeelding mag maximaal 5 MB zijn.';
        } else {
            $types = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp'
            ];

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($_FILES['afbeelding']['tmp_name']);

            if (!isset($types[$mime])) {
                $fout = 'Gebruik JPG, PNG of WEBP.';
            } else {
                $uploadMap = __DIR__ . '/../uploads/';

                if (!is_dir($uploadMap) && !mkdir($uploadMap, 0755, true)) {
                    $fout = 'Uploadmap kon niet worden aangemaakt.';
                } else {
                    $bestandsnaam = bin2hex(random_bytes(16)) . '.' . $types[$mime];
                    $nieuweAfbeeldingPad = $uploadMap . $bestandsnaam;

                    if (move_uploaded_file(
                        $_FILES['afbeelding']['tmp_name'],
                        $nieuweAfbeeldingPad
                    )) {
                        $afbeelding = '../uploads/' . $bestandsnaam;
                    } else {
                        $fout = 'Afbeelding opslaan mislukt.';
                    }
                }
            }
        }
    }

    if ($fout === '') {
        try {
            $sql = "UPDATE recepten SET
                naam = :naam,
                afbeelding = :afbeelding,
                categorie = :categorie,
                bereidingstijd = :bereidingstijd,
                aantal_personen = :aantal_personen,
                ingredienten = :ingredienten,
                bereiding = :bereiding
                WHERE id = :id";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':naam' => $naam,
                ':afbeelding' => $afbeelding,
                ':categorie' => $categorie,
                ':bereidingstijd' => $bereidingstijd,
                ':aantal_personen' => $aantal_personen,
                ':ingredienten' => $ingredienten,
                ':bereiding' => $bereiding,
                ':id' => $id
            ]);

            if (
                $afbeelding !== $oudeAfbeelding &&
                !empty($oudeAfbeelding)
            ) {
                $oudBestand = __DIR__ . '/../uploads/' . basename($oudeAfbeelding);

                if (is_file($oudBestand)) {
                    unlink($oudBestand);
                }
            }

            header('Location: admin.php?bewerkt=1');
            exit;

        } catch (PDOException $e) {
            if ($nieuweAfbeeldingPad && is_file($nieuweAfbeeldingPad)) {
                unlink($nieuweAfbeeldingPad);
            }

            error_log($e->getMessage());
            $fout = 'Het recept kon niet worden aangepast.';
        }
    }

    $recept['naam'] = $naam;
    $recept['categorie'] = $categorie;
    $recept['bereidingstijd'] = $bereidingstijd;
    $recept['aantal_personen'] = $aantal_personen;
    $recept['ingredienten'] = $ingredienten;
    $recept['bereiding'] = $bereiding;
    $recept['afbeelding'] = $afbeelding;
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recept bewerken | Laat Gen Z Koken</title>
    <link rel="stylesheet" href="admin.css">
</head>

<body>

<main class="admin-container">

    <section class="admin-section">

        <h1>Recept bewerken</h1>

        <?php if ($fout !== ''): ?>
            <p style="color:red;">
                <?= htmlspecialchars($fout, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">

            <input type="hidden" name="id" value="<?= (int) $recept['id'] ?>">

            <input type="hidden"
                   name="csrf_token"
                   value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

            <label>Naam</label>
            <input type="text"
                   name="naam"
                   value="<?= htmlspecialchars($recept['naam'], ENT_QUOTES, 'UTF-8') ?>"
                   required>

            <br><br>

            <label>Categorie</label>
            <select name="categorie" required>
                <?php foreach (['Ontbijt', 'Lunch', 'Avondeten', 'Dessert'] as $cat): ?>
                    <option value="<?= $cat ?>"
                        <?= $recept['categorie'] === $cat ? 'selected' : '' ?>>
                        <?= $cat ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <br><br>

            <label>Bereidingstijd (minuten)</label>
            <input type="number"
                   name="bereidingstijd"
                   value="<?= (int) $recept['bereidingstijd'] ?>"
                   min="1"
                   max="1440"
                   required>

            <br><br>

            <label>Aantal personen</label>
            <input type="number"
                   name="aantal_personen"
                   value="<?= (int) $recept['aantal_personen'] ?>"
                   min="1"
                   max="100"
                   required>

            <br><br>

            <label>Ingrediënten</label>
            <textarea name="ingredienten" required><?= htmlspecialchars($recept['ingredienten'], ENT_QUOTES, 'UTF-8') ?></textarea>

            <br><br>

            <label>Bereiding</label>
            <textarea name="bereiding" required><?= htmlspecialchars($recept['bereiding'], ENT_QUOTES, 'UTF-8') ?></textarea>

            <br><br>

            <label>Huidige afbeelding</label>

            <?php if (!empty($recept['afbeelding'])): ?>
                <br>
                <img src="<?= htmlspecialchars($recept['afbeelding'], ENT_QUOTES, 'UTF-8') ?>"
                     alt="Huidige afbeelding"
                     style="width:180px; border-radius:10px;">
            <?php endif; ?>

            <br><br>

            <label>Nieuwe afbeelding (optioneel)</label>
            <input type="file"
                   name="afbeelding"
                   accept="image/jpeg,image/png,image/webp">

            <br><br>

            <button type="submit">Wijzigingen opslaan</button>

            <a href="admin.php">Annuleren</a>

        </form>

    </section>

</main>

</body>
</html>