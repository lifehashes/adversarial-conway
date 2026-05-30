<?php
include_once __DIR__ . '/../../priv/db_conf_laniakea.php';

// Fetch all available Conway Glyphs from the database
$stmt = $pdo->query("SELECT BATTLE_NAME, BIN, ITERATIONS as GENERATIONS, PEAK, MAX, MIN FROM GLYPHREG ORDER BY BATTLE_NAME ASC");
$glyphs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HASHWAR ENGINE :: v200</title>
    <style></style>
    <link rel="stylesheet" href="styles.css">
    <SCRIPT SRC="js/control.js"></SCRIPT>
	<SCRIPT SRC="js/gol.js"></SCRIPT>
	<SCRIPT SRC="js/sha256.js"></SCRIPT>
</head>
<body>

    <div class="outer-frame">

    <div id="gamestatus-container" style="display: flex; flex-direction: column; align-items: center; margin-bottom: 0px;">
        <div id="gamestatus" style="font-family: 'Courier New', monospace; color: var(--frame-grey); margin-bottom: 0px;">
            SYSTEM READY // SELECT ROUNDS
        </div>
        <div style="font-family: 'Courier New', monospace;">
            Round <span id="current-round-display">1</span>/<span id="total-rounds-display">1</span>
        </div>
        <div style="font-family: 'Courier New', monospace; font-size:0.55rem;">
            Seed: <span id="round-seed" >-</span>
        </div>
        <div id="match-tally" style="margin-top: 0px; margin-bottom:20px; font-size: 1.5rem; color: #fff; display: block;">
            
            <span id="high-score-p1" style="color:#404040;">◀</span>
            [<span id="rounds-won-p1">0</span>]
            <span id="match-p1" style="color: var(--accent-green);">0</span>
             : <span id="match-p2" style="color: var(--accent-green);">0</span> 
            [<span id="rounds-won-p2">0</span>]
            <span id="high-score-p2" style="color:#404040;">▶</span>
        </div>
    </div>

        <div class="duel-stage">
            
            <div class="player-box p1-box">
                <div class="readout">
                    <h3 style="margin:0; color: var(--frame-grey);"> 
                        <span id="name1">UNIT_01</span>                                              
                    </h3>
                    <div class="stat-line" style="color: var(--accent-green);"><span>SCORE</span> <span id="points1">0</span></div>
                    <div class="stat-line"><span>ITER</span> <span id="iteration1">0</span></div>
                </div>

                <div class="glyph-preview-container p1-layout">
                    <div class="glyph-stats">
                        <div class="stat-item">GEN <span id="spec-gen1">0</span></div>
                        <div class="stat-item">PEAK <span id="spec-peak1">0</span></div>
                        <div class="stat-item">MAX <span id="spec-max1">0</span></div>
                        <div class="stat-item">MIN <span id="spec-min1">0</span></div>
                    </div>

                    <canvas id="canvas1" class="glyph-canvas"></canvas>  
                </div>

                <div class="hash-readout" style="opacity: 0.6;">
                    <div class="stat-line" style="font-size: 0.55rem;"><span>ORIGIN</span> <span id="originHash1">0x...</span></div>
                    <div class="stat-line" style="font-size: 0.55rem; color: var(--accent-green);"><span>CURRENT</span> <span id="currentHash1">0x...</span></div>
                </div>

                <div class="profile-label">Live Cell Profile</div>
                <canvas id="cellProfile1" class="cell-profile-container"></canvas>

                <div class="profile-label">Match History (Round Wins)</div>
                <canvas id="roundHistory1" class="cell-profile-container"></canvas>

                <div class="profile-label">Charge Distribution (Histogram)</div>
                <canvas id="chargeHistogram1" class="cell-profile-container" style="height: 30px;"></canvas>

                <div class="profile-label">TOURNAMENT NETWORK</div>
                <canvas id="tournamentPolygon" width="260" height="300" style="margin-top:10px;"></canvas>
            </div>

            <div class="arena-container">
                <canvas id="canvasA" width="640" height="320"></canvas>
                <div class="analytics-tab">
                    <h3>LIVE PERFORMANCE (SCORE: THICK | DELTA: THIN)</h3>
                    <canvas id="unifiedChart" width="600" height="200"></canvas>
                </div>
            </div>

            <div class="player-box p2-box">
                <div class="readout">
                    <h3 style="margin:0; color: var(--frame-grey);"> 
                        <span id="name2">UNIT_02</span>                                               
                    </h3>
                    <div class="stat-line" style="color: var(--accent-green);"><span>SCORE</span> <span id="points2">0</span></div>
                    <div class="stat-line"><span>ITER</span> <span id="iteration2">0</span></div>
                </div>

                <div class="glyph-preview-container p2-layout">
                    <canvas id="canvas2" class="glyph-canvas"></canvas>
                    
                    <div class="glyph-stats">
                        <div class="stat-item">GEN <span id="spec-gen2">0</span></div>
                        <div class="stat-item">PEAK <span id="spec-peak2">0</span></div>
                        <div class="stat-item">MAX <span id="spec-max2">0</span></div>
                        <div class="stat-item">MIN <span id="spec-min2">0</span></div>
                    </div>
                </div>

                <div class="hash-readout" style="opacity: 0.6;">
                    <div class="stat-line" style="font-size: 0.55rem;"><span>ORIGIN</span> <span id="originHash2">0x...</span></div>
                    <div class="stat-line" style="font-size: 0.55rem; color: var(--accent-green);"><span>CURRENT</span> <span id="currentHash2">0x...</span></div>
                </div>

                <div class="profile-label">Live Cell Profile</div>
                <canvas id="cellProfile2" class="cell-profile-container"></canvas>

                <div class="profile-label">Match History (Round Wins)</div>
                <canvas id="roundHistory2" class="cell-profile-container"></canvas>

                <div class="profile-label">Charge Distribution (Histogram)</div>
                <canvas id="chargeHistogram2" class="cell-profile-container" style="height: 30px;"></canvas>

                <div class="profile-label">LIVE RANKINGS</div>
                <canvas id="tournamentLeaderboard" width="260" height="300" style="margin-top:10px;"></canvas>

            </div>

        </div>

    </div>

    <!-- OVERLAY FOR LOADING OF GLYPHS -->
    <div id="selectionModal" class="modal-overlay" onclick="if(event.target == this) closeSelectionModal()">
        <div class="modal-window">
            <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
                <h2 style="color:var(--accent-green); margin:0;">SELECT CONWAY GLYPH</h2>
                <button onclick="closeSelectionModal()" style="background:none; border:1px solid var(--frame-grey); color:white; cursor:pointer;">X</button>
            </div>
            
            <div class="glyph-list">
                <?php foreach ($glyphs as $glyph): ?>
                    <div class="glyph-row" onclick="selectGlyph(
                        '<?php echo $glyph['BIN']; ?>', 
                        '<?php echo addslashes($glyph['BATTLE_NAME']); ?>',
                        {gen: '<?php echo $glyph['GENERATIONS']; ?>', peak: '<?php echo $glyph['PEAK']; ?>', max: '<?php echo $glyph['MAX']; ?>', min: '<?php echo $glyph['MIN']; ?>'},true)">
                        <span class="glyph-name"><?php echo htmlspecialchars($glyph['BATTLE_NAME']); ?></span>
                        <span class="glyph-peak" style="opacity:0.5;">
                            GEN: <?php echo $glyph['GENERATIONS']; ?>,
                            PEAK: <?php echo $glyph['PEAK']; ?>,
                            MIN/MAX: <?php echo $glyph['MIN']; ?>/<?php echo $glyph['MAX']; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>    

    <div id="matchModal" class="modal-overlay" onclick="if(event.target == this) closeMatchModal()">
        <div class="modal-window" style="max-width: 400px;">
            <h2 style="color:var(--accent-green); margin-top:0;">INITIALIZE ENGAGEMENT</h2>
            
            <div style="margin-bottom: 15px;">
                <label class="profile-label">MATCH DESIGNATION</label>
                <input type="text" id="matchNameInput" placeholder="ENTER OPERATION NAME..." 
                    style="width: 100%; background: rgba(0,0,0,0.5); border: 1px solid var(--frame-grey); color: white; padding: 8px; font-family: 'Courier New';">
            </div>

            <div style="margin-bottom: 20px;">
                <label class="profile-label">ITERATION CYCLES (ROUNDS)</label>
                <div id="modal-round-selectors" style="display: flex; flex-wrap: wrap; gap: 5px; margin-top: 5px;">
                    <?php foreach ([1, 2, 4, 8, 16, 32, 256] as $r): ?>
                        <button class="load-btn round-opt" onclick="setTotalRounds(<?php echo $r; ?>, this)">
                            <?php echo $r; ?>R
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <button onclick="confirmAndStart()" class="load-btn" 
                    style="width: 100%; padding: 12px; border-color: var(--accent-green); color: var(--accent-green); font-weight: bold;">
                EXECUTE DUEL
            </button>
        </div>
    </div>

    <!-- WHO WILL WIN MODAL -->
    <div id="tournament-modal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <h1 id="modal-title" style="font-family: 'Courier New', monospace; color: var(--frame-grey); letter-spacing: 5px;">WHO WILL WIN?</h1>
            <div id="modal-canvas-container">
                </div>
            <button onclick="closeTournamentModal();" class="start-btn">ENGAGE</button>
        </div>
    </div>

    <script>

        let unit1, unit2;

        window.onload = function() {
            initializeUnits(16); // Initialize with a default 16x16 grid
        };

        function initializeUnits(n) {
            
            // Create the instances
            unit1 = new LifeEngine("canvas1", n);
            unit2 = new LifeEngine("canvas2", n);

            // Load a "blank" pattern (all zeros) so the canvases draw their initial state
            const blank = "0".repeat(n * n);
            unit1.loadFromBinary(blank);
            unit2.loadFromBinary(blank);
            
            console.log("Engines initialized at " + n + "x" + n);

        }

    </script>

</body>
</html>