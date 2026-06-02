<?php
include_once __DIR__ . '/../../../priv/db_conf_laniakea.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

try {
    $stmt = $pdo->prepare("INSERT INTO tournaments (name, mode) VALUES (?, ?)");
    $stmt->execute([
        $data['name'] ?? 'Automated Round Robin',
        $data['mode'] ?? 'round-robin'
    ]);
    
    echo json_encode(['success' => true, 'tournament_id' => $pdo->lastInsertId()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>