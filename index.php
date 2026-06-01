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
        let selectedTournamentN = 4;
        let activeTournamentPool = [];
        let TourneyHalleck = null; // the master of the revels overseeing the tournaments
        let currentMatchIndex = 0;
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
                owner: "<?php echo addslashes($glyph['OWNER']); ?>"
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

        function simulateMatch(){

            // 0. Ensure that both Glyphs are loaded
            if (!unit1.originHash || !unit2.originHash) {
                document.getElementById('gamestatus').innerText = "ERROR: BOTH UNITS MUST BE LOADED";
                return;
            }       
            
            // 1. Reset UI at round level
            resetUI(1);

            // 2. Instantiate the Arena
            const arenaGridSize = 96; 
            arena = new ArenaEngine("canvasA", 600, 300, arenaGridSize);

            // 3a. Estimate spatial and temporal envelope of match
            let gen1 = document.getElementById("spec-gen1").innerText;
            let gen2 = document.getElementById("spec-gen2").innerText;
            maxMatchLength = Math.max(parseInt(gen1), parseInt(gen2));
            totalCells = arena.rows * arena.cols;

            // 3b. Instantiate Analytics
            instantiateAnalytics();

            // 3c. Reset match round graph
            if (currentRound === 1){ roundGraph1.clear(); roundGraph2.clear(); }

            // 4. Generate a new repeat value for this match
            const repeatValue = Math.floor(Math.random() * 1000000);
            arena.setSeed(repeatValue);
            document.getElementById("round-seed").innerText = repeatValue;  
            document.getElementById("total-rounds-display").innerText = totalRounds;          
            //console.log("Match Seed: " + repeatValue);

            // 5. Stop any existing intervals and reset Glyphs to their starting configuration
            if (duelInterval) clearInterval(duelInterval);
            unit1.resetToOrigin(unit1.lastLoadedBin); 
            unit2.resetToOrigin(unit2.lastLoadedBin);            

            // setTimeout(() => {}, 1000);

            // 6. Start the main simulation loop
            duelInterval = setInterval(() => {

                // 1. Evolve the internal DNA of each Glyph
                const p1Active = unit1.computeNextGeneration();
                const p2Active = unit2.computeNextGeneration();

                // Update UI counters
                document.getElementById('iteration1').innerText = unit1.iteration;
                document.getElementById('currentHash1').innerText = "0x" + unit1.currentHash.substring(0, 16) + "...";

                document.getElementById('iteration2').innerText = unit2.iteration;
                document.getElementById('currentHash2').innerText = "0x" + unit2.currentHash.substring(0, 16) + "...";

                document.getElementById('current-round-display').innerText = currentRound;
                document.getElementById('gamestatus').innerText = `ROUND ${currentRound} IN PROGRESS...`;

                if (!p1Active) { document.getElementById('iteration1').style.color = "#ff4444"; }
                if (!p2Active) { document.getElementById('iteration2').style.color = "#ff4444"; }   
                
                // STOP CONDITION: The round is over once both Glyphs have reaced their respective halting state
                if (!unit1.isActive && !unit2.isActive) {
                    clearInterval(duelInterval); 
                    duelInterval = null;
                    
                    // Optional: Run one last render to show the final state
                    arena.render(unit1.intrinsicColor, unit2.intrinsicColor);

                    calcScore();
                    roundGraph1.record(score.p1, score.p2);
                    roundGraph2.record(score.p2, score.p1);

                    if (currentRound < totalRounds) {
                        document.getElementById('gamestatus').innerText = `ROUND ${currentRound} COMPLETE - WAITING...`;
                        currentRound++;

                        let waitBetweenRounds = 3000;
                        setTimeout(simulateMatch, waitBetweenRounds);
                    } else {
                        if (currentActiveMode === 'tournament') {
                            document.getElementById('gamestatus').innerText = `MATCH COMPLETE // STEPPING GRID...`;
                            currentMatchIndex++;
                            setTimeout(loadAndStartNextMatch, 4000);
                        } else {
                            document.getElementById('gamestatus').innerText = "MATCH COMPLETE";
                            currentRound = 1; 
                        }
                    }

                    renderAnalytics();
                    return;

                }

                arena.applyJitter();

                // 2. Project/Stamp the current DNA onto the Arena
                arena.stamp(unit1, 1);
                arena.stamp(unit2, 2);

                // 3. Render the preview windows (the DNA)
                unit1.render();
                unit2.render();

                // 4. Render the Arena (the Charge Field)
                arena.render(unit1.intrinsicColor, unit2.intrinsicColor);
                score = arena.calculateScore();
                document.getElementById('points1').innerText = score.p1;
                document.getElementById('points2').innerText = score.p2;

                // 5. Render Analytics
                renderAnalytics();

            }, frameDelay);

        }

        function switchMode(mode) {
            currentActiveMode = mode;
            const singlePanel = document.getElementById('panel-single-match');
            const tourneyPanel = document.getElementById('panel-tournament');
            
            if (mode === 'single') {
                document.getElementById('radio-single').checked = true;
                singlePanel.style.borderColor = 'var(--accent-green)';
                singlePanel.style.background = 'rgba(0,255,0,0.02)';
                singlePanel.querySelector('.mode-dependent-content').style.opacity = '1';
                singlePanel.querySelector('.mode-dependent-content').style.pointerEvents = 'auto';

                tourneyPanel.style.borderColor = '#222';
                tourneyPanel.style.background = 'rgba(0,0,0,0.2)';
                tourneyPanel.querySelector('.mode-dependent-content').style.opacity = '0.4';
                tourneyPanel.querySelector('.mode-dependent-content').style.pointerEvents = 'none';
            } else {
                document.getElementById('radio-tournament').checked = true;
                tourneyPanel.style.borderColor = 'var(--accent-green)';
                tourneyPanel.style.background = 'rgba(0,255,0,0.02)';
                tourneyPanel.querySelector('.mode-dependent-content').style.opacity = '1';
                tourneyPanel.querySelector('.mode-dependent-content').style.pointerEvents = 'auto';

                singlePanel.style.borderColor = '#222';
                singlePanel.style.background = 'rgba(0,0,0,0.2)';
                singlePanel.querySelector('.mode-dependent-content').style.opacity = '0.4';
                singlePanel.querySelector('.mode-dependent-content').style.pointerEvents = 'none';
            }
        }

        // Filters full pool list using current Layer 1 validation ranges
        function getFilteredPool() {
            const minGen = parseInt(document.getElementById('filter-gen-min').value) || 0;
            const maxGen = parseInt(document.getElementById('filter-gen-max').value) || Infinity;
            const minPeak = parseInt(document.getElementById('filter-peak-min').value) || 0;
            const maxPeak = parseInt(document.getElementById('filter-peak-max').value) || Infinity;
            const hashPrefix = document.getElementById('filter-hash-prefix').value.trim().toLowerCase();

            return GlyphRegistry.filter(g => {
                if (g.gen < minGen || g.gen > maxGen) return false;
                if (g.peak < minPeak || g.peak > maxPeak) return false;
                // Basic structural hook placeholder checking binary stream patterns
                if (hashPrefix && !g.name.toLowerCase().startsWith(hashPrefix)) return false; 
                return true;
            });
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
                unit1.loadFromBinary(binary);
                unit1.lastLoadedBin = binary;
                document.getElementById('ui-selected-p1').innerText = name;
                document.getElementById('name1').innerText = name;
                document.getElementById('spec-gen1').innerText = gen;
                document.getElementById('spec-peak1').innerText = peak;
                document.getElementById('spec-max1').innerText = max;
                document.getElementById('spec-min1').innerText = min;
                document.getElementById('originHash1').innerText = originHash;
            } else {
                unit2.loadFromBinary(binary);
                unit2.lastLoadedBin = binary;
                document.getElementById('ui-selected-p2').innerText = name;
                document.getElementById('name2').innerText = name;
                document.getElementById('spec-gen2').innerText = gen;
                document.getElementById('spec-peak2').innerText = peak;
                document.getElementById('spec-max2').innerText = max;
                document.getElementById('spec-min2').innerText = min;
                document.getElementById('originHash2').innerText = originHash;
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
                unit1.loadFromBinary(chosen.bin);
                unit1.lastLoadedBin = chosen.bin;
                document.getElementById('ui-selected-p1').innerText = chosen.name;
                document.getElementById('name1').innerText = chosen.name;
                document.getElementById('spec-gen1').innerText = chosen.gen;
                document.getElementById('spec-peak1').innerText = chosen.peak;
                document.getElementById('spec-max1').innerText = chosen.max;
                document.getElementById('spec-min1').innerText = chosen.min;
                document.getElementById('originHash1').innerText = "0x" + chosen.originHash.substring(0, 16) + "...";
            } else {
                unit2.loadFromBinary(chosen.bin);
                unit2.lastLoadedBin = chosen.bin;
                document.getElementById('ui-selected-p2').innerText = chosen.name;
                document.getElementById('name2').innerText = chosen.name;
                document.getElementById('spec-gen2').innerText = chosen.gen;
                document.getElementById('spec-peak2').innerText = chosen.peak;
                document.getElementById('spec-max2').innerText = chosen.max;
                document.getElementById('spec-min2').innerText = chosen.min;
                document.getElementById('originHash2').innerText = "0x" + chosen.originHash.substring(0, 16) + "...";
            }
        }

        function setTournamentN(n) {
            selectedTournamentN = n;
            document.querySelectorAll('.dynamic-n-btn').forEach(btn => {
                if(parseInt(btn.getAttribute('data-n')) === n) {
                    btn.style.borderColor = 'var(--accent-green)';
                    btn.style.color = 'var(--accent-green)';
                } else {
                    btn.style.borderColor = '';
                    btn.style.color = '';
                }
            });
        }

        function autoFillTournamentGlyphs() {
            const pool = getFilteredPool();
            if(pool.length < selectedTournamentN) {
                alert(`INSUFFICIENT DATA POOL: Requested ${selectedTournamentN} matching entities, but filters only returned ${pool.length}.`);
                return;
            }
            // Shuffler routine parsing parameters
            let mixed = [...pool].sort(() => 0.5 - Math.random());
            activeTournamentPool = mixed.slice(0, selectedTournamentN);
            console.log(`Tournament configuration array filled with ${selectedTournamentN} units via Layer 1 logic maps.`);
        }

        function executeSystemEngagement() {
            const executionName = document.getElementById('matchNameInput').value.trim() || "UNNAMED_ENGAGEMENT";
            document.getElementById('matchID').innerText = executionName.toUpperCase();

            if (currentActiveMode === 'single') {
                document.getElementById('unifiedConfigModal').style.display = 'none';
                currentRound = 1;
                simulateMatch();
            } else {
                if (activeTournamentPool.length === 0 || activeTournamentPool.length !== selectedTournamentN) {
                    alert(`CRITICAL INTERCEPT: You must inject or select contestants for an N=${selectedTournamentN} tournament matrix before initializing.`);
                    return;
                }

                document.getElementById('unifiedConfigModal').style.display = 'none';
                
                // Initialize the Tournament Manager globally
                TourneyHalleck = new TournamentManager(activeTournamentPool);
                
                // Reset our match index tracking pointer for a fresh start
                currentMatchIndex = 0;

                // Reset overall win/loss metrics in the control UI if desired
                document.getElementById("rounds-won-p1").innerText = "0";
                document.getElementById("rounds-won-p2").innerText = "0";

                // Kick off the asynchronous tournament processor loop
                loadAndStartNextMatch();
            }
        }

        function loadAndStartNextMatch() {
            const totalMatches = TourneyHalleck.matchQueue.length;

            if (currentMatchIndex < totalMatches) {
                // Explicitly sync the manager's index pointer with our loop pointer
                TourneyHalleck.currentMatchIndex = currentMatchIndex;
                const activeMatch = TourneyHalleck.getNextMatch();
                resetUI(0);

                if (activeMatch) {
                    console.log(`[TOURNAMENT] Initializing Match ${currentMatchIndex + 1}/${totalMatches}:`, activeMatch);
                    
                    unit1.loadFromBinary(activeMatch.p1.bin);
                    unit1.lastLoadedBin = activeMatch.p1.bin;
                    unit2.loadFromBinary(activeMatch.p2.bin);
                    unit2.lastLoadedBin = activeMatch.p2.bin;

                    document.getElementById('name1').innerText = activeMatch.p1.name;
                    document.getElementById('name2').innerText = activeMatch.p2.name;

                    document.getElementById('spec-gen1').innerText = activeMatch.p1.gen;
                    document.getElementById('spec-peak1').innerText = activeMatch.p1.peak;
                    document.getElementById('spec-max1').innerText = activeMatch.p1.max;
                    document.getElementById('spec-min1').innerText = activeMatch.p1.min;
                    document.getElementById('originHash1').innerText = "0x" + activeMatch.p1.originHash.substring(0, 16) + "...";

                    document.getElementById('spec-gen2').innerText = activeMatch.p2.gen;
                    document.getElementById('spec-peak2').innerText = activeMatch.p2.peak;
                    document.getElementById('spec-max2').innerText = activeMatch.p2.max;
                    document.getElementById('spec-min2').innerText = activeMatch.p2.min;
                    document.getElementById('originHash2').innerText = "0x" + activeMatch.p2.originHash.substring(0, 16) + "...";
                    
                    // Clear round graph visuals before the first round of the new match begins
                    if (roundGraph1) roundGraph1.clear();
                    if (roundGraph2) roundGraph2.clear();

                    currentRound = 1;
                    document.getElementById('gamestatus').innerText = `TOURNAMENT MATCH ${currentMatchIndex + 1}/${totalMatches}`;
                    
                    simulateMatch(); 
                } else {
                    alert("ERROR GENERATING MATCH PAYLOAD");
                }
            } else {
                document.getElementById('gamestatus').innerText = "TOURNAMENT COMPLETE";
                alert("🏁 TOURNAMENT LOG COMPLETED: All queue pairings resolved.");
            }
        }

    </script>

</body>
</html>