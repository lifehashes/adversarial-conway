/* MODAL CONTROL */

function openUnifiedConfig() {
    resetUI(0);
    document.getElementById('unifiedConfigModal').style.display = 'flex';
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

        setTournamentN(2);
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

function setTotalRounds(count, btn) {
    totalRounds = count;    
    document.querySelectorAll('.round-opt').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected'); // UI highlight for selected button      
}

function closeUnifiedConfig() {
    document.getElementById('unifiedConfigModal').style.display = 'none';

    const selectedTourneyVariant = document.getElementById("tournament-variant-select").value;
    openTourneyConfig(selectedTournamentN, currentActiveMode, selectedTourneyVariant);
}

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

function openTourneyConfig(N, mode, variant){

    // 0. Set up the selection screen
    const modal = document.getElementById('tournament-modal');
    const container = document.getElementById('modal-canvas-container');
    container.innerHTML = ''; // Clear previous    
    modal.style.display = 'flex';
    const centerX = container.offsetWidth / 2;
    const centerY = container.offsetHeight / 2;
    const radius = Math.min(centerX, centerY) * 1.2;

    // 1a. Copy the GlyphRegistry to create a pool, then apply Fisher-Yates Shuffle to randomize it
    activeTournamentPool = getFilteredPool();
    for (let i = activeTournamentPool.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [activeTournamentPool[i], activeTournamentPool[j]] = [activeTournamentPool[j], activeTournamentPool[i]];
    }

    // 1b. Select N entries from the randomized pool
    activeTournamentPool = activeTournamentPool.slice(0, N);

    modalEngines = []; // this is where we will instantiate the individual Game of Life engines to preview the Glyphs

    activeTournamentPool.forEach((glyph, i) => {

        let canvasId = null;
        let canvas = null;
        let label = null;

        if (variant == 'round-robin'){

            // 1. Calculate position on the Polygon
            const angle = (i / N) * Math.PI * 2 - (Math.PI / 2);
            const x = centerX + radius * Math.cos(angle);
            const y = centerY + radius * Math.sin(angle);

            // 2. Create Canvas Element
            canvasId = `modal-canvas-${i}`;
            canvas = document.createElement('canvas');
            canvas.id = canvasId;
            canvas.className = 'modal-glyph-canvas';
            canvas.width = 120; // Size of the mini-display
            canvas.height = 120;
            canvas.style.left = `${x}px`;
            canvas.style.top = `${y}px`;        
            container.appendChild(canvas);

            // 3. Append labels
            label = document.createElement('div');
            label.className = 'modal-glyph-label';    
            label.style.left = `${x}px`; // Offset the label based on the same X, Y as the canvas
            label.style.top = `${y}px`;

            if (x > centerX) {
                label.style.transform = 'translate(65px, -50%)';
                label.style.alignItems = 'flex-start';
                label.style.borderLeft = `3px solid ${glyph.intrinsicColor || "#42f485"}`;
                label.style.borderRight = 'none';
            } else {
                label.style.transform = 'translate(-100%, -50%)';
                label.style.left = `${x - 65}px`; // Shift it left of the canvas
                label.style.alignItems = 'flex-end';
                label.style.textAlign = 'right';
                label.style.borderRight = `3px solid ${glyph.intrinsicColor || "#42f485"}`;
                label.style.borderLeft = 'none';
            }

        }

        if (variant == 'knock-out'){}

        // 4. Inject the Data
        label.innerHTML = `
            <h3>${glyph.name}</h3>
            <div class="modal-glyph-stats">
                <span>G:<span class="stat-val">${glyph.gen}</span></span>
                <span>P:<span class="stat-val">${glyph.peak}</span></span>
                <span>MN:<span class="stat-val">${glyph.min}</span></span>
                <span>MX:<span class="stat-val">${glyph.max}</span></span>
            </div>
        `;

        // Apply the glyph's unique color to the border
        label.style.borderLeftColor = glyph.intrinsicColor || "#42f485";

        container.appendChild(label);

        // 5. Initialize the LifeEngine 
        const engine = new LifeEngine(canvasId, 16);
        engine.intrinsicColor = glyph.intrinsicColor || "#42f485";
        engine.loadFromBinary(glyph.bin);        
        modalEngines.push(engine);

        let lastTime = 0;
        const throttleSpeed = 50; // The delay in milliseconds. Higher = Slower.
        animateModalPreview();

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

    });

}

function populateTourneyConfig(){
}

function closeTourneyConfig(){
    document.getElementById('tournament-modal').style.display = 'none';
    executeSystemEngagement();
}

/* U.I. CONTROL */

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

/* ANALYTICS CONTROL */

function initializeAnalytics(){

        // console.log("[match.js] initializeAnalytics(): Done.");
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

    // console.log("[match.js] renderAnalytics(): Doing ma thang!");

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