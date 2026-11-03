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

$stmt = $pdo->query("SELECT COUNT(*) FROM recepten");
$totaalRecepten = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM gebruikers");
$totaalGebruikers = $stmt->fetchColumn();

$stmt = $pdo->query("
    SELECT recepten.id,
           recepten.naam,
           recepten.categorie,
           recepten.bereidingstijd,
           gebruikers.naam AS maker
    FROM recepten
    LEFT JOIN gebruikers
        ON recepten.gebruiker_id = gebruikers.id
    ORDER BY recepten.aangemaakt_op DESC
");

$recepten = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("
    SELECT id, naam, email, rol, geblokkeerd
    FROM gebruikers
    ORDER BY naam
");

$gebruikers = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adminpaneel | Laat Gen Z Koken</title>
    <link rel="stylesheet" href="../admin/admin.css">
</head>

<body>

<header class="admin-header">
    <h1>Laat Gen Z Koken</h1>
    <p>Beheerderspaneel</p>

    <a href="uitloggen.php" class="logout-button">Uitloggen</a>
    <a href="../pages/homepage.html" class="home-button">Terug naar website</a>
</header>

<main class="admin-container">

    <h2>Dashboard</h2>

    <?php if (isset($_GET['verwijderd'])): ?>
        <p class="success-message">Recept succesvol verwijderd!</p>
    <?php endif; ?>

    <?php if (isset($_GET['bewerkt'])): ?>
        <p class="success-message">Recept succesvol aangepast!</p>
    <?php endif; ?>

    <section class="stats">

        <div class="stat-card">
            <h3>Totaal recepten</h3>
            <p><?= (int) $totaalRecepten ?></p>
        </div>

        <div class="stat-card">
            <h3>Totaal gebruikers</h3>
            <p><?= (int) $totaalGebruikers ?></p>
        </div>

    </section>

    <section class="admin-section">

        <h2>Recepten beheren</h2>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Naam</th>
                        <th>Categorie</th>
                        <th>Bereidingstijd</th>
                        <th>Gemaakt door</th>
                        <th>Acties</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($recepten as $recept): ?>
                        <tr>
                            <td><?= (int) $recept['id'] ?></td>

                            <td>
                                <?= htmlspecialchars($recept['naam'], ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($recept['categorie'], ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td>
                                <?= (int) $recept['bereidingstijd'] ?> minuten
                            </td>

                            <td>
                                <?= htmlspecialchars($recept['maker'] ?? 'Onbekend', ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td>
                                <div class="recipe-actions">

                                    <a href="bewerken.php?id=<?= (int) $recept['id'] ?>"
                                       class="action-button">
                                        Bewerken
                                    </a>

                                    <form action="verwijderen.php"
                                          method="POST"
                                          onsubmit="return confirm('Weet je zeker dat je dit recept wilt verwijderen?');">

                                        <input type="hidden"
                                               name="csrf_token"
                                               value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                                        <input type="hidden"
                                               name="id"
                                               value="<?= (int) $recept['id'] ?>">

                                        <button type="submit" class="delete-button">
                                            Verwijderen
                                        </button>
                                    </form>

                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($recepten)): ?>
                        <tr>
                            <td colspan="6">Er zijn nog geen recepten.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </section>

    <section class="admin-section">

        <h2>Gebruikers beheren</h2>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Naam</th>
                        <th>E-mailadres</th>
                        <th>Rol</th>
                        <th>Status</th>
                        <th>Acties</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($gebruikers as $gebruiker): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($gebruiker['naam'], ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($gebruiker['email'], ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($gebruiker['rol'], ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td>
                                <?php if ($gebruiker['geblokkeerd']): ?>
                                    <span class="status blocked">Geblokkeerd</span>
                                <?php else: ?>
                                    <span class="status active">Actief</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ((int) $gebruiker['id'] !== (int) $_SESSION['gebruiker_id']): ?>

                                    <form action="admin_gebruiker_actie.php"
                                          method="POST"
                                          class="action-form">

                                        <input type="hidden"
                                               name="csrf_token"
                                               value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                                        <input type="hidden"
                                               name="gebruiker_id"
                                               value="<?= (int) $gebruiker['id'] ?>">

                                        <button type="submit"
                                                name="actie"
                                                value="<?= $gebruiker['geblokkeerd'] ? 'deblokkeren' : 'blokkeren' ?>"
                                                class="action-button">

                                            <?= $gebruiker['geblokkeerd'] ? 'Deblokkeren' : 'Blokkeren' ?>

                                        </button>

                                        <button type="submit"
                                                name="actie"
                                                value="<?= $gebruiker['rol'] === 'admin' ? 'demote' : 'promote' ?>"
                                                class="action-button role-button">

                                            <?= $gebruiker['rol'] === 'admin' ? 'Admin verwijderen' : 'Admin maken' ?>

                                        </button>

                                    </form>

                                <?php else: ?>

                                    <span class="own-account">Jouw account</span>

                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </section>

</main>

</body>
</html>