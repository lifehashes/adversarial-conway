<?php
include_once __DIR__ . '/../../../priv/db_conf_laniakea.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['tournament_id'], $data['series_id'], $data['group_label'])) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit;
}

try {
    // Update tournament_id and sync MODE from GLYPHREG where glyph_name matches BATTLE_NAME
    $stmt = $pdo->prepare("UPDATE series_participants sp
                           LEFT JOIN GLYPHREG g ON sp.glyph_name = g.BATTLE_NAME
                           SET sp.tournament_id = ?,
                               sp.MODE = g.MODE
                           WHERE sp.series_id = ? 
                             AND sp.group_label = ? 
                             AND sp.tournament_id = 0");
                             
    $stmt->execute([$data['tournament_id'], $data['series_id'], $data['group_label']]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>