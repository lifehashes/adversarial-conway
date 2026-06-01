<?php
include_once __DIR__ . '/../../../priv/db_conf_laniakea.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Read incoming JSON payload from frontend
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'No data provided']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Insert or locate a macro tournament context if applicable
    $tournament_id = !empty($data['tournament_id']) ? $data['tournament_id'] : null;

    // 2. Insert into 'matches' table
    $stmt = $pdo->prepare("INSERT INTO matches (
        tournament_id, match_designation, p1_glyph_name, p2_glyph_name, 
        grid_size, arena_width, arena_height, game_mode, 
        total_rounds_configured, p1_rounds_won, p2_rounds_won
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->execute([
        $tournament_id,
        $data['match_designation'] ?? null,
        $data['p1_glyph_name'],
        $data['p2_glyph_name'],
        $data['grid_size'] ?? 16,
        $data['arena_width'] ?? 640,
        $data['arena_height'] ?? 320,
        $data['game_mode'] ?? 'combative',
        $data['total_rounds_configured'] ?? 1,
        $data['p1_rounds_won'] ?? 0,
        $data['p2_rounds_won'] ?? 0
    ]);

    $match_id = $pdo->lastInsertId();

    // 3. Insert individual round parameters (seeds & performance parameters)
    if (!empty($data['rounds']) && is_array($data['rounds'])) {
        $roundStmt = $pdo->prepare("INSERT INTO match_rounds (
            match_id, round_number, round_seed, p1_final_score, p2_final_score, total_iterations
        ) VALUES (?, ?, ?, ?, ?, ?)");

        foreach ($data['rounds'] as $round) {
            $roundStmt->execute([
                $match_id,
                $round['round_number'],
                $round['round_seed'],
                $round['p1_final_score'] ?? 0,
                $round['p2_final_score'] ?? 0,
                $round['total_iterations'] ?? 0
            ]);
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'match_id' => $match_id]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>