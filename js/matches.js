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

        if (this.glyphA){ unit1.loadFromBinary(this.glyphA.bin); } else { console.log("[match.js] Match constructor(): Glyph '" + glyphA + "' not found!"); }
        if (this.glyphB){ unit2.loadFromBinary(this.glyphB.bin); } else { console.log("[match.js] Match constructor(): Glyph '" + glyphB + "' not found!"); }      

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
                        // console.log("[match.js] Match.run(): this.matchRoundData = [" + this.matchRoundData + "].");

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
        document.getElementById("owner1").innerText = this.glyphA.owner;
        document.getElementById("spec-gen1").innerText = this.glyphA.gen;
        document.getElementById("spec-peak1").innerText = this.glyphA.peak;
        document.getElementById("spec-max1").innerText = this.glyphA.max;
        document.getElementById("spec-min1").innerText = this.glyphA.min;
        document.getElementById("originHash1").innerText = "0x" + this.glyphA.originHash.substring(0, 16) + "...";

        document.getElementById("name2").innerText = this.glyphB.name;
        document.getElementById("owner2").innerText = this.glyphB.owner;
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

class Tournament {
    constructor(contestants, mode = 'round-robin') {
        this.contestants = contestants; 
        this.mode = mode;
        this.matchQueue = [];
        this.results = [];
        this.currentMatchIndex = 0;
        this.standings = {};
        this.dbTourneyId = null;
        this.survivors = [...contestants]; // this tracks the remaining contestants for the knock-out variant

        this.init();
    }

    init() {
        if (this.mode === 'round-robin') {
            this.generateRoundRobin();
        } 
        if (this.mode === 'knock-out'){
            this.generateKnockOut();
        }
        // Initialize standings
        this.contestants.forEach(c => {
            this.standings[c.name] = { wins: 0, losses: 0, points: 0, played: 0 };
        });
    }

    generateRoundRobin() {
        for (let i = 0; i < this.contestants.length; i++) {
            for (let j = 0; j < this.contestants.length; j++) {
                if (i !== j) {
                    this.matchQueue.push({ p1: this.contestants[i], p2: this.contestants[j] }); // Play everyone twice (Home/Away style)
                }
            }
        }
    }

    generateKnockOut() {
        this.matchQueue = [];
        this.currentMatchIndex = 0;

        if (this.survivors.length === 2) {
            this.matchQueue.push({ p1: this.survivors[0], p2: this.survivors[1], wing: 'left', slot: 0 });
            return;
        }

        // console.log("[tournament.js] Tournament{}.generateKnockOut(): survivors = " + this.survivors.map(c => c ? c.name : "null").join(", "));

        // --- ROUND 0: Initial seeding ---
        if (this.results.length === 0) {
            let leftSlotCounter = 0;
            let rightSlotCounter = 0;

            for (let i = 0; i < this.survivors.length; i += 2) {
                const isLeft = i < this.contestants.length / 2;
                const wing = isLeft ? 'left' : 'right';
                const slot = isLeft ? leftSlotCounter++ : rightSlotCounter++;

                this.matchQueue.push({
                    p1: this.survivors[i],
                    p2: this.survivors[i+1],
                    wing: wing,
                    slot: slot
                });
            }
        } 
        // --- DEEPER ROUNDS: Group by structural targets ---
        else {
            // We will organize players into a map keyed by "wing_targetSlot"
            const prospectiveMatches = {};

            this.survivors.forEach(player => {
                // FIX: Look up the LATEST historical match won by this player by scanning backwards
                let historicalMatch = null;
                for (let i = this.results.length - 1; i >= 0; i--) {
                    if (this.results[i].winner.name === player.name && this.results[i].status === 'eliminated') {
                        historicalMatch = this.results[i];
                        break;
                    }
                }
                
                const wing = historicalMatch ? historicalMatch.wing : 'left';
                const targetSlot = historicalMatch ? Math.floor(historicalMatch.slot / 2) : 0;
                const key = `${wing}_${targetSlot}`;

                if (!prospectiveMatches[key]) {
                    prospectiveMatches[key] = [];
                }
                prospectiveMatches[key].push(player);
            });

            // Now convert those grouped structural pairs into actual match queue items
            Object.keys(prospectiveMatches).forEach(key => {
                const [wing, slotStr] = key.split('_');
                const slot = parseInt(slotStr, 10);
                const players = prospectiveMatches[key];

                if (players.length === 2) {
                    this.matchQueue.push({
                        p1: players[0],
                        p2: players[1],
                        wing: wing,
                        slot: slot
                    });
                } else if (players.length === 1) {
                    // Safeguard/Fallback: If a player gets a bye due to un-even brackets
                    console.warn(`[tournament.js] Player ${players[0].name} is stranded without an opponent in ${wing} slot ${slot}`);
                }
            });
        }
    }   

    getNextMatch() {
        if (this.currentMatchIndex < this.matchQueue.length) {
            return this.matchQueue[this.currentMatchIndex];
        }
        return null;
    }

    recordResult(p1Name, p2Name, p1Score, p2Score) {
        const p1 = this.standings[p1Name];
        const p2 = this.standings[p2Name];

        let winnerName = null;
        let loserName = null;

        if (p1Score > p2Score) { 
            p1.wins++; p2.losses++;
            winnerName = p1Name;
            loserName = p2Name;
        } else if (p2Score > p1Score) { 
            p2.wins++; p1.losses++;
            winnerName = p2Name;
            loserName = p1Name;
        } else {
            // Tie-breaker for Knock-Out if scores are perfectly even
            if (this.mode === 'knock-out') {
                const coinFlip = Math.random() > 0.5;
                winnerName = coinFlip ? p1Name : p2Name;
                loserName = coinFlip ? p2Name : p1Name;
                if(coinFlip) { p1.wins++; p2.losses++; } else { p2.wins++; p1.losses++; }
                console.log(`[tournament.js] Tie broken by coin flip. Winner: ${winnerName}`);
            } else {
                // Standard round robin tie fallback
                p1.points += p1Score; p2.points += p2Score;
                p1.played++; p2.played++;
                return; 
            }
        }

        p1.points += p1Score;
        p2.points += p2Score;
        
        p1.played++;
        p2.played++;

        // For knock-out, eliminate the loser from the tournament survivors array
        if (this.mode === 'knock-out' && loserName) {
            this.survivors = this.survivors.filter(c => c.name !== loserName);

            const currentMatch = this.matchQueue[this.currentMatchIndex];
            const winnerGlyph = this.contestants.find(c => c.name === winnerName);
            
            tourneyVisualizer.recordBracketResult(currentMatch.wing, currentMatch.slot, winnerGlyph);

            this.results.push({ 
                winner: winnerGlyph, 
                loser: this.contestants.find(c => c.name === loserName),
                status: 'eliminated',
                wing: currentMatch.wing,  // Track the wing
                slot: currentMatch.slot   // Track the slot!
            });
        }
    }

    async startTournament(){
        // console.log("[tournament.js] Initializing master tournament entry...");
        const tournamentName = document.getElementById("matchNameInput").value.trim() || "Automated Round Robin";
        
        try {
            const response = await fetch('php/save_tournament.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name: tournamentName, mode: this.mode })
            });

            const result = await response.json();

            if (result.success) {
                this.dbTourneyId = result.tournament_id;
                // console.log("[tournament.js] Database tournament registered successfully. ID: " + this.dbTourneyId);
                
                // Visualizers initialization
                tourneyVisualizer = new TournamentVisualizer("tournamentPolygon", this.contestants, this.mode);
                tourneyLeaderboard = new TournamentLeaderboard("tournamentLeaderboard", this.contestants, this.mode);

                this.runTournament();
            } else {
                // console.error("[tournament.js] Database rejected tournament creation: ", result.error);
            }
        } catch (error) {
            console.error("[tournament.js] Network failure initializing tournament: ", error);
        }
    }

    async runTournament(){
        let mI = this.currentMatchIndex;
        let tL = this.matchQueue.length;

        // console.log("[tournament.js] Tournament{}: Tournament in progress: Now simulating match " + mI + " of " + tL + ".");
        let waitBetweenMatches = 3000;

        if (mI < tL){
            const glyphA = this.getNextMatch().p1.name;
            const glyphB = this.getNextMatch().p2.name;
            let executionName = document.getElementById("matchNameInput").value.trim() || "UNNAMED_ENGAGEMENT";
            executionName = executionName + "_M_" + mI;

            tourneyVisualizer.render(glyphA, glyphB);
            tourneyLeaderboard.render(this.standings);

            let myMatch = new Match(glyphA, glyphB, executionName, totalRounds, frameDelay, this.dbTourneyId);

            await myMatch.run();
            await new Promise(resolve => setTimeout(resolve, waitBetweenMatches));

            this.recordResult(glyphA, glyphB, myMatch.matchScore.p1, myMatch.matchScore.p2);

            tourneyVisualizer.updateEdge(document.getElementById('name1').innerText, document.getElementById('name2').innerText);
            tourneyVisualizer.render();
            tourneyLeaderboard.render(this.standings);

            resetUI();
            this.currentMatchIndex += 1;
            this.runTournament(); 
        } else {
            if (this.mode === 'knock-out' && this.survivors.length > 1) {
                // console.log(`[tournament.js] Round complete. ${this.survivors.length} glyphs survive. Generating next round...`);
                
                tourneyVisualizer.advanceRoundTier();     
                
                // CRITICAL FIX: Reset the index back to 0 for the upcoming round's queue!
                this.currentMatchIndex = 0; 
                            
                this.generateKnockOut(); 
                this.runTournament(); 
            } else {
                console.log("[tournament.js] Tournament completed.");
                if(this.mode === 'knock-out' && this.survivors.length === 1) {
                    tourneyVisualizer.setGrandChampion(this.survivors[0]);
                    console.log(`[tournament.js] GRAND CHAMPION: ${this.survivors[0].name}`);
                }
            }
        }
    }
}

class TournamentVisualizer {
    constructor(canvasId, contestants, mode) {
        this.canvas = document.getElementById(canvasId);
        this.ctx = this.canvas.getContext('2d');
        this.contestants = contestants;
        this.mode = mode;

        this.nodes = [];
        this.edgeStatus = {}; 
        
        this.maxRounds = Math.ceil(Math.log2(contestants.length));
        this.currentVisualRound = 0;
        this.bracketStructure = []; 
        
        if (this.mode === 'round-robin'){ this.initPolygonNodes(); }
        if (this.mode === 'knock-out'){ this.initBracket(); }
    }

    initPolygonNodes() {
        const cx = this.canvas.width / 2;
        const cy = this.canvas.height / 2;
        const radius = Math.min(cx, cy) - 30;

        this.contestants.forEach((c, i) => {
            const angle = (i / this.contestants.length) * 2 * Math.PI - Math.PI / 2;
            this.nodes.push({ x: cx + radius * Math.cos(angle), y: cy + radius * Math.sin(angle), glyph: c });
        });
    }

    initBracket() {
        this.bracketStructure = {
            left: [],
            right: []
        };

        // Layer 0 needs to hold the initial SEEDS (Contestants)
        // For 8 contestants, that's 4 contestants per wing on Layer 0.
        const halfContestants = Math.ceil(this.contestants.length / 2);
        
        // We iterate up to maxRounds + 1 to include the initial seeding layer
        for (let round = 0; round <= this.maxRounds; round++) {
            const slotsInRound = Math.ceil(halfContestants / Math.pow(2, round));
            
            if (slotsInRound > 0) {
                this.bracketStructure.left.push(new Array(slotsInRound).fill(null));
                this.bracketStructure.right.push(new Array(slotsInRound).fill(null));
            }
        }

        // --- PRE-POPULATE LAYER 0 WITH INITIAL CONTESTANTS ---
        let leftIdx = 0;
        let rightIdx = 0;
        for (let i = 0; i < this.contestants.length; i++) {
            const isLeft = i < this.contestants.length / 2;
            if (isLeft) {
                this.bracketStructure.left[0][leftIdx++] = this.contestants[i];
            } else {
                this.bracketStructure.right[0][rightIdx++] = this.contestants[i];
            }
        }
        
        this.grandChampion = null; 
    }

    advanceRoundTier() {
        this.currentVisualRound++;
    }

    recordBracketResult(wing, slotIndex, winnerGlyph) {
        // Because Layer 0 is occupied by initial seeds, 
        // the results of currentVisualRound (0, 1, etc.) belong in layer (currentVisualRound + 1)
        const targetLayer = this.currentVisualRound + 1;
        if (this.bracketStructure[wing] && this.bracketStructure[wing][targetLayer]) {
            this.bracketStructure[wing][targetLayer][slotIndex] = winnerGlyph;
        }
    }

    setGrandChampion(winnerGlyph) {
        this.grandChampion = winnerGlyph;
    }

    // HELPER
    getBracketNodeCoords(wing, roundIndex, slotIndex) {
        const w = this.canvas.width;
        const h = this.canvas.height;
        const padding = 10;
        
        // Calculate horizontal spacing based on max rounds
        const horizontalStep = (w / 2 - padding) / this.maxRounds;
        
        let x;
        if (wing === 'left') {
            x = padding + (roundIndex * horizontalStep);
        } else {
            x = w - padding - (roundIndex * horizontalStep);
        }

        // Calculate vertical spacing dynamically per round
        const totalSlotsInRound = this.bracketStructure[wing][roundIndex].length;
        const verticalStep = (h - 2 * padding) / (totalSlotsInRound + 1);
        const y = padding + (slotIndex + 1) * verticalStep;

        return { x, y };
    }

    updateEdge(idA, idB) {
        if (this.mode === 'round-robin'){
            const key = [idA, idB].sort().join('_');
            this.edgeStatus[key] = (this.edgeStatus[key] || 0) + 1;
        }
    }

    render(activeP1Name, activeP2Name) {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

        if (this.mode === 'round-robin') {
            this.renderRoundRobin(activeP1Name, activeP2Name);
        } 
        if (this.mode === 'knock-out') {
            this.renderBracket(activeP1Name, activeP2Name);
        }
    }

    renderRoundRobin(activeP1Name, activeP2Name) {
        const ctx = this.ctx;
        for (let i = 0; i < this.nodes.length; i++) {
            for (let j = i + 1; j < this.nodes.length; j++) {
                const n1 = this.nodes[i];
                const n2 = this.nodes[j];
                const key = [n1.glyph.name, n2.glyph.name].sort().join('_');
                const status = this.edgeStatus[key] || 0;

                let color = 'rgba(100, 100, 100, 0.3)';
                let lineWidth = 2;

                if ((n1.glyph.name === activeP1Name && n2.glyph.name === activeP2Name) ||
                    (n1.glyph.name === activeP2Name && n2.glyph.name === activeP1Name)) {
                    color = '#FFFFFF';
                    lineWidth = 3;
                } else if (status === 1) {
                    color = '#2d5a27';
                } else if (status === 2) {
                    color = '#42f485';
                }

                ctx.beginPath();
                ctx.moveTo(n1.x, n1.y);
                ctx.strokeStyle = color;
                ctx.lineWidth = lineWidth;
                ctx.lineTo(n2.x, n2.y);
                ctx.stroke();
            }
        }

        this.nodes.forEach(node => {
            const isActive = node.glyph.name === activeP1Name || node.glyph.name === activeP2Name;
            
            ctx.beginPath();
            ctx.arc(node.x, node.y, 8, 0, Math.PI * 2);
            ctx.fillStyle = node.glyph.intrinsicColor || "#444"; 
            ctx.shadowBlur = isActive ? 15 : 0;
            ctx.shadowColor = node.glyph.intrinsicColor || "#FFF";
            ctx.fill();
            
            if(isActive) {
                ctx.strokeStyle = '#FFF';
                ctx.lineWidth = 2;
                ctx.stroke();
            }
        });
    }

    renderBracket(activeP1Name, activeP2Name) {
        const ctx = this.ctx;
        const w = this.canvas.width;
        const h = this.canvas.height;

        // 1. Draw Bracket Tree Lines & Nodes
        ['left', 'right'].forEach(wing => {
            const rounds = this.bracketStructure[wing];
            
            rounds.forEach((roundSlots, roundIdx) => {
                roundSlots.forEach((glyph, slotIdx) => {
                    const { x, y } = this.getBracketNodeCoords(wing, roundIdx, slotIdx);

                    // If not the absolute final tier node of the wing, draw tree paths forward
                    if (roundIdx < rounds.length - 1) {
                        const nextSlotIdx = Math.floor(slotIdx / 2);
                        const nextCoords = this.getBracketNodeCoords(wing, roundIdx + 1, nextSlotIdx);
                        
                        ctx.beginPath();
                        ctx.moveTo(x, y);
                        // Clean squared orthodontic-style bracket pathing
                        ctx.lineTo((x + nextCoords.x) / 2, y);
                        ctx.lineTo((x + nextCoords.x) / 2, nextCoords.y);
                        ctx.lineTo(nextCoords.x, nextCoords.y);
                        ctx.strokeStyle = 'rgba(100, 100, 100, 0.4)';
                        ctx.lineWidth = 1.5;
                        ctx.stroke();
                    } else {
                        // Connect final tier nodes directly to the Grand Final center spotlight
                        ctx.beginPath();
                        ctx.moveTo(x, y);
                        ctx.lineTo(w / 2, h / 2);
                        ctx.strokeStyle = 'rgba(150, 150, 150, 0.3)';
                        ctx.lineWidth = 2;
                        ctx.stroke();
                    }

                    // Render node circle anchor
                    ctx.beginPath();
                    ctx.arc(x, y, 6, 0, Math.PI * 2);
                    ctx.fillStyle = glyph ? (glyph.intrinsicColor || "#42f485") : "rgba(50, 50, 50, 0.5)";
                    ctx.fill();

                    // Draw text labels safely if a participant occupies this spot
                    if (glyph && glyph.name) {
                        ctx.fillStyle = "#FFF";
                        ctx.font = "10px 'Courier New'";
                        
                        // Push text outward on the left wing, inward on the right wing so it never overlaps paths
                        const textOffset = wing === 'left' ? -25 : 15;
                        
                        // Cleanly slice names that are too long to prevent chaotic overlaps
                        const displayName = glyph.name
                            .split(' ')
                            .map(word => word.charAt(0).toUpperCase())
                            .join('');
                        ctx.fillText(displayName, x + textOffset, y + 3);
                    }
                });
            });
        });

        // 2. Render Grand Champion Showcase
        if (this.grandChampion) {
            const cx = w / 2;
            const cy = h / 2;

            ctx.beginPath();
            ctx.arc(cx, cy, 22, 0, Math.PI * 2);
            ctx.strokeStyle = this.grandChampion.intrinsicColor || "#FFD700";
            ctx.lineWidth = 4;
            ctx.shadowBlur = 20;
            ctx.shadowColor = this.grandChampion.intrinsicColor || "#FFD700";
            ctx.stroke();
            ctx.shadowBlur = 0; 

            ctx.fillStyle = "#FFD700";
            ctx.font = "bold 13px 'Courier New'";
            ctx.textAlign = "center";
            ctx.fillText("GRAND CHAMPION", cx, cy - 32);
            ctx.fillStyle = "#FFF";
            ctx.fillText(this.grandChampion.name, cx, cy + 38);
            ctx.textAlign = "start";
        }
    }
}

class TournamentLeaderboard {
    constructor(canvasId, contestants, mode) {
        this.canvas = document.getElementById(canvasId);
        this.ctx = this.canvas.getContext('2d');
        this.contestants = contestants;
        this.totalMatchesPerGlyph = (contestants.length - 1) * 2;
        this.maxRounds = Math.ceil(Math.log2(this.contestants.length));
        this.mode = mode;
    }

    render(standings) {
        const ctx = this.ctx;
        const w = this.canvas.width;
        const h = this.canvas.height;
        ctx.clearRect(0, 0, w, h);

        ctx.font = "11px 'Courier New'";
        ctx.fillStyle = "rgba(209, 209, 209, 0.5)";
        ctx.fillText("GLYPH", 10, 20);
        ctx.fillText("P", 120, 20);
        ctx.fillText("W", 145, 20);
        ctx.fillText("PTS", 175, 20);

        const sorted = Object.keys(standings).sort((a, b) => {
            return standings[b].points - standings[a].points;
        });

        const rowHeight = 25;
        const topPadding = 40;

        sorted.forEach((name, i) => {
            const data = standings[name];
            const y = topPadding + (i * rowHeight);
            const glyphObj = this.contestants.find(c => c.name === name);

            ctx.fillStyle = "rgba(255,255,255,0.05)";
            ctx.fillRect(10, y + 5, w - 20, 15);

            // const progress = (data.wins + data.losses) / this.totalMatchesPerGlyph;

            // Calculate the maximum possible games a single competitor can play in this variant
            let maxPossibleGames = this.totalMatchesPerGlyph;
            if (this.mode === 'knock-out') {
                // In knock-out, the absolute maximum matches a Grand Champion plays is maxRounds
                maxPossibleGames = this.maxRounds;
            }

            // Compute progress percentage against the correct baseline
            const totalPlayed = data.wins + data.losses;
            const progress = maxPossibleGames > 0 ? (totalPlayed / maxPossibleGames) : 0;

            ctx.fillStyle = glyphObj.intrinsicColor || "#42f485";
            ctx.globalAlpha = 0.3;
            ctx.fillRect(10, y + 5, (w - 20) * progress, 15);
            ctx.globalAlpha = 1.0;

            ctx.fillStyle = "#FFF";
            ctx.font = "bold 11px 'Courier New'";
            ctx.fillText(name.substring(0, 12), 15, y + 16);
            
            ctx.font = "10px 'Courier New'";
            ctx.fillStyle = "var(--frame-grey)";
            ctx.fillText(data.wins + data.losses, 120, y + 16);
            ctx.fillText(data.wins, 145, y + 16);
            ctx.fillText(Math.floor(data.points).toLocaleString(), 175, y + 16);
        });
    }
}