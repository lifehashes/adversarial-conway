function resetUI(level){

    if (level == "0"){

        resetTopLeft();
        resetTopCentre();

        resetGlyph(1);
        resetGlyph(2);

        if (arena){ arena.reset() };

        matchScore = { p1: 0, p2: 0 };

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