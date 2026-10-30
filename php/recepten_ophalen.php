<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $sql = "SELECT
                recepten.id,
                recepten.naam,
                recepten.afbeelding,
                recepten.categorie,
                recepten.bereidingstijd,
                recepten.aantal_personen,
                gebruikers.naam AS gebruiker_naam
            FROM recepten
            LEFT JOIN gebruikers
                ON recepten.gebruiker_id = gebruikers.id
            ORDER BY recepten.aangemaakt_op DESC";

    $stmt = $pdo->query($sql);
    $recepten = $stmt->fetchAll();

    echo json_encode($recepten, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'De recepten konden niet worden opgehaald.'
    ]);
}
?>