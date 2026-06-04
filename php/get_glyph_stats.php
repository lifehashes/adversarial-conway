<?php
// Ensure no hidden notices or warnings ruin our JSON payload formatting
error_reporting(0); 
ini_set('display_errors', 0);

include_once __DIR__ . '/../../../priv/db_conf_laniakea.php';

header('Content-Type: application/json');

if (!isset($_GET['name']) || empty(trim($_GET['name']))) {
    echo json_encode(['success' => false, 'error' => 'Missing glyph identifier']);
    exit;
}

$glyph_name = trim($_GET['name']);

try {
    // 1. Fetch the Glyph's static generation count (ITERATIONS) from the registry
    $regSql = "SELECT ITERATIONS FROM GLYPHREG WHERE BATTLE_NAME = :name1 OR CONCAT('#', ATTEMPT) = :name2 LIMIT 1";
    $regStmt = $pdo->prepare($regSql);
    $regStmt->execute([
        ':name1' => $glyph_name,
        ':name2' => $glyph_name
    ]);
    $glyphReg = $regStmt->fetch(PDO::FETCH_ASSOC);
    
    // Fallback to 1 if not found or 0 to prevent division by zero downstream
    $generations = (!empty($glyphReg) && (int)$glyphReg['ITERATIONS'] > 0) ? (int)$glyphReg['ITERATIONS'] : 1;

    // 2. Aggregate match metrics across player 1 and player 2 configurations
    $sql = "
        SELECT 
            COUNT(mr.id) as total_rounds,
            SUM(CASE WHEN m.p1_glyph_name = :name1 THEN mr.p1_final_score ELSE mr.p2_final_score END) as total_score,
            COUNT(DISTINCT m.id) as total_matches
        FROM matches m
        JOIN match_rounds mr ON m.id = mr.match_id
        WHERE m.p1_glyph_name = :name2 OR m.p2_glyph_name = :name3
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name1' => $glyph_name,
        ':name2' => $glyph_name,
        ':name3' => $glyph_name
    ]);
    
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    $rounds_played = (int)($stats['total_rounds'] ?? 0);
    $total_score = (int)($stats['total_score'] ?? 0);

    // 3. Calculate Efficiency: Points / (Rounds * Generations)
    // Guard against division by zero if a glyph has 0 rounds played
    $efficiency = 0.0;
    if ($rounds_played > 0) {
        $efficiency = $total_score / ($rounds_played * $generations);
    }

    echo json_encode([
        'success' => true,
        'rounds_played' => $rounds_played,
        'total_score' => $total_score,
        'matches_played' => (int)($stats['total_matches'] ?? 0),
        'efficiency_index' => round($efficiency, 4) // Format to 4 decimal places for precision telemetry
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database pipeline exception: ' . $e->getMessage()
    ]);
}