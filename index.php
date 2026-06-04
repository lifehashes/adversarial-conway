<?php
include_once __DIR__ . '/../../priv/db_conf_laniakea.php';

// Fetch all available Conway Glyphs from the database
$stmt = $pdo->query("SELECT BATTLE_NAME, BIN, ITERATIONS as GENERATIONS, PEAK, MAX, MIN, HASH, TERMINAL, OWNER FROM GLYPHREG ORDER BY BATTLE_NAME ASC");
$glyphs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HASHWAR ENGINE :: v220</title>
    <style></style>
    <link rel="stylesheet" href="styles.css">
    <SCRIPT SRC="js/adv-conw-hash.js"></SCRIPT>
    <SCRIPT SRC="js/analytics.js"></SCRIPT>
    <SCRIPT SRC="js/control.js"></SCRIPT>
    <SCRIPT SRC="js/matches.js"></SCRIPT>    
</head>
<body>

    <div class="outer-frame">

    <div id="gamestatus-container" style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 15px; font-family: 'Courier New', monospace; padding: 0 10px;">
        
        <div class="header-left" style="display: flex; flex-direction: column; text-align: left; width: 250px; font-size: 0.9rem; color: var(--frame-grey);">
            <div style="font-size: 0.65rem;">
                Round <span id="current-round-display" style="color: white;">-</span>/<span id="total-rounds-display">-</span>
            </div>
            <div style="font-size: 0.65rem;">
                Seed: <span id="round-seed" style="color: white;">-</span>
            </div>
            <div style="font-size: 0.65rem;">
                Algorithm: <span id="algo" style="color: white;">-</span>
            </div>
            <div style="font-size: 0.65rem;">
                Mode: <span id="algo-mode" style="color: white;">-</span>
            </div>
        </div>

        <div class="header-center" style="display: flex; flex-direction: column; align-items: center; flex-grow: 1; text-align: center;">
            <div id="matchID" style="color: var(--frame-grey); margin-bottom: 0px; letter-spacing: 2px;">
                [ MATCH NAME ]
            </div>  
            <div id="match-tally" style="margin-top: 0px; margin-bottom: 0px; font-size: 1.2rem; color: #fff;">
                <span id="high-score-p1" style="color:#404040;">◀</span>
                [<span id="rounds-won-p1">0</span>]
                <span id="match-p1" style="color: var(--accent-green);">0</span>
                 : <span id="match-p2" style="color: var(--accent-green);">0</span> 
                [<span id="rounds-won-p2">0</span>]
                <span id="high-score-p2" style="color:#404040;">▶</span>
            </div>
            <div id="gamestatus" style="color: var(--frame-grey); font-size: 0.65rem; letter-spacing: 1px;">
                SYSTEM READY // AWAITING INPUT
            </div>
        </div>

        <div class="header-right" style="width: 250px;"></div>

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

    <?php include_once __DIR__ . '/modals.html'; ?>

    <script>

        let unit1, unit2, arena;

        let duelInterval;
        let frameDelay = 50;

        let totalRounds = 4;
        let currentRound = 1;
        let score;
        let matchScore = { p1: 0, p2: 0 };

        let currentActiveMode = 'single'; // 'single' or 'tournament'
        let targetPickerSlot = 1;
        let selectedTournamentN = 2;
        let activeTournamentPool = [];
        let TourneyHalleck = null; // the master of the revels overseeing the tournaments
        let matchIndex = null;
        let tourneyLength = null;
        let tourneyVisualizer;
        let tourneyLeaderboard;

        let maxMatchLength, totalCells;
        let globalChargePeak = 10;
        let profile1, profile2;
        let roundGraph1, roundGraph2;
        let chargeHist1, chargeHist2;
        let unifiedGraph;
        
        const GlyphRegistry = [
            <?php foreach ($glyphs as $glyph): ?>
            {
                name: "<?php echo addslashes($glyph['BATTLE_NAME']); ?>",
                bin: "<?php echo $glyph['BIN']; ?>",
                gen: parseInt("<?php echo $glyph['GENERATIONS']; ?>") || 0,
                peak: parseInt("<?php echo $glyph['PEAK']; ?>") || 0,
                min: parseInt("<?php echo $glyph['MIN']; ?>") || 0,
                max: parseInt("<?php echo $glyph['MAX']; ?>") || 0,
                originHash: "<?php echo addslashes($glyph['HASH']); ?>",
                terminal: "<?php echo addslashes($glyph['TERMINAL']); ?>",
                owner: "<?php echo addslashes($glyph['OWNER']); ?>",
                intrinsicColor: "#" + "<?php echo addslashes($glyph['HASH']); ?>".substring(3, 9)
            },
            <?php endforeach; ?>
        ];

        window.onload = function() {
            initializeUnits(16); // Initialize with a default 16x16 grid
            document.getElementById('canvasA').addEventListener('click', () => {
                if (!duelInterval) { openUnifiedConfig(); }
            });
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

        function openGlyphPicker(slot) {
            targetPickerSlot = slot;
            const pool = getFilteredPool();
            const rows = document.querySelectorAll('#selectionModal .glyph-row');
            
            // Instantly toggle sub-list visibility based on filtered pool requirements
            rows.forEach(row => {
                const name = row.getAttribute('data-name');
                const matched = pool.some(item => item.name === name);
                row.style.display = matched ? 'flex' : 'none';
            });

            document.getElementById('selectionModal').style.display = 'flex';
        }

        function closeSelectionModal() {
            document.getElementById('selectionModal').style.display = 'none';
        }

        function selectGlyphFromBroker(element) {
            const binary = element.getAttribute('data-bin');
            const name = element.getAttribute('data-name');
            const gen = element.getAttribute('data-gen');
            const peak = element.getAttribute('data-peak');
            const max = element.getAttribute('data-max');
            const min = element.getAttribute('data-min');
            const originHash = "0x" + element.getAttribute('data-originHash').substring(0, 16) + "...";

            if (targetPickerSlot === 1) {
                document.getElementById('ui-selected-p1').innerText = name;
            } else {
                document.getElementById('ui-selected-p2').innerText = name;
            }
            closeSelectionModal();
        }

        function assignRandomGlyph(slot) {
            const pool = getFilteredPool();
            if (pool.length === 0) {
                alert("FILTER EXCLUSION TRIGGER: NO GLYPHS MATCH LAYER 1 SPECS.");
                return;
            }
            const chosen = pool[Math.floor(Math.random() * pool.length)];
            
            if (slot === 1) {
                document.getElementById('ui-selected-p1').innerText = chosen.name;
            } else {
                document.getElementById('ui-selected-p2').innerText = chosen.name;
            }
        }

        function executeSystemEngagement() {

            let myGlyphSelection = "";
            activeTournamentPool.forEach((glyph) => { myGlyphSelection = myGlyphSelection + glyph.name + " " });
            console.log("[index.php] executeSystemEngagement(): activeTournamentPool = " + myGlyphSelection);
         
            const frameDelay = document.getElementById("engine-throttle-select").value;
            const selectedTourneyVariant = document.getElementById("tournament-variant-select").value;

            if (currentActiveMode === 'single') {

                const glyphA = activeTournamentPool[0].name;
                const glyphB = activeTournamentPool[1].name;                

                document.getElementById('unifiedConfigModal').style.display = 'none';
                const executionName = document.getElementById("matchNameInput").value.trim() || "UNNAMED_ENGAGEMENT";
                let myMatch = new Match(glyphA, glyphB, executionName, totalRounds, frameDelay);
                myMatch.run();

            } else {

                document.getElementById('unifiedConfigModal').style.display = 'none';
                TourneyHalleck = new Tournament(activeTournamentPool, selectedTourneyVariant);
                TourneyHalleck.startTournament();

            }
        }

    </script>

</body>
</html>