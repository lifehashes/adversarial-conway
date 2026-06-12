<?php
include_once __DIR__ . '/../../../priv/db_conf_laniakea.php';

// Get parameters
$series_id = $_GET['series_id'] ?? null;
$phase_id  = $_GET['phase_id'] ?? null;
$group     = $_GET['group'] ?? null;

if (!$series_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing series_id']);
    exit;
}

// Start building the query
$sql = "SELECT 
            sp.glyph_name AS name, 
            g.BIN AS bin, 
            g.ITERATIONS AS gen, 
            g.PEAK AS peak, 
            g.MIN AS min, 
            g.MAX AS max 
        FROM series_participants sp
        JOIN GLYPHREG g ON sp.glyph_name = g.BATTLE_NAME
        WHERE sp.series_id = ?";

$params = [$series_id];

// Dynamically add filters if provided
if ($phase_id !== null) {
    $sql .= " AND sp.phase_id = ?";
    $params[] = $phase_id;
}

if ($group !== null) {
    $sql .= " AND sp.group_label = ?";
    $params[] = $group;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));