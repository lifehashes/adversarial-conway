<?php
include_once __DIR__ . '/../../../priv/db_conf_laniakea.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['tournament_id'], $data['series_id'], $data['group_label'])) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit;
}

try {
    // Update the dummy 0 entries to the new real tournament ID
    $stmt = $pdo->prepare("UPDATE series_participants 
                           SET tournament_id = ? 
                           WHERE series_id = ? AND group_label = ? AND tournament_id = 0");
    $stmt->execute([$data['tournament_id'], $data['series_id'], $data['group_label']]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>