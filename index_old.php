<!DOCTYPE html>
<html lang="en">
<head>
    <SCRIPT SRC="adversarial.js"></SCRIPT>
    <SCRIPT SRC="analytics.js"></SCRIPT>
    <SCRIPT SRC="tournament.js"></SCRIPT>
</head>
<body>

    <script>

        let arena;
        let duelInterval;
        let unifiedGraph;
        let profile1, profile2;

        let totalRounds = 1;
        let currentRound = 1;
        let matchScore = { p1: 0, p2: 0 };
        let roundGraph1, roundGraph2;
        let chargeHist1, chargeHist2;
        let globalChargePeak = 10;

        let frameDelay = 50;

        let tourneyManager;
        let tourneyVisualizer;
        let tourneyLeaderboard;

        let modalEngines = []; // for the modal that brings up the Glyphs and spawns their respective LifeEngines

        // N: Number of contestants, k: Number of rounds per match, s: Simulation speed [0: fast, 50: slow]
        async function launchNWayTourney(N, k, s) {

                totalRounds = k;
                frameDelay = s;

                const modal = document.getElementById('tournament-modal');
                const container = document.getElementById('modal-canvas-container');
                container.innerHTML = ''; // Clear previous
                modalEngines = [];
                
                modal.style.display = 'flex';

                const centerX = container.offsetWidth / 2;
                const centerY = container.offsetHeight / 2;
                const radius = Math.min(centerX, centerY) * 1.2;

            // 1. Convert the PHP array to a JavaScript array of objects
            let allAvailable = <?php echo json_encode($glyphs); ?>;

            // 2. Shuffle the array (Fisher-Yates Shuffle)
            for (let i = allAvailable.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [allAvailable[i], allAvailable[j]] = [allAvailable[j], allAvailable[i]];
            }

            // 3. Select N glyphs from the now-randomized list
            // (Note: 'offset' can still be used if you want to skip the first few random results)
            const offset = 0;
            const selected = allAvailable.slice(offset, N + offset);

                selected.forEach((glyph, i) => {
                        // 1. Calculate Polygon Position
                        const angle = (i / N) * Math.PI * 2 - (Math.PI / 2);
                        const x = centerX + radius * Math.cos(angle);
                        const y = centerY + radius * Math.sin(angle);
                        // console.log("[index.php] launchNWayTourney(N=" + N + "): i=" + i + ", x=" + x + ", y=" + y + ".");

                        // 2. Create Canvas Element
                        const canvasId = `modal-canvas-${i}`;
                        const canvas = document.createElement('canvas');
                        canvas.id = canvasId;
                        canvas.className = 'modal-glyph-canvas';
                        canvas.width = 120; // Size of the mini-display
                        canvas.height = 120;
                        canvas.style.left = `${x}px`;
                        canvas.style.top = `${y}px`;
                        
                        container.appendChild(canvas);

                        // labels

                        const label = document.createElement('div');
                        label.className = 'modal-glyph-label';
                        
                        // Offset the label based on the same X, Y as the canvas
                        label.style.left = `${x}px`;
                        label.style.top = `${y}px`;

                        if (x > centerX) {
                            label.style.transform = 'translate(65px, -50%)';
                            label.style.alignItems = 'flex-start';
                            label.style.borderLeft = `3px solid ${glyph.color || "#42f485"}`;
                            label.style.borderRight = 'none';
                        } else {
                            label.style.transform = 'translate(-100%, -50%)';
                            label.style.left = `${x - 65}px`; // Shift it left of the canvas
                            label.style.alignItems = 'flex-end';
                            label.style.textAlign = 'right';
                            label.style.borderRight = `3px solid ${glyph.color || "#42f485"}`;
                            label.style.borderLeft = 'none';
                        }

                        // Inject the Data
                        label.innerHTML = `
                            <h3>${glyph.BATTLE_NAME}</h3>
                            <div class="modal-glyph-stats">
                                <span>G:<span class="stat-val">${glyph.GENERATIONS}</span></span>
                                <span>P:<span class="stat-val">${glyph.PEAK}</span></span>
                                <span>MN:<span class="stat-val">${glyph.MIN}</span></span>
                                <span>MX:<span class="stat-val">${glyph.MAX}</span></span>
                            </div>
                        `;

                        // Apply the glyph's unique color to the border
                        label.style.borderLeftColor = glyph.color || "#42f485";

                        container.appendChild(label);

                        // 3. Initialize LifeEngine (from your gol.js)
                        const engine = new LifeEngine(canvasId, 16); // Assuming 16x16 grid
                        engine.intrinsicColor = glyph.color || "#42f485";
                        engine.loadFromBinary(glyph.BIN);
                        
                        modalEngines.push(engine);
                    });

                    let lastTime = 0;
                    const throttleSpeed = 50; // The delay in milliseconds. Higher = Slower.
                    animateModalPreview();

                    /*
                    function animateModalPreview() {
                        if (document.getElementById('tournament-modal').style.display === 'none') return;

                        modalEngines.forEach(engine => {
                            engine.computeNextGeneration(); // Step the GOL logic
                            engine.render(); // Draw to its mini-canvas
                        });

                        requestAnimationFrame(animateModalPreview);
                    }
                    */

                    function animateModalPreview(timestamp) {
                        // 1. Check if the modal is still open
                        const modal = document.getElementById('tournament-modal');
                        if (!modal || modal.style.display === 'none') return;

                        // 2. Calculate how much time has passed since the last update
                        const deltaTime = timestamp - lastTime;

                        // 3. Only update the LifeEngine logic if enough time has elapsed
                        if (deltaTime > throttleSpeed) {
                            modalEngines.forEach(engine => {
                                engine.computeNextGeneration(); 
                                engine.render(); 
                            });
                            lastTime = timestamp; // Reset the timer
                        }

                        // 4. Keep the loop running smoothly
                        requestAnimationFrame(animateModalPreview);
                    }

                    function animateModalPreview(timestamp) {
                        // 1. Check if the modal is still open
                        const modal = document.getElementById('tournament-modal');
                        if (!modal || modal.style.display === 'none') return;

                        // 2. Calculate how much time has passed since the last update
                        const deltaTime = timestamp - lastTime;

                        // 3. Only update the LifeEngine logic if enough time has elapsed
                        if (deltaTime > throttleSpeed) {
                            modalEngines.forEach(engine => {
                                engine.computeNextGeneration(); 
                                engine.render(); 
                            });
                            lastTime = timestamp; // Reset the timer
                        }

                        // 4. Keep the loop running smoothly
                        requestAnimationFrame(animateModalPreview);
                    }

            // 4. Initialize the tournament
            // startTournament(selected);

            await new Promise((resolve) => {
                const engageBtn = document.querySelector('#tournament-modal .start-btn');
                
                // One-time event listener
                engageBtn.onclick = () => {
                    modal.style.display = 'none'; // Close the modal
                    resolve(); // This "unblocks" the await below
                };
            });

            // This will now only fire AFTER the button is clicked and modal is hidden
            console.log("Modal closed. Starting tournament...");
            startTournament(selected);

        }

        function closeTournamentModal() {
            document.getElementById('tournament-modal').style.display = 'none';
            // Trigger actual tournament start logic here
        }

        function startTournament(selectedGlyphs) {
            // 1. Initialize Logic
            tourneyManager = new TournamentManager(selectedGlyphs, 'round-robin');
            
            // 2. Initialize Visuals
            tourneyVisualizer = new TournamentVisualizer("tournamentPolygon", selectedGlyphs);
            tourneyLeaderboard = new TournamentLeaderboard("tournamentLeaderboard", selectedGlyphs);
            
            // 3. Start the first match
            playNextTournamentMatch();
        }

        function playNextTournamentMatch() {
            const match = tourneyManager.getNextMatch();
            
            if (match) {
 
                document.getElementById('total-rounds-display').innerText = totalRounds;

                // Auto-load units (Assuming match.p1 and match.p2 are Glyph objects from DB)
                // binString, name, stats
                selectGlyphForTourney(1, match.p1);
                selectGlyphForTourney(2, match.p2);

                match.p1.intrinsicColor = unit1.intrinsicColor;
                match.p2.intrinsicColor = unit2.intrinsicColor;

                // Update Visuals for the "White" highlight
                tourneyVisualizer.render(match.p1.BATTLE_NAME, match.p2.BATTLE_NAME);
                tourneyLeaderboard.render(tourneyManager.standings);

                // Start the actual GOL Duel
                confirmAndStart(); 
            } else {
                document.getElementById('gamestatus').innerText = "TOURNAMENT COMPLETE";
            }
        }

        // Helper to load units without opening the modal
        function selectGlyphForTourney(playerNum, glyph) {
            // 1. First, tell the selection modal which "target" we are simulating
            // This is vital because selectGlyph looks at this dataset attribute
            document.getElementById('selectionModal').dataset.activePlayer = playerNum;

            // 2. Map the tournament glyph object to the 'stats' object format used by the modal
            const statsObj = {
                gen: glyph.GENERATIONS,
                peak: glyph.PEAK,
                max: glyph.MAX,
                min: glyph.MIN
            };

            // This will handle the grid size math, the hash strings, and the UI labels
            selectGlyph(glyph.BIN, glyph.BATTLE_NAME, statsObj, false);
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

        }

        function openSelectionModal(playerNum) {
            const modal = document.getElementById('selectionModal');
            modal.style.display = 'flex';
            // Store which player is currently selecting
            modal.dataset.activePlayer = playerNum;
            
            // In the future, this is where you fetch from your PHP:
            // fetch('get_glyphs.php').then(res => res.json()).then(populateModal);
        }

        function closeSelectionModal() {
            document.getElementById('selectionModal').style.display = 'none';
        }  

        function selectGlyph(binString, name, stats, mode) {
            // console.log("[index.php] selectGlyph(" + name +"): Binary has length " + binString.length);
            const n = Math.sqrt(binString.length);
            
            if (Number.isInteger(n)) {
                const playerNum = document.getElementById('selectionModal').dataset.activePlayer;
                const targetUnit = (playerNum == 1) ? unit1 : unit2;
                
                // Re-initialize engine with new N if necessary
                targetUnit.n = n;
                targetUnit.loadFromBinary(binString);
                targetUnit.lastLoadedBin = binString;
                targetUnit.generations = parseInt(stats.gen);

                document.getElementById(`spec-gen${playerNum}`).innerText = stats.gen;
                document.getElementById(`spec-peak${playerNum}`).innerText = stats.peak;
                document.getElementById(`spec-max${playerNum}`).innerText = stats.max;
                document.getElementById(`spec-min${playerNum}`).innerText = stats.min;
                
                document.getElementById(`name${playerNum}`).innerText = name;
                document.getElementById(`originHash${playerNum}`).innerText = "0x" + targetUnit.originHash.substring(0, 16) + "...";
                closeSelectionModal();

                // CHECK IF BOTH ARE READY
                if (unit1.lastLoadedBin && unit2.lastLoadedBin && mode) {
                    setTimeout(openMatchModal, 500); // Slight delay for visual polish
                }                        

            } else {
                alert("CRITICAL ERROR: Binary string is not a perfect square.");
            }
        }

        function openMatchModal() {
            document.getElementById('matchModal').style.display = 'flex';
        }

        function closeMatchModal() {
            document.getElementById('matchModal').style.display = 'none';
        }

function confirmAndStart() {
    const matchName = document.getElementById('matchNameInput').value || "UNNAMED_ENGAGEMENT";
    document.getElementById('gamestatus').innerText = "OP: " + matchName.toUpperCase();
    closeMatchModal();

    document.getElementById("rounds-won-p1").innerText = "0";
    document.getElementById("rounds-won-p2").innerText = "0";

    document.getElementById("high-score-p1").style.color = "#404040";
    document.getElementById("high-score-p2").style.color = "#404040";

    const maxMatchLength = Math.max(unit1.generations, unit2.generations);
    if (unifiedGraph){ unifiedGraph.maxIterations = maxMatchLength; }
    if (profile1){ profile1.maxIterations = maxMatchLength; }
    if (profile2){ profile2.maxIterations = maxMatchLength; }

    globalChargePeak = 10;

    runDuel(); // Existing duel trigger
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

    function setTotalRounds(count, btn) {
        totalRounds = count;
        document.getElementById('total-rounds-display').innerText = count;

        // UI highlight for selected button
        document.querySelectorAll('.round-opt').forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');        
    }

    function runDuel() {

        // 1. Validation: Ensure both units are loaded
        if (!unit1.originHash || !unit2.originHash) {
            document.getElementById('gamestatus').innerText = "ERROR: BOTH UNITS MUST BE LOADED";
            return;
        }

        // If it's the very first round of a match, reset match scores
        if (currentRound === 1) {
            matchScore = { p1: 0, p2: 0 };
            document.getElementById('match-p1').innerText = "0";
            document.getElementById('match-p2').innerText = "0";
            // document.getElementById('match-tally').style.display = 'block';

            if (chargeHist1) chargeHist1.clear();
            if (chargeHist2) chargeHist2.clear();
        }

        /* RESET LOGIC */
        if (duelInterval) clearInterval(duelInterval);
        
        unit1.resetToOrigin(unit1.lastLoadedBin); 
        unit2.resetToOrigin(unit2.lastLoadedBin);
       
        document.getElementById('points1').innerText = "0";
        document.getElementById('points2').innerText = "0";
        document.getElementById('iteration1').innerText = "0";
        document.getElementById('iteration2').innerText = "0";
        document.getElementById('iteration1').style.color = "";
        document.getElementById('iteration2').style.color = "";
        document.getElementById('current-round-display').innerText = currentRound;

        // If arena already exists, clear it; otherwise it's created below
        if (arena) arena.reset();
        
        // Reset Analytics Graphs if they exist
        if (unifiedGraph) unifiedGraph.clear();
        if (profile1) profile1.clear();
        if (profile2) profile2.clear();
        if (chargeHist1) chargeHist1.clear();
        if (chargeHist2) chargeHist2.clear();
        /* END RESET LOGIC */

        const startBtn = document.getElementById('start-match-btn');
        if (startBtn) startBtn.style.display = 'none';

        // 2. Instantiate the Arena
        const arenaGridSize = 96; 
        arena = new ArenaEngine("canvasA", 600, 300, arenaGridSize);

        const maxMatchLength = Math.max(unit1.generations, unit2.generations);
        const totalCells = arena.rows * arena.cols;

        if (!profile1) profile1 = new CellProfileGraph("cellProfile1", maxMatchLength);
        if (!profile2) profile2 = new CellProfileGraph("cellProfile2", maxMatchLength);

        profile1.canvas.width = profile1.canvas.offsetWidth;
        profile2.canvas.width = profile2.canvas.offsetWidth;
        profile1.canvas.height = 30;
        profile2.canvas.height = 30;

        if (!roundGraph1) roundGraph1 = new RoundHistoryGraph("roundHistory1", totalRounds);
        if (!roundGraph2) roundGraph2 = new RoundHistoryGraph("roundHistory2", totalRounds);

        chargeHist1 = new ChargeHistogram('chargeHistogram1');
        chargeHist2 = new ChargeHistogram('chargeHistogram2');
        
        chargeHist1.canvas.width = chargeHist1.canvas.offsetWidth;
        chargeHist2.canvas.width = chargeHist2.canvas.offsetWidth;
        chargeHist1.canvas.height = 30;
        chargeHist2.canvas.height = 30;

        // console.log("[index.php] runDuel(): currentRound = " + currentRound);

        if (currentRound === 1){

            roundGraph1.canvas.width = roundGraph1.canvas.offsetWidth;
            roundGraph2.canvas.width = roundGraph2.canvas.offsetWidth;
            roundGraph1.canvas.height = 30;
            roundGraph2.canvas.height = 30;

            roundGraph1.clear();
            roundGraph2.clear();

        }

        // Instantiate analytics graph engine
        const chartWidth = document.getElementById('canvasA').offsetWidth;
        if (!unifiedGraph) unifiedGraph = new AnalyticsEngine("unifiedChart", maxMatchLength);
        unifiedGraph.canvas.width = chartWidth;
        unifiedGraph.canvas.height = 200;

        // Generate a new repeat value for this match
        const repeatValue = Math.floor(Math.random() * 1000000);
        arena.setSeed(repeatValue);
        document.getElementById("round-seed").innerText = repeatValue;
        
        console.log("Match Seed: " + repeatValue); 
        // document.getElementById('gamestatus').innerText = "SEED: " + repeatValue;

        // 4. Visual Feedback
        // document.getElementById('gamestatus').innerText = "INITIALIZING COLLISION...";
        
        // Stop any existing intervals
        if (duelInterval) clearInterval(duelInterval);

        // 5. The Duel Loop
        setTimeout(() => {
            document.getElementById('gamestatus').innerText = "DUEL IN PROGRESS";
            
            if (totalRounds == 256){ frameDelay = 0; }

            duelInterval = setInterval(() => {

                // 1. Evolve the internal DNA of each Glyph
                const p1Active = unit1.computeNextGeneration();
                const p2Active = unit2.computeNextGeneration();

                // Update UI counters
                document.getElementById('iteration1').innerText = unit1.iteration;
                document.getElementById('currentHash1').innerText = "0x" + unit1.currentHash.substring(0, 16) + "...";

                document.getElementById('iteration2').innerText = unit2.iteration;
                document.getElementById('currentHash2').innerText = "0x" + unit2.currentHash.substring(0, 16) + "...";

                if (!p1Active) { document.getElementById('iteration1').style.color = "#ff4444"; }
                if (!p2Active) { document.getElementById('iteration2').style.color = "#ff4444"; }

                // STOP CONDITION: If both are inactive, kill the loop
                if (!unit1.isActive && !unit2.isActive) {
                    clearInterval(duelInterval);
                    document.getElementById('gamestatus').innerText = "DUEL STABILIZED";
                    
                    // Optional: Run one last render to show the final state
                    arena.render(unit1.intrinsicColor, unit2.intrinsicColor);

                    const finalRoundScore = arena.calculateScore();
                    matchScore.p1 += finalRoundScore.p1;
                    matchScore.p2 += finalRoundScore.p2;

                    if (matchScore.p1 == matchScore.p2){
                        document.getElementById("high-score-p1").style.color = "#ffffff";
                        document.getElementById("high-score-p1").style.color = "#ffffff";
                    } else {
                        if (matchScore.p1 > matchScore.p2){
                            document.getElementById("high-score-p1").style.color = "var(--accent-green)";
                            document.getElementById("high-score-p2").style.color = "#404040";
                        } else {
                            document.getElementById("high-score-p1").style.color = "#404040";
                            document.getElementById("high-score-p2").style.color = "var(--accent-green)";
                        }
                    }

                    // Update number of rounds won in header
                    let roundsWonByP1 = document.getElementById("rounds-won-p1").innerText;
                    let roundsWonByP2 = document.getElementById("rounds-won-p2").innerText;

                    if (finalRoundScore.p1 > finalRoundScore.p2){
                        roundsWonByP1 = parseInt(roundsWonByP1) + 1;
                        document.getElementById("rounds-won-p1").innerText = roundsWonByP1;
                    } else {
                        roundsWonByP2 = parseInt(roundsWonByP2) + 1;
                        document.getElementById("rounds-won-p2").innerText = roundsWonByP2;
                    }                    

                    // Update Match UI
                    document.getElementById('match-p1').innerText = matchScore.p1;
                    document.getElementById('match-p2').innerText = matchScore.p2;

                    roundGraph1.record(finalRoundScore.p1, finalRoundScore.p2);
                    roundGraph2.record(finalRoundScore.p2, finalRoundScore.p1);

                    roundGraph1.render(unit1.intrinsicColor, finalRoundScore.p1);
                    roundGraph2.render(unit2.intrinsicColor, finalRoundScore.p2);

                    if (currentRound < totalRounds) {
                        document.getElementById('gamestatus').innerText = `ROUND ${currentRound} COMPLETE - WAITING...`;
                        currentRound++;

                        let waitBetweenRounds = 3000;
                        if (totalRounds == 256){ waitBetweenRounds = 2000; }

                        setTimeout(runDuel, waitBetweenRounds);
                    } else {
                        document.getElementById('gamestatus').innerText = "MATCH COMPLETE";

                        // NEW: Record to Tournament Manager
                        const winner = matchScore.p1 > matchScore.p2 ? unit1 : unit2;
                        const loser = matchScore.p1 > matchScore.p2 ? unit2 : unit1;
                        
                        tourneyManager.recordResult(
                            document.getElementById('name1').innerText, 
                            document.getElementById('name2').innerText,
                            matchScore.p1, // Adding support for your aggregate points
                            matchScore.p2
                        );

                        // Update Visuals
                        tourneyVisualizer.updateEdge(
                            document.getElementById('name1').innerText, 
                            document.getElementById('name2').innerText
                        );
                        tourneyVisualizer.render();
                        tourneyLeaderboard.render(tourneyManager.standings);

                        currentRound = 1; // Reset for next time button is clicked
                        profile1.history = [];
                        profile2.history = [];

                        setTimeout(playNextTournamentMatch, 3000);
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

                // 5. Update the LIVE SCORE
                const score = arena.calculateScore();
                document.getElementById('points1').innerText = score.p1;
                document.getElementById('points2').innerText = score.p2;

                // Update Analytics
                unifiedGraph.record(score.p1, score.p2);
                unifiedGraph.render(unit1.intrinsicColor, unit2.intrinsicColor);

                const pop1 = unit1.getPopulationCount(); 
                const pop2 = unit2.getPopulationCount();
                profile1.record(pop1);
                profile2.record(pop2);

                // Find the highest population seen by either player so far
                const globalPopMax = Math.max(
                    ...profile1.history.slice(5), 
                    ...profile2.history.slice(5), 
                    10
                );

                // 2. Calculate Global Max for Charge Histogram
                const dist1 = chargeHist1.getDistribution(arena, 1);
                const dist2 = chargeHist2.getDistribution(arena, 2);
                globalChargePeak = Math.max(globalChargePeak, dist1.maxCount, dist2.maxCount);

                profile1.render(unit1.intrinsicColor, globalPopMax);
                profile2.render(unit2.intrinsicColor, globalPopMax);

                if (roundGraph1) roundGraph1.render(unit1.intrinsicColor, score.p1);
                if (roundGraph2) roundGraph2.render(unit2.intrinsicColor, score.p2);

                chargeHist1.render(arena, 1, unit1.intrinsicColor, globalChargePeak);
                chargeHist2.render(arena, 2, unit2.intrinsicColor, globalChargePeak);

            }, frameDelay);
        }, 1000);
    }

    </script>

</body>
</html>