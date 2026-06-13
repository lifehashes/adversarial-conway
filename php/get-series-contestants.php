<?php
// Debugging: Force PHP to show errors in the output so we can see them in the browser
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

// 2. Use an array to store additional conditions
$conditions = [];

if ($phase_id !== null) {
    $conditions[] = "sp.phase_id = ?";
    $params[] = $phase_id;
}

if ($group !== null) {
    $conditions[] = "sp.group_label = ?";
    $params[] = $group;
}

// 3. If there are conditions, append them with " AND "
if (count($conditions) > 0) {
    $sql .= " AND " . implode(" AND ", $conditions);
}

// 4. Add the ORDER BY clause
$sql .= " ORDER BY sp.id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));