class Match{

    constructor(glyphA, glyphB, designation, rounds, frameDelay, tournamentId = null){

        this.designation = designation;
        this.currentRound = 1;
        this.totalRounds = rounds;
        this.repeatValue = null;

        this.frameDelay = frameDelay;

        this.algorithm = "Adversarial Conway";
        this.mode = "Combative"

        this.tournamentId = tournamentId;

        this.glyphA = this.findGlyphByName(GlyphRegistry, glyphA);
        this.glyphB = this.findGlyphByName(GlyphRegistry, glyphB);

        if (this.glyphA){ unit1.loadFromBinary(this.glyphA.bin); } else { console.log("[control.js] Match constructor(): Glyph '" + glyphA + "' not found!"); }
        if (this.glyphB){ unit2.loadFromBinary(this.glyphB.bin); } else { console.log("[control.js] Match constructor(): Glyph '" + glyphB + "' not found!"); }      

        this.matchScore = { p1: 0, p2: 0 };
        this.roundsWonByGlyph = { p1: 0, p2: 0 };
        this.matchRoundData = [];

        this.initUI();

    }

    findGlyphByName(glyphArray, targetName) {
        return glyphArray.find(glyph => {
            return glyph.name.toLowerCase() === targetName.toLowerCase();
        });
    }

    run(){

        return new Promise((resolve) => {

            const executeRound = () => {

                const arenaGridSize = 96; 
                arena = new ArenaEngine("canvasA", 600, 300, arenaGridSize); 

                this.repeatValue = Math.floor(Math.random() * 1000000);
                arena.setSeed(this.repeatValue);
                document.getElementById("round-seed").innerText = this.repeatValue;

                unit1.resetToOrigin();
                unit2.resetToOrigin();

                document.getElementById("iteration1").style.color = "#ffffff";
                document.getElementById("iteration2").style.color = "#ffffff";

                //  Estimate spatial and temporal envelope of the match to initialize analytics
                maxMatchLength = Math.max(this.glyphA.gen, this.glyphB.gen);
                totalCells = arena.rows * arena.cols;
                initializeAnalytics();

                if (duelInterval) clearInterval(duelInterval);

                duelInterval = setInterval(() => {

                    // 1. Evolve the Glyph
                    const p1Active = unit1.computeNextGeneration();
                    const p2Active = unit2.computeNextGeneration();

                    this.updateUI();

                    if (!p1Active) { document.getElementById('iteration1').style.color = "#ff4444"; }
                    if (!p2Active) { document.getElementById('iteration2').style.color = "#ff4444"; }   
                    
                    // STOP CONDITION: The round is over once both Glyphs have reached their respective halting state
                    if (!unit1.isActive && !unit2.isActive) {
                        clearInterval(duelInterval); 
                        duelInterval = null;                
                        
                        arena.render(unit1.intrinsicColor, unit2.intrinsicColor); // Run one last render to show the final state

                        this.calcScore();
                        roundGraph1.record(score.p1, score.p2);
                        roundGraph2.record(score.p2, score.p1);

                        this.matchRoundData.push({
                            round_number: this.currentRound,
                            round_seed: this.repeatValue,
                            p1_final_score: score.p1,
                            p2_final_score: score.p2,
                            total_iterations: maxMatchLength
                        });
                        // console.log("[control.js] Match.run(): this.matchRoundData = [" + this.matchRoundData + "].");

                        if (this.currentRound < this.totalRounds) {
                            document.getElementById('gamestatus').innerText = `ROUND ${this.currentRound} COMPLETE - WAITING...`;
                            this.currentRound++;

                            let waitBetweenRounds = 3000;
                            setTimeout(() => { executeRound(); }, waitBetweenRounds);
                        } else {
                            document.getElementById('gamestatus').innerText = "MATCH COMPLETE";                    
                            this.saveMatchToDatabase();
                            this.matchRoundData = [];
                            resolve();
                        }

                        renderAnalytics();
                        return;

                    }

                    arena.applyJitter();

                    // 2. Project ("stamp") the current Glyph configuration onto the Arena
                    arena.stamp(unit1, 1);
                    arena.stamp(unit2, 2);

                    // 3. Render the individual Glyphs
                    unit1.render();
                    unit2.render();

                    // 4. Render the Arena with the updated projection
                    arena.render(unit1.intrinsicColor, unit2.intrinsicColor);
                    score = arena.calculateScore();
                    document.getElementById('points1').innerText = score.p1;
                    document.getElementById('points2').innerText = score.p2;

                    // 5. Render Analytics
                    renderAnalytics();

                }, this.frameDelay);

            };

            executeRound(); // kicking off the first round internally

        });

    }

    initUI(){

        document.getElementById("matchID").innerText = this.designation;
        document.getElementById("total-rounds-display").innerText = this.totalRounds;
        document.getElementById("algo").innerText = this.algorithm;
        document.getElementById("algo-mode").innerText = this.mode;

        document.getElementById("name1").innerText = this.glyphA.name;
        document.getElementById("spec-gen1").innerText = this.glyphA.gen;
        document.getElementById("spec-peak1").innerText = this.glyphA.peak;
        document.getElementById("spec-max1").innerText = this.glyphA.max;
        document.getElementById("spec-min1").innerText = this.glyphA.min;
        document.getElementById("originHash1").innerText = "0x" + this.glyphA.originHash.substring(0, 16) + "...";

        document.getElementById("name2").innerText = this.glyphB.name;
        document.getElementById("spec-gen2").innerText = this.glyphB.gen;
        document.getElementById("spec-peak2").innerText = this.glyphB.peak;
        document.getElementById("spec-max2").innerText = this.glyphB.max;
        document.getElementById("spec-min2").innerText = this.glyphB.min;
        document.getElementById("originHash2").innerText = "0x" + this.glyphB.originHash.substring(0, 16) + "...";

    }

    updateUI(){

        document.getElementById('iteration1').innerText = unit1.iteration;
        document.getElementById('currentHash1').innerText = "0x" + unit1.currentHash.substring(0, 16) + "...";

        document.getElementById('iteration2').innerText = unit2.iteration;
        document.getElementById('currentHash2').innerText = "0x" + unit2.currentHash.substring(0, 16) + "...";

        document.getElementById('current-round-display').innerText = this.currentRound;
        document.getElementById('gamestatus').innerText = `ROUND ${this.currentRound} IN PROGRESS...`;

    }

    calcScore(){

        const finalRoundScore = arena.calculateScore();
        this.matchScore.p1 += finalRoundScore.p1;
        this.matchScore.p2 += finalRoundScore.p2;

        if (this.matchScore.p1 == this.matchScore.p2){
            document.getElementById("high-score-p1").style.color = "#ffffff";
            document.getElementById("high-score-p1").style.color = "#ffffff";
        } else {
            if (this.matchScore.p1 > this.matchScore.p2){
                document.getElementById("high-score-p1").style.color = "var(--accent-green)";
                document.getElementById("high-score-p2").style.color = "#404040";
            } else {
                document.getElementById("high-score-p1").style.color = "#404040";
                document.getElementById("high-score-p2").style.color = "var(--accent-green)";
            }
        }

        if (finalRoundScore.p1 > finalRoundScore.p2){
            this.roundsWonByGlyph.p1 += 1;
            document.getElementById("rounds-won-p1").innerText = this.roundsWonByGlyph.p1;
        } else {
            this.roundsWonByGlyph.p2 += 1;
            document.getElementById("rounds-won-p2").innerText = this.roundsWonByGlyph.p2;
        }                    

        document.getElementById('match-p1').innerText = this.matchScore.p1;
        document.getElementById('match-p2').innerText = this.matchScore.p2;

    }

    saveMatchToDatabase() {
        const payload = {
            tournament_id: (currentActiveMode === 'tournament') ? 1 : null, // Set context id if applicable
            match_designation: this.designation.toUpperCase(),
            p1_glyph_name: this.glyphA.name,
            p2_glyph_name: this.glyphB.name,
            tournament_id: this.tournamentId,
            grid_size: 16,
            arena_width: 640,
            arena_height: 320,
            game_mode: this.mode,
            total_rounds_configured: this.totalRounds,
            p1_rounds_won: this.roundsWonByGlyph.p1,
            p2_rounds_won: this.roundsWonByGlyph.p2,
            rounds: this.matchRoundData
        };

        console.log("[DATABASE API] Dispatched payload packet:", payload);

        // 2. Dispatch data packet asynchronously via HTTP POST straight to your target script endpoint
        fetch('php/save_match.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload) // Convert the native javascript object into a clean JSON string stream
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log(`%c[DATABASE API] SUCCESS: Saved match safely under ID ${data.match_id || 'N/A'}`, 'color: #00aa00;');
            } else {
                console.error("[DATABASE API] SERVER REJECTION:", data.error);
            }
        })
        .catch(error => {
            console.error("[DATABASE API] NETWORK EXCEPTION PROTOCOL CRASHED:", error);
        });
    }

}

function resetUI(){

    resetTopLeft();
    resetTopCentre();

    resetGlyph(1);
    resetGlyph(2);

    if (arena){ arena.reset() };
    deleteAnalytics();

    matchScore = { p1: 0, p2: 0 };

    document.getElementById("iteration1").style.color = "#ffffff";
    document.getElementById("iteration2").style.color = "#ffffff";

    function resetTopLeft(){

        document.getElementById("current-round-display").innerText = "-";
        document.getElementById("total-rounds-display").innerText = "-";
        document.getElementById("round-seed").innerText = "-";
        document.getElementById("algo").innerText = "-";
        document.getElementById("algo-mode").innerText = "-";

    }

    function resetTopCentre(){

        document.getElementById("matchID").innerText = "[ MATCH NAME ]";
        document.getElementById("rounds-won-p1").innerText = "0";
        document.getElementById("match-p1").innerText = "0";
        document.getElementById("high-score-p1").style.color = "#404040";
        document.getElementById("high-score-p2").style.color = "#404040";
        document.getElementById("match-p2").innerText = "0";
        document.getElementById("rounds-won-p2").innerText = "0";
        document.getElementById("gamestatus").innerText = "SYSTEM READY // AWAITING INPUT";

    }

    function resetGlyph(unit){

        if (unit === 1){ 
            unit1 = new LifeEngine("canvas1", 16); } 
        else if (unit === 2){ 
            unit2 = new LifeEngine("canvas2", 16); 
        }

        resetGlyphSpecs(unit);
        resetGlyphStats(unit);

    }

    function resetGlyphSpecs(unit){

        document.getElementById(`name${unit}`).innerText = `UNIT_0${unit}`;
        document.getElementById(`spec-gen${unit}`).innerText = "0";
        document.getElementById(`spec-peak${unit}`).innerText = "0";
        document.getElementById(`spec-max${unit}`).innerText = "0";
        document.getElementById(`spec-min${unit}`).innerText = "0";

    }

    function resetGlyphStats(unit){
        
        document.getElementById(`points${unit}`).innerText = "0";
        document.getElementById(`iteration${unit}`).innerText = "0";
        document.getElementById(`iteration${unit}`).style.color = "#ffffff";
        document.getElementById(`originHash${unit}`).innerText = "0x...";
        document.getElementById(`currentHash${unit}`).innerText = "0x...";

    }

    function deleteAnalytics(){

        profile1 = null;
        profile2 = null;

        roundGraph1 = null;
        roundGraph2 = null;

        chargeHist1 = null;
        chargeHist2 = null;

        unifiedGraph = null;        

    }

}

function openUnifiedConfig() {
    resetUI(0);
    document.getElementById('unifiedConfigModal').style.display = 'flex';
}

function setTotalRounds(count, btn) {
    totalRounds = count;    
    document.querySelectorAll('.round-opt').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected'); // UI highlight for selected button      
}

function closeUnifiedConfig() {
    document.getElementById('unifiedConfigModal').style.display = 'none';
}

function initializeAnalytics(){

        // console.log("[control.js] initializeAnalytics(): Done.");
        clearAnalytics();

        if (!profile1) profile1 = new CellProfileGraph("cellProfile1", maxMatchLength);
        if (!profile2) profile2 = new CellProfileGraph("cellProfile2", maxMatchLength);

        profile1.canvas.width = profile1.canvas.offsetWidth;
        profile2.canvas.width = profile2.canvas.offsetWidth;
        profile1.canvas.height = 30;
        profile2.canvas.height = 30;

        if (!roundGraph1) roundGraph1 = new RoundHistoryGraph("roundHistory1", totalRounds);
        if (!roundGraph2) roundGraph2 = new RoundHistoryGraph("roundHistory2", totalRounds);

        roundGraph1.canvas.width = roundGraph1.canvas.offsetWidth;
        roundGraph2.canvas.width = roundGraph2.canvas.offsetWidth;
        roundGraph1.canvas.height = 30;
        roundGraph2.canvas.height = 30;

        if (!chargeHist1) chargeHist1 = new ChargeHistogram('chargeHistogram1');
        if (!chargeHist2) chargeHist2 = new ChargeHistogram('chargeHistogram2');
        
        chargeHist1.canvas.width = chargeHist1.canvas.offsetWidth;
        chargeHist2.canvas.width = chargeHist2.canvas.offsetWidth;
        chargeHist1.canvas.height = 30;
        chargeHist2.canvas.height = 30;

        const chartWidth = document.getElementById('canvasA').offsetWidth;
        if (!unifiedGraph) unifiedGraph = new AnalyticsEngine("unifiedChart", maxMatchLength);
        unifiedGraph.canvas.width = chartWidth;
        unifiedGraph.canvas.height = 200;

}

function renderAnalytics(){

    // console.log("[control.js] renderAnalytics(): Doing ma thang!");

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

}

function clearAnalytics(){

    if (unifiedGraph) unifiedGraph.clear();
    if (profile1) profile1.clear();
    if (profile2) profile2.clear();
    if (chargeHist1) chargeHist1.clear();
    if (chargeHist2) chargeHist2.clear();

}