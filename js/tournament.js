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
        let shuffled = [...this.survivors].sort(() => 0.5 - Math.random());

        this.matchQueue = [];
        this.currentMatchIndex = 0;

        for (let i = 0; i < shuffled.length; i += 2) {
            if (shuffled[i+1]) {
                this.matchQueue.push({ p1: shuffled[i], p2: shuffled[i+1] });
            } else {                
                this.results.push({ winner: shuffled[i], loser: null, status: 'bye' }); // Bye round logic if odd number
            }
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
            // Conway engines can tie, so we pick a random survivor if needed
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

            const matchSlotIndex = this.currentMatchIndex;
            tourneyVisualizer.recordBracketResult(
                matchSlotIndex,
                this.contestants.find(c => c.name === winnerName)
            );

            this.results.push({ 
                winner: this.contestants.find(c => c.name === winnerName), 
                loser: this.contestants.find(c => c.name === loserName), 
                status: 'eliminated' 
            });
        }
    }

    async startTournament(){
        console.log("[tournament.js] Initializing master tournament entry...");
        
        const tournamentName = document.getElementById("matchNameInput").value.trim() || "Automated Round Robin";
        
        try {
            // 1. Ping save_tournament.php FIRST to create the parent row
            const response = await fetch('php/save_tournament.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    name: tournamentName,
                    mode: this.mode // or pull from a select dropdown if you add one
                })
            });

            const result = await response.json();

            if (result.success) {
                // 2. Capture the real, auto-incremented ID from your DB schema
                this.dbTourneyId = result.tournament_id;
                console.log("[tournament.js] Database tournament registered successfully. ID: " + this.dbTourneyId);
                
                // 3a. Initialize Visuals
                tourneyVisualizer = new TournamentVisualizer("tournamentPolygon", this.contestants, this.mode);
                tourneyLeaderboard = new TournamentLeaderboard("tournamentLeaderboard", this.contestants);

                // 3b. NOW it is safe to kick off your async match loops!
                this.runTournament();
            } else {
                console.error("[tournament.js] Database rejected tournament creation: ", result.error);
            }

        } catch (error) {
            console.error("[tournament.js] Network failure initializing tournament: ", error);
        }

    }

    async runTournament(){

        let mI = this.currentMatchIndex;
        let tL = this.matchQueue.length; // tourney length

        console.log("[tournament.js] Tournament{}: Tournament in progress: Now simulating match " + mI + " of " + tL + ".");
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

            this.recordResult(
                glyphA, 
                glyphB,
                myMatch.matchScore.p1,
                myMatch.matchScore.p2
            );

            // Update Visuals
            tourneyVisualizer.updateEdge(
                document.getElementById('name1').innerText, 
                document.getElementById('name2').innerText
            );
            tourneyVisualizer.render();
            tourneyLeaderboard.render(this.standings);

            resetUI();
            this.currentMatchIndex += 1;
            this.runTournament(); 
    
        } else {

            if (this.mode === 'knock-out' && this.survivors.length > 1) {
                console.log(`[tournament.js] Round complete. ${this.survivors.length} glyphs survive. Generating next round...`);
                
                // Generate next bracket step with remaining survivors
                this.generateKnockOut(); 
                
                // Re-kick off loop for the next round
                this.runTournament();
            } else {
                console.log("[tournament.js] Tournament completed.");
                if(this.mode === 'knock-out' && this.survivors.length === 1) {
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

        // these are used for round-robin
        this.nodes = [];
        this.edgeStatus = {}; // Key: "id1_id2", Value: count (0, 1, 2)
        
        // Knock-out bracket architecture
        this.maxRounds = Math.ceil(Math.log2(contestants.length));
        this.bracketStructure = []; // Dynamic matrix matching rounds and match slots
        
        if (this.mode === 'round-robin'){ this.initPolygonNodes(); }
        if (this.mode === 'knock-out'){ this.initBracket(); }
    }

    initPolygonNodes() {
        const cx = this.canvas.width / 2;
        const cy = this.canvas.height / 2;
        const radius = Math.min(cx, cy) - 30;

        this.contestants.forEach((c, i) => {
            const angle = (i / this.contestants.length) * 2 * Math.PI - Math.PI / 2;
            this.nodes.push({
                x: cx + radius * Math.cos(angle),
                y: cy + radius * Math.sin(angle),
                glyph: c
            });
        });
    }

    initBracket() {
        const w = this.canvas.width;
        const h = this.canvas.height;
        const paddingX = 60;
        const midX = w / 2;

        // Allocate spacing between rounds up to the center match
        const roundSpacing = (midX - paddingX) / this.maxRounds;

        // Initialize empty tracking arrays for every round
        for (let r = 0; r <= this.maxRounds; r++) {
            this.bracketStructure[r] = [];
        }

        // Round 0: Seed all initial contestants
        const totalMatchesR0 = Math.ceil(this.contestants.length / 2);
        
        this.contestants.forEach((glyph, index) => {
            // Split contestants: Even indices on Left, Odd indices on Right
            const isLeft = index % 2 === 0;
            const wingIndex = Math.floor(index / 2);
            const wingCount = isLeft ? Math.ceil(this.contestants.length / 2) : Math.floor(this.contestants.length / 2);
            
            // Distribute down the Y axis evenly based on wing count
            const y = 40 + (wingIndex * (h - 80) / (wingCount - 1 || 1));
            const x = isLeft ? paddingX : w - paddingX;

            // Determine the structural match slot it feeds into
            const matchSlot = Math.floor(wingIndex / 2) + (isLeft ? 0 : totalMatchesR0 / 2);

            this.bracketStructure[0].push({
                x, y, glyph, isLeft, matchSlot
            });
        });

        // Pre-calculate structural nodes for future rounds (Rounds 1 to Finals)
        for (let r = 1; r <= this.maxRounds; r++) {
            const prevNodes = this.bracketStructure[r - 1];
            const maxSlotsInRound = Math.ceil(this.contestants.length / Math.pow(2, r + 1));
            
            // Temporary collection to group nodes by their upcoming match dependencies
            let leftSlots = {};
            let rightSlots = {};

            prevNodes.forEach(node => {
                const targetDict = node.isLeft ? leftSlots : rightSlots;
                if (!targetDict[node.matchSlot]) targetDict[node.matchSlot] = [];
                targetDict[node.matchSlot].push(node);
            });

            // Process Left side round nodes
            Object.keys(leftSlots).forEach((slot) => {
                const parents = leftSlots[slot];
                const x = paddingX + (r * roundSpacing);
                // Position node at the perfect geometric midpoint of its bracket parents
                const y = parents.reduce((sum, n) => sum + n.y, 0) / parents.length;
                
                this.bracketStructure[r].push({
                    x, y, glyph: null, isLeft: true, matchSlot: Math.floor(slot / 2)
                });
            });

            // Process Right side round nodes
            Object.keys(rightSlots).forEach((slot) => {
                const parents = rightSlots[slot];
                const x = w - paddingX - (r * roundSpacing);
                const y = parents.reduce((sum, n) => sum + n.y, 0) / parents.length;
                
                this.bracketStructure[r].push({
                    x, y, glyph: null, isLeft: false, matchSlot: Math.floor(slot / 2)
                });
            });
        }
    }

    // Keeps structural nodes up-to-date as the Tournament progresses
    recordBracketResult(matchSlotIndex, winnerGlyph) {
        if (this.mode !== 'knock-out') return;

        // Find what current round is executing by finding the lowest incomplete round layer
        let currentRound = 0;
        for (let r = 0; r < this.maxRounds; r++) {
            const spotsFilled = this.bracketStructure[r].filter(n => n.glyph !== null).length;
            const spotsExpected = Math.ceil(this.contestants.length / Math.pow(2, r));
            if (spotsFilled >= spotsExpected) {
                currentRound = r + 1;
            } else {
                break;
            }
        }

        // Advance winner glyph into its pre-calculated slot in the next round layer
        const nextRoundNodes = this.bracketStructure[currentRound + 1];
        if (nextRoundNodes) {
            // Find the node where this match slot converges
            const targetedNode = nextRoundNodes.find(n => n.matchSlot === Math.floor(matchSlotIndex / 2));
            if (targetedNode) targetedNode.glyph = winnerGlyph;
        }
    }

    updateEdge(idA, idB) {
        if (this.mode === 'round-robin'){
            const key = [idA, idB].sort().join('_'); // Sort IDs so the key is consistent regardless of who is P1 or P2
            this.edgeStatus[key] = (this.edgeStatus[key] || 0) + 1;
        }
    }

    render(activeP1Name, activeP2Name) {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

        if (this.mode === 'round-robin') {
            this.renderRoundRobin(activeP1Name, activeP2Name);
        } else {
            this.renderBracket(activeP1Name, activeP2Name);
        }
    }

    renderRoundRobin(activeP1Name, activeP2Name) {
        const ctx = this.ctx;
        ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

        // 1. Draw Edges (Matches)
        for (let i = 0; i < this.nodes.length; i++) {
            for (let j = i + 1; j < this.nodes.length; j++) {
                const n1 = this.nodes[i];
                const n2 = this.nodes[j];
                const key = [n1.glyph.name, n2.glyph.name].sort().join('_');
                const status = this.edgeStatus[key] || 0;

                // Determine Color
                let color = 'rgba(100, 100, 100, 0.3)'; // Grey (Pending)
                let lineWidth = 2;

                if ((n1.glyph.name === activeP1Name && n2.glyph.name === activeP2Name) ||
                    (n1.glyph.name === activeP2Name && n2.glyph.name === activeP1Name)) {
                    color = '#FFFFFF'; // White (Active)
                    lineWidth = 3;
                } else if (status === 1) {
                    color = '#2d5a27'; // Medium Green (1 Match played)
                } else if (status === 2) {
                    color = '#42f485'; // Light Green (Full Round Robin pair done)
                }

                ctx.beginPath();
                ctx.moveTo(n1.x, n1.y);
                ctx.strokeStyle = color;
                ctx.lineWidth = lineWidth;
                ctx.lineTo(n2.x, n2.y);
                ctx.stroke();
            }
        }

        // 2. Draw nodes representing the Glyphs
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

        // Step 1: Draw geometric connections between round iterations
        for (let r = 0; r < this.maxRounds; r++) {
            const currentNodes = this.bracketStructure[r];
            const nextNodes = this.bracketStructure[r + 1];

            currentNodes.forEach(node => {
                if (!nextNodes) return;
                
                // Track down the next inner node this entity builds towards
                const targetNode = nextNodes.find(n => n.isLeft === node.isLeft && n.matchSlot === Math.floor(node.matchSlot / 2));
                
                if (targetNode) {
                    let color = 'rgba(100, 100, 100, 0.2)';
                    let lineWidth = 1.5;

                    const isNodeActive = node.glyph && (node.glyph.name === activeP1Name || node.glyph.name === activeP2Name);
                    const isTargetActive = targetNode.glyph && (targetNode.glyph.name === activeP1Name || targetNode.glyph.name === activeP2Name);

                    if (isNodeActive && isTargetActive) {
                        color = '#FFFFFF'; // Bright highlight for active match lines
                        lineWidth = 3;
                    } else if (node.glyph && targetNode.glyph && node.glyph.name === targetNode.glyph.name) {
                        color = node.glyph.intrinsicColor || '#42f485'; // Match path highlighted by winner's tint
                        lineWidth = 2;
                    }

                    ctx.beginPath();
                    ctx.strokeStyle = color;
                    ctx.lineWidth = lineWidth;
                    ctx.moveTo(node.x, node.y);
                    // Draw clean angular structural bracket steps
                    ctx.lineTo(targetNode.x, node.y);
                    ctx.lineTo(targetNode.x, targetNode.y);
                    ctx.stroke();
                }
            });
        }

        // Step 2: Draw the node points over the brackets
        this.bracketStructure.forEach((round, rIndex) => {
            round.forEach(node => {
                const glyphExists = node.glyph !== null;
                const isActive = glyphExists && (node.glyph.name === activeP1Name || node.glyph.name === activeP2Name);

                ctx.beginPath();
                ctx.arc(node.x, node.y, rIndex === this.maxRounds ? 12 : 6, 0, Math.PI * 2);
                
                // Color configuration matching your engine aesthetics
                ctx.fillStyle = glyphExists ? (node.glyph.intrinsicColor || "#42f485") : "#222222";
                ctx.strokeStyle = isActive ? "#FFFFFF" : "rgba(100, 100, 100, 0.5)";
                ctx.lineWidth = isActive ? 2 : 1;
                
                ctx.shadowBlur = isActive ? 15 : 0;
                ctx.shadowColor = glyphExists ? (node.glyph.intrinsicColor || "#FFF") : "#FFF";
                ctx.fill();
                ctx.stroke();
                ctx.shadowBlur = 0; // Clear blur layer immediately

                // Render Labels for the outer entry positions and the grand center champ node
                if (rIndex === 0 || rIndex === this.maxRounds) {
                    if (glyphExists) {
                        ctx.fillStyle = "#FFFFFF";
                        ctx.font = "bold 9px 'Courier New'";
                        ctx.textAlign = node.isLeft ? "right" : "left";
                        ctx.fillText(
                            node.glyph.name.substring(0, 10), 
                            node.x + (node.isLeft ? -12 : 12), 
                            node.y + 3
                        );
                    }
                }
            });
        });
    }

}

class TournamentLeaderboard {
    constructor(canvasId, contestants) {
        this.canvas = document.getElementById(canvasId);
        this.ctx = this.canvas.getContext('2d');
        this.contestants = contestants;
        
        this.totalMatchesPerGlyph = (contestants.length - 1) * 2; // Match settings (to calculate completion %)
    }

    render(standings) {
        const ctx = this.ctx;
        const w = this.canvas.width;
        const h = this.canvas.height;
        ctx.clearRect(0, 0, w, h);

        // Header
        ctx.font = "11px 'Courier New'";
        ctx.fillStyle = "rgba(209, 209, 209, 0.5)";
        ctx.fillText("GLYPH", 10, 20);
        ctx.fillText("P", 120, 20);  // Played
        ctx.fillText("W", 145, 20);  // Wins
        ctx.fillText("PTS", 175, 20); // Total Score

        // Sort contestants by points for a live-ranking effect
        const sorted = Object.keys(standings).sort((a, b) => {
            return standings[b].points - standings[a].points;
        });

        const rowHeight = 25;
        const topPadding = 40;

        sorted.forEach((name, i) => {
            const data = standings[name];
            const y = topPadding + (i * rowHeight);
            const glyphObj = this.contestants.find(c => c.name === name);

            // 1. Completion Bar (Background)
            ctx.fillStyle = "rgba(255,255,255,0.05)";
            ctx.fillRect(10, y + 5, w - 20, 15);

            // 2. Completion Bar (Progress)
            const progress = (data.wins + data.losses) / this.totalMatchesPerGlyph;
            ctx.fillStyle = glyphObj.intrinsicColor || "#42f485";
            ctx.globalAlpha = 0.3;
            ctx.fillRect(10, y + 5, (w - 20) * progress, 15);
            ctx.globalAlpha = 1.0;

            // 3. Text Data
            ctx.fillStyle = "#FFF";
            ctx.font = "bold 11px 'Courier New'";
            ctx.fillText(name.substring(0, 12), 15, y + 16);
            
            ctx.font = "10px 'Courier New'";
            ctx.fillStyle = "var(--frame-grey)";
            ctx.fillText(data.wins + data.losses, 120, y + 16);
            ctx.fillText(data.wins, 145, y + 16);
            ctx.fillText(Math.floor(data.points).toLocaleString(), 175, y + 16);

            // 4. Highlight if currently playing (optional - requires passing active state)
        });
    }
}