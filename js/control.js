function resetUI(level){

    if (level == "0"){

        resetTopLeft();
        resetTopCentre();

        resetGlyph(1);
        resetGlyph(2);

        if (arena){ arena.reset() };

        matchScore = { p1: 0, p2: 0 };

    }

    if (level == "1"){

        document.getElementById("iteration1").style.color = "#ffffff";
        document.getElementById("iteration2").style.color = "#ffffff";

        if (arena){ arena.reset(); }

        if (unifiedGraph) unifiedGraph.clear();
        if (profile1) profile1.clear();
        if (profile2) profile2.clear();
        if (chargeHist1) chargeHist1.clear();
        if (chargeHist2) chargeHist2.clear();


    }

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

}

function calcScore(){

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

}

function instantiateAnalytics(){

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

function openUnifiedConfig() {
    resetUI(0);
    document.getElementById('unifiedConfigModal').style.display = 'flex';
}

function closeUnifiedConfig() {
    document.getElementById('unifiedConfigModal').style.display = 'none';
}

function setTotalRounds(count, btn) {
    totalRounds = count;
    document.getElementById('total-rounds-display').innerText = count;

    // UI highlight for selected button
    document.querySelectorAll('.round-opt').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');        
}