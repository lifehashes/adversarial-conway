class Match{

    constructor(glyphA, glyphB, designation, rounds, frameDelay){

        this.glyphA = findGlyphByName(GlyphRegistry, glyphA);
        this.glyphB = findGlyphByName(GlyphRegistry, glyphB);

        this.designation = designation;
        this.currentRound = 1;
        this.totalRounds = rounds;

        this.frameDelay = frameDelay;

        this.algorithm = "Adversarial Conway";
        this.mode = "Combative"

        // UI Updates
        document.getElementById("matchID").innerText = this.designation;
        document.getElementById("total-rounds-display").innerText = this.totalRounds;
        document.getElementById("algo").innerText = this.algorithm;
        document.getElementById("algo-mode").innerText = this.mode;

        unit1.loadFromBinary(this.glyphA.bin);
        unit2.loadFromBinary(this.glyphB.bin);

        // HELPER FUNCTION
        function findGlyphByName(glyphArray, targetName) {
            return glyphArray.find(glyph => {
                return glyph.name.toLowerCase() === targetName.toLowerCase();
            });
        }

    }

    run(){

        const arenaGridSize = 96; 
        arena = new ArenaEngine("canvasA", 600, 300, arenaGridSize); 

        const repeatValue = Math.floor(Math.random() * 1000000);
        arena.setSeed(repeatValue);
        document.getElementById("round-seed").innerText = repeatValue;

        unit1.resetToOrigin();
        unit2.resetToOrigin();

        document.getElementById("iteration1").style.color = "#ffffff";
        document.getElementById("iteration2").style.color = "#ffffff";

        if (duelInterval) clearInterval(duelInterval);

        duelInterval = setInterval(() => {

            // 1. Evolve the internal DNA of each Glyph
            const p1Active = unit1.computeNextGeneration();
            const p2Active = unit2.computeNextGeneration();

            this.updateUI();

            if (!p1Active) { document.getElementById('iteration1').style.color = "#ff4444"; }
            if (!p2Active) { document.getElementById('iteration2').style.color = "#ff4444"; }   
            
            // STOP CONDITION: The round is over once both Glyphs have reached their respective halting state
            if (!unit1.isActive && !unit2.isActive) {
                clearInterval(duelInterval); 
                duelInterval = null;
                
                // Run one last render to show the final state
                arena.render(unit1.intrinsicColor, unit2.intrinsicColor);

                // calcScore();
                // roundGraph1.record(score.p1, score.p2);
                // roundGraph2.record(score.p2, score.p1);

                if (this.currentRound < this.totalRounds) {
                    document.getElementById('gamestatus').innerText = `ROUND ${this.currentRound} COMPLETE - WAITING...`;
                    this.currentRound++;

                    let waitBetweenRounds = 3000;
                    setTimeout(() => { this.run(); }, waitBetweenRounds);
                } else {
                    document.getElementById('gamestatus').innerText = "MATCH COMPLETE";
                }

                // renderAnalytics();
                return;

            }

            arena.applyJitter();

            // 2. "Project" the current DNA onto the Arena
            arena.stamp(unit1, 1);
            arena.stamp(unit2, 2);

            // 3. Render the individual Glyphs
            unit1.render();
            unit2.render();

            // 4. Render the Arena
            arena.render(unit1.intrinsicColor, unit2.intrinsicColor);
            score = arena.calculateScore();
            document.getElementById('points1').innerText = score.p1;
            document.getElementById('points2').innerText = score.p2;

            // 5. Render Analytics
            // renderAnalytics();

        }, this.frameDelay);

    }

    updateUI(){

        document.getElementById('iteration1').innerText = unit1.iteration;
        document.getElementById('currentHash1').innerText = "0x" + unit1.currentHash.substring(0, 16) + "...";

        document.getElementById('iteration2').innerText = unit2.iteration;
        document.getElementById('currentHash2').innerText = "0x" + unit2.currentHash.substring(0, 16) + "...";

        document.getElementById('current-round-display').innerText = this.currentRound;
        document.getElementById('gamestatus').innerText = `ROUND ${this.currentRound} IN PROGRESS...`;

    }

}

function resetUI(){

    resetTopLeft();
    resetTopCentre();

    resetGlyph(1);
    resetGlyph(2);

    if (arena){ arena.reset() };
    clearAnalytics();
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

    function clearAnalytics(){

        if (unifiedGraph) unifiedGraph.clear();
        if (profile1) profile1.clear();
        if (profile2) profile2.clear();
        if (chargeHist1) chargeHist1.clear();
        if (chargeHist2) chargeHist2.clear();

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