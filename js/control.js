function resetUI(level){

    if (level == "0"){

        resetTopLeft();
        resetTopCentre();

        resetGlyph(1);
        resetGlyph(2);

        if (arena){ arena.reset() };

    }

    function resetTopLeft(){

        document.getElementById("current-round-display").innerText = "0";
        document.getElementById("total-rounds-display").innerText = "?";
        document.getElementById("round-seed").innerText = "-";
        document.getElementById("algo").innerText = "-";
        document.getElementById("algo-mode").innerText = "-";

    }

    function resetTopCentre(){

        document.getElementById("matchID").innerText = "[ MATCH NAME ]";
        document.getElementById("rounds-won-p1").innerText = "0";
        document.getElementById("match-p1").innerText = "0";
        document.getElementById("match-p2").innerText = "0";
        document.getElementById("rounds-won-p2").innerText = "0";
        document.getElementById("gamestatus").innerText = "SYSTEM READY // AWAITING INPUT";

    }

    function resetGlyph(unit){

        console.log("Resetting canvas" + unit + " now...");

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