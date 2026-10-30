<?php
require_once __DIR__ . '/../config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit('Ongeldig recept.');
}

$stmt = $pdo->prepare(
    "SELECT recepten.*, gebruikers.naam AS gebruiker_naam
     FROM recepten
     LEFT JOIN gebruikers ON recepten.gebruiker_id = gebruikers.id
     WHERE recepten.id = :id"
);

$stmt->execute(['id' => $id]);
$recept = $stmt->fetch();

if (!$recept) {
    http_response_code(404);
    exit('Dit recept bestaat niet.');
}

function veilig($waarde) {
    return htmlspecialchars((string) $waarde, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= veilig($recept['naam']) ?> - Ontkoking</title>
    <link rel="stylesheet" href="../css/homepage.css">

    <style>
        .recept-detail {
            max-width: 850px;
            margin: 35px auto;
            padding: 25px;
        }

        .recept-detail img {
            width: 100%;
            max-height: 500px;
            object-fit: contain;
            background: #f5f5f5;
            border-radius: 15px;
        }

        .recept-detail h1 {
            font-size: 36px;
            margin-bottom: 10px;
        }

        .recept-info {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin: 20px 0;
        }

        .recept-detail h2 {
            margin-top: 30px;
        }

        .recept-tekst {
            white-space: pre-line;
            line-height: 1.8;
        }

        .terug {
            display: inline-block;
            margin-bottom: 20px;
            color: inherit;
        }
    </style>
</head>
<body>
    <main class="recept-detail">
        <a class="terug" href="../pages/homepage.html">← Terug naar recepten</a>

        <h1><?= veilig($recept['naam']) ?></h1>

        <?php if (!empty($recept['afbeelding'])): ?>
            <img
                src="../<?= veilig($recept['afbeelding']) ?>"
                alt="<?= veilig($recept['naam']) ?>"
            >
        <?php endif; ?>

        <div class="recept-info">
            <span>🍽️ <?= veilig($recept['categorie']) ?></span>
            <span>⏱️ <?= veilig($recept['bereidingstijd']) ?> minuten</span>
            <span>👥 <?= veilig($recept['aantal_personen']) ?> personen</span>
        </div>

        <?php if (!empty($recept['gebruiker_naam'])): ?>
            <p>Toegevoegd door <?= veilig($recept['gebruiker_naam']) ?></p>
        <?php endif; ?>

        <h2>Ingrediënten</h2>
        <div class="recept-tekst"><?= veilig($recept['ingredienten']) ?></div>

        <h2>Bereiding</h2>
        <div class="recept-tekst"><?= veilig($recept['bereiding']) ?></div>
    </main>
</body>
</html>