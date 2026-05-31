<?php
include_once __DIR__ . '/../../priv/db_conf_laniakea.php';

// Fetch all available Conway Glyphs from the database
$stmt = $pdo->query("SELECT BATTLE_NAME, BIN, ITERATIONS as GENERATIONS, PEAK, MAX, MIN, HASH FROM GLYPHREG ORDER BY BATTLE_NAME ASC");
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
    <SCRIPT SRC="js/control.js"></SCRIPT>
	<SCRIPT SRC="js/gol.js"></SCRIPT>
	<SCRIPT SRC="js/sha256.js"></SCRIPT>
    <SCRIPT SRC="js/adversarial.js"></SCRIPT>
    <SCRIPT SRC="js/analytics.js"></SCRIPT>
    <SCRIPT SRC="js/tournament.js"></SCRIPT>
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

    <div id="unifiedConfigModal" class="modal-overlay" style="display: none;" onclick="if(event.target == this) closeUnifiedConfig()">
        <div class="modal-window" style="max-width: 850px; width: 95%; display: flex; flex-direction: column; gap: 20px; font-family: 'Courier New', monospace; border-color: var(--frame-grey);">
            
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #333; padding-bottom: 10px;">
                <h2 style="color: var(--accent-green); margin: 0; letter-spacing: 2px;">SYSTEM ENGAGEMENT ARCHITECTURE</h2>
                <div style="font-size: 0.75rem; color: var(--frame-grey);">SECURE LINK</div>
            </div>

            <div style="background: rgba(255,255,255,0.02); border: 1px solid #222; padding: 15px;">
                <div class="profile-label" style="margin-bottom: 10px; color: var(--accent-green);">[ LAYER 01 ] GLYPH ELIGIBILITY FILTERS</div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div>
                        <label style="font-size: 0.7rem; color: var(--frame-grey); display: block; margin-bottom: 4px;">GENERATION RANGE (MIN - MAX)</label>
                        <div style="display: flex; gap: 5px;">
                            <input type="number" id="filter-gen-min" placeholder="0" style="width: 50%; background: #000; border: 1px solid #444; color: #fff; padding: 5px; font-family: inherit;">
                            <input type="number" id="filter-gen-max" placeholder="9999" style="width: 50%; background: #000; border: 1px solid #444; color: #fff; padding: 5px; font-family: inherit;">
                        </div>
                    </div>
                    <div>
                        <label style="font-size: 0.7rem; color: var(--frame-grey); display: block; margin-bottom: 4px;">PEAK SPECS RANGE (MIN - MAX)</label>
                        <div style="display: flex; gap: 5px;">
                            <input type="number" id="filter-peak-min" placeholder="0" style="width: 50%; background: #000; border: 1px solid #444; color: #fff; padding: 5px; font-family: inherit;">
                            <input type="number" id="filter-peak-max" placeholder="9999" style="width: 50%; background: #000; border: 1px solid #444; color: #fff; padding: 5px; font-family: inherit;">
                        </div>
                    </div>
                    <div>
                        <label style="font-size: 0.7rem; color: var(--frame-grey); display: block; margin-bottom: 4px;">ORIGIN HASH PREFIX PREFIX</label>
                        <input type="text" id="filter-hash-prefix" placeholder="0x..." style="width: 100%; background: #000; border: 1px solid #444; color: #fff; padding: 5px; box-sizing: border-box; font-family: inherit;">
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                
                <div id="panel-single-match" onclick="switchMode('single')" style="border: 1px solid var(--accent-green); background: rgba(0,255,0,0.02); padding: 15px; cursor: pointer; transition: all 0.2s ease;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                        <input type="radio" id="radio-single" name="mode-select" checked style="cursor: pointer;">
                        <label for="radio-single" class="profile-label" style="margin: 0; color: #fff; cursor: pointer;">TACTICAL SINGLE COMBAT</label>
                    </div>
                    
                    <div class="mode-dependent-content" style="display: flex; flex-direction: column; gap: 10px;">
                        <div>
                            <div style="font-size: 0.7rem; color: var(--frame-grey); margin-bottom: 4px;">CONTESTANT ALPHA</div>
                            <div style="display: flex; gap: 5px;">
                                <button onclick="openGlyphPicker(1); event.stopPropagation();" style="flex-grow: 1; background: #111; border: 1px solid #444; color: white; padding: 6px; cursor: pointer; font-family: inherit; font-size: 0.75rem;">
                                    <span id="ui-selected-p1">SELECT GLYPH 01</span>
                                </button>
                                <button onclick="assignRandomGlyph(1); event.stopPropagation();" style="background: none; border: 1px solid #444; color: var(--frame-grey); padding: 0 8px; cursor: pointer; font-family: inherit; font-size: 0.7rem;">🎲 RAND</button>
                            </div>
                        </div>
                        <div>
                            <div style="font-size: 0.7rem; color: var(--frame-grey); margin-bottom: 4px;">CONTESTANT BETA</div>
                            <div style="display: flex; gap: 5px;">
                                <button onclick="openGlyphPicker(2); event.stopPropagation();" style="flex-grow: 1; background: #111; border: 1px solid #444; color: white; padding: 6px; cursor: pointer; font-family: inherit; font-size: 0.75rem;">
                                    <span id="ui-selected-p2">SELECT GLYPH 02</span>
                                </button>
                                <button onclick="assignRandomGlyph(2); event.stopPropagation();" style="background: none; border: 1px solid #444; color: var(--frame-grey); padding: 0 8px; cursor: pointer; font-family: inherit; font-size: 0.7rem;">🎲 RAND</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="panel-tournament" onclick="switchMode('tournament')" style="border: 1px solid #222; background: rgba(0,0,0,0.2); padding: 15px; cursor: pointer; transition: all 0.2s ease;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                        <input type="radio" id="radio-tournament" name="mode-select" style="cursor: pointer;">
                        <label for="radio-tournament" class="profile-label" style="margin: 0; color: #fff; cursor: pointer;">TOURNAMENT BRACKET MATRIX</label>
                    </div>

                    <div class="mode-dependent-content" style="display: flex; flex-direction: column; gap: 10px; opacity: 0.4; pointer-events: none;">
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <span style="font-size: 0.75rem; color: var(--frame-grey);">VARIANT:</span>
                            <select style="background: #000; border: 1px solid #444; color: #fff; padding: 4px; font-family: inherit; font-size: 0.75rem; flex-grow: 1;">
                                <option>ROUND ROBIN</option>
                                <option disabled>KNOCK-OUT SYSTEM (LOCKED)</option>
                            </select>
                        </div>
                        <div>
                            <div style="font-size: 0.7rem; color: var(--frame-grey); margin-bottom: 4px;">CONTESTANT METRICS (N-VAL)</div>
                            <div style="display: flex; gap: 5px;">
                                <button onclick="setTournamentN(4); event.stopPropagation();" class="load-btn n-opt dynamic-n-btn" data-n="4" style="flex: 1; padding: 4px; font-size:0.7rem;">4P</button>
                                <button onclick="setTournamentN(8); event.stopPropagation();" class="load-btn n-opt dynamic-n-btn" data-n="8" style="flex: 1; padding: 4px; font-size:0.7rem;">8P</button>
                                <button onclick="setTournamentN(16); event.stopPropagation();" class="load-btn n-opt dynamic-n-btn" data-n="16" style="flex: 1; padding: 4px; font-size:0.7rem;">16P</button>
                                <button onclick="setTournamentN(32); event.stopPropagation();" class="load-btn n-opt dynamic-n-btn" data-n="32" style="flex: 1; padding: 4px; font-size:0.7rem;">32P</button>
                            </div>
                        </div>
                        <div style="display: flex; gap: 5px; margin-top: 2px;">
                            <button onclick="openBulkTournamentPicker(); event.stopPropagation();" style="flex: 1; background: #111; border: 1px solid #444; color: white; padding: 5px; cursor: pointer; font-family: inherit; font-size: 0.7rem;">MANUAL SELECTION</button>
                            <button onclick="autoFillTournamentGlyphs(); event.stopPropagation();" style="flex: 1; background: none; border: 1px solid #444; color: var(--accent-green); padding: 5px; cursor: pointer; font-family: inherit; font-size: 0.7rem;">🎲 INJECT AUTOMATIC</button>
                        </div>
                    </div>
                </div>

            </div>

            <div style="background: rgba(255,255,255,0.02); border: 1px solid #222; padding: 15px; display: flex; flex-direction: column; gap: 12px;">
                <div class="profile-label" style="color: var(--accent-green);">[ LAYER 03 ] RUNTIME SIMULATION PARAMETERS</div>
                
                <div style="display: grid; grid-template-columns: 2fr 3fr 2fr; gap: 15px; align-items: flex-end;">
                    <div>
                        <label style="font-size: 0.7rem; color: var(--frame-grey); display: block; margin-bottom: 4px;">OPERATION NAME</label>
                        <input type="text" id="matchNameInput" placeholder="ENTER DESIG..." style="width: 100%; background: #000; border: 1px solid #444; color: white; padding: 6px; font-family: inherit; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="font-size: 0.7rem; color: var(--frame-grey); display: block; margin-bottom: 4px;">MATCH ITERATION CYCLES</label>
                        <div style="display: flex; gap: 4px;">
                            <?php foreach ([1, 2, 4, 8, 16, 32, 256] as $r): ?>
                                <button class="load-btn round-opt" onclick="setTotalRounds(<?php echo $r; ?>, this)" style="padding: 6px 0; font-size: 0.7rem; flex: 1;">
                                    <?php echo $r; ?>R
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div>
                        <label style="font-size: 0.7rem; color: var(--frame-grey); display: block; margin-bottom: 4px;">THROTTLE MATRIX (DELAY)</label>
                        <select id="engine-throttle-select" onchange="frameDelay = parseInt(this.value);" style="width: 100%; background: #000; border: 1px solid #444; color: white; padding: 5px; font-family: inherit; font-size: 0.75rem;">
                            <option value="100">100ms (Slow)</option>
                            <option value="50" selected>50ms (Normal)</option>
                            <option value="16">16ms (60 FPS)</option>
                            <option value="0">0ms (Instant/Max Run)</option>
                        </select>
                    </div>
                </div>
            </div>

            <button onclick="executeSystemEngagement()" class="load-btn" style="width: 100%; padding: 14px; border-color: var(--accent-green); color: var(--accent-green); font-weight: bold; background: rgba(0,255,0,0.02); font-size: 1rem; letter-spacing: 2px;">
                INITIALIZE SIMULATION
            </button>
        </div>
    </div>

    <div id="selectionModal" class="modal-overlay" style="z-index: 10000; display:none;" onclick="if(event.target == this) closeSelectionModal()">
        <div class="modal-window" style="max-width: 500px;">
            <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
                <h3 style="color:var(--accent-green); margin:0; font-family:'Courier New';">LOAD CELL CONFIGURATION</h3>
                <button onclick="closeSelectionModal()" style="background:none; border:1px solid var(--frame-grey); color:white; cursor:pointer;">X</button>
            </div>
            <div class="glyph-list" style="max-height: 400px; overflow-y: auto;">
                <?php foreach ($glyphs as $glyph): ?>
                    <div class="glyph-row" 
                         data-gen="<?php echo $glyph['GENERATIONS']; ?>"
                         data-peak="<?php echo $glyph['PEAK']; ?>"
                         data-bin="<?php echo $glyph['BIN']; ?>"
                         data-name="<?php echo htmlspecialchars($glyph['BATTLE_NAME']); ?>"
                         data-max="<?php echo htmlspecialchars($glyph['MAX']); ?>"
                         data-min="<?php echo htmlspecialchars($glyph['MIN']); ?>"
                         data-originHash="<?php echo htmlspecialchars($glyph['HASH']); ?>"
                         onclick="selectGlyphFromBroker(this)">
                        <span class="glyph-name"><?php echo htmlspecialchars($glyph['BATTLE_NAME']); ?></span>
                        <span class="glyph-peak" style="opacity:0.5; font-size:0.7rem;">
                            G: <?php echo $glyph['GENERATIONS']; ?> | P: <?php echo $glyph['PEAK']; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>

        let unit1, unit2, arena;

        let duelInterval;
        let frameDelay = 50;

        let totalRounds = 4;
        let currentRound = 1;
        let matchScore = { p1: 0, p2: 0 };

        let currentActiveMode = 'single'; // 'single' or 'tournament'
        let targetPickerSlot = 1;
        let selectedTournamentN = 4;
        let activeTournamentPool = [];
        let TourneyHalleck = null; // the master of the revels overseeing the tournaments
        
        // Cache array built out of PHP payload pool objects for rapid dynamic filters
        const rawGlyphRegistry = [
            <?php foreach ($glyphs as $glyph): ?>
            {
                name: "<?php echo addslashes($glyph['BATTLE_NAME']); ?>",
                bin: "<?php echo $glyph['BIN']; ?>",
                gen: parseInt("<?php echo $glyph['GENERATIONS']; ?>") || 0,
                peak: parseInt("<?php echo $glyph['PEAK']; ?>") || 0,
                min: parseInt("<?php echo $glyph['MIN']; ?>") || 0,
                max: parseInt("<?php echo $glyph['MAX']; ?>") || 0,
                originHash: "<?php echo addslashes($glyph['HASH']); ?>"
            },
            <?php endforeach; ?>
        ];

        window.onload = function() {
            initializeUnits(16); // Initialize with a default 16x16 grid
            document.getElementById('canvasA').addEventListener('click', () => {
                if (!duelInterval) { openUnifiedConfig(); }
            });
            // openUnifiedConfig();
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

        function initMockUnits(n){

            // Create the instances
            unit1 = new LifeEngine("canvas1", n);
            unit2 = new LifeEngine("canvas2", n);

            // Initial random population for testing
            const mockBinary1 = Array.from({length: n*n}, () => Math.round(Math.random())).join('');
            const mockBinary2 = Array.from({length: n*n}, () => Math.round(Math.random())).join('');

            unit1.loadFromBinary(mockBinary1);
            unit2.loadFromBinary(mockBinary2);

            unit1.lastLoadedBin = unit1.getBinaryString();
            unit2.lastLoadedBin = unit2.getBinaryString();

        }

        function runPreviews() {
            const previewInterval = setInterval(() => {

                const p1Active = unit1.computeNextGeneration();
                const p2Active = unit2.computeNextGeneration();
                
                unit1.render();
                unit2.render();
                
                // Update UI counters
                document.getElementById('iteration1').innerText = unit1.iteration;
                document.getElementById('originHash1').innerText = "0x" + unit1.originHash.substring(0, 16) + "...";
                document.getElementById('currentHash1').innerText = "0x" + unit1.currentHash.substring(0, 16) + "...";

                document.getElementById('iteration2').innerText = unit2.iteration;
                document.getElementById('originHash2').innerText = "0x" + unit2.originHash.substring(0, 16) + "...";
                document.getElementById('currentHash2').innerText = "0x" + unit2.currentHash.substring(0, 16) + "...";

                if (!p1Active) { document.getElementById('iteration1').style.color = "#ff4444"; }
                if (!p2Active) { document.getElementById('iteration2').style.color = "#ff4444"; }

                // Stop the interval if both are finished
                if (!p1Active && !p2Active) {
                    clearInterval(previewInterval);
                    document.getElementById('gamestatus').innerText = "DUEL COMPLETE";
                }

            }, 50);
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

            // 3. Generate a new repeat value for this match
            const repeatValue = Math.floor(Math.random() * 1000000);
            arena.setSeed(repeatValue);
            document.getElementById("round-seed").innerText = repeatValue;  
            document.getElementById("total-rounds-display").innerText = totalRounds;          
            //console.log("Match Seed: " + repeatValue);

            // 4. Stop any existing intervals and reset Glyphs to their starting configuration
            if (duelInterval) clearInterval(duelInterval);
            unit1.resetToOrigin(unit1.lastLoadedBin); 
            unit2.resetToOrigin(unit2.lastLoadedBin);            

            // setTimeout(() => {}, 1000);

            // 5. Start the main simulation loop
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
                    clearInterval(duelInterval); duelInterval = null;
                    
                    // Optional: Run one last render to show the final state
                    arena.render(unit1.intrinsicColor, unit2.intrinsicColor);

                    calcScore();

                    if (currentRound < totalRounds) {
                        document.getElementById('gamestatus').innerText = `ROUND ${currentRound} COMPLETE - WAITING...`;
                        currentRound++;

                        let waitBetweenRounds = 3000;
                        setTimeout(simulateMatch, waitBetweenRounds);
                    } else {
                        document.getElementById('gamestatus').innerText = "MATCH COMPLETE";
                        currentRound = 1; // Reset for next time button is clicked
                    }

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
                const score = arena.calculateScore();
                document.getElementById('points1').innerText = score.p1;
                document.getElementById('points2').innerText = score.p2;

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

            return rawGlyphRegistry.filter(g => {
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
                document.getElementById('gamestatus').innerText = `TOURNAMENT NETWORK STANDBY [N=${selectedTournamentN}]`;
                
                TourneyHalleck = new TournamentManager(activeTournamentPool);
                const activeMatch = TourneyHalleck.getNextMatch();
                if (activeMatch){ 

                    console.log(activeMatch);
                    unit1.loadFromBinary(activeMatch.p1.bin);
                    unit1.lastLoadedBin = activeMatch.p1.bin;
                    unit2.loadFromBinary(activeMatch.p2.bin);
                    unit2.lastLoadedBin = activeMatch.p2.bin;

                    document.getElementById('name1').innerText = activeMatch.p1.name;
                    document.getElementById('name2').innerText = activeMatch.p2.name;
                    
                    currentRound = 1;
                    document.getElementById('gamestatus').innerText = `TOURNAMENT MATCH 1/${TourneyHalleck.matchQueue.length}`;
                    
                    simulateMatch(); 
                } else {
                    alert("TOURNAMENT COMPLETE OR ERROR GENERATING QUEUE");
                }

            }
        }

    </script>

</body>
</html>