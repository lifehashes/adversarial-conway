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

        console.log("[tournament.js] Tournament{}.generateKnockOut(): survivors = " + this.survivors.map(c => c ? c.name : "null").join(", "));

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
                const historicalMatch = this.results.find(r => r.winner.name === player.name && r.status === 'eliminated');
                
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
                tourneyLeaderboard = new TournamentLeaderboard("tournamentLeaderboard", this.contestants);

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
                console.log(`[tournament.js] Round complete. ${this.survivors.length} glyphs survive. Generating next round...`);
                
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
        const w = this.canvas.width;
        const h = this.canvas.height;
        const paddingX = 75;
        const midX = w / 2;
        const midY = h / 2;
        const roundSpacing = (midX - paddingX) / this.maxRounds;

        for (let r = 0; r <= this.maxRounds; r++) {
            this.bracketStructure[r] = [];
        }

        // Separate contestants into pure visual arrays
        let leftWingContestants = this.contestants.slice(0, this.contestants.length / 2);
        let rightWingContestants = this.contestants.slice(this.contestants.length / 2);

        // Plot Round 0 Left Column nodes sequentially down Y
        leftWingContestants.forEach((glyph, i) => {
            const y = 40 + (i * (h - 80) / (leftWingContestants.length - 1 || 1));
            this.bracketStructure[0].push({ 
                x: paddingX, y, glyph, isLeft: true, matchSlot: i 
            });
        });

        // Plot Round 0 Right Column nodes sequentially down Y
        rightWingContestants.forEach((glyph, i) => {
            const y = 40 + (i * (h - 80) / (rightWingContestants.length - 1 || 1));
            this.bracketStructure[0].push({ 
                x: w - paddingX, y, glyph, isLeft: false, matchSlot: i 
            });
        });

        // Pre-calculate convergence positions for deeper rounds up to the Semi-Finals
        // FIX: Change condition to strictly LESS THAN (r < this.maxRounds)
        for (let r = 1; r < this.maxRounds; r++) {
            const nodesInPrevRound = this.bracketStructure[r - 1];
            
            // 1. Group Left wing parents by their FUTURE slot
            let leftSlots = {};
            nodesInPrevRound.filter(n => n.isLeft).forEach(n => {
                const nextSlot = Math.floor(n.matchSlot / 2);
                if (!leftSlots[nextSlot]) leftSlots[nextSlot] = [];
                leftSlots[nextSlot].push(n);
            });

            Object.keys(leftSlots).forEach((slotStr) => {
                const slot = parseInt(slotStr, 10);
                const parents = leftSlots[slot];
                const x = paddingX + (r * roundSpacing);
                const y = parents.reduce((sum, n) => sum + n.y, 0) / parents.length;
                
                this.bracketStructure[r].push({ 
                    x, y, glyph: null, isLeft: true, matchSlot: slot 
                });
            });

            // 2. Group Right wing parents by their FUTURE slot
            let rightSlots = {};
            nodesInPrevRound.filter(n => !n.isLeft).forEach(n => {
                const nextSlot = Math.floor(n.matchSlot / 2);
                if (!rightSlots[nextSlot]) rightSlots[nextSlot] = [];
                rightSlots[nextSlot].push(n);
            });

            Object.keys(rightSlots).forEach((slotStr) => {
                const slot = parseInt(slotStr, 10);
                const parents = rightSlots[slot];
                const x = w - paddingX - (r * roundSpacing);
                const y = parents.reduce((sum, n) => sum + n.y, 0) / parents.length;
                
                this.bracketStructure[r].push({ 
                    x, y, glyph: null, isLeft: false, matchSlot: slot 
                });
            });
        }

        // --- NEW: Plot the single Grand Champion terminal node in the middle ---
        this.bracketStructure[this.maxRounds].push({
            x: midX,
            y: midY,
            glyph: null,
            isLeft: true, // Arbitrary flag since it's perfectly centered
            matchSlot: 0
        });
    }

    advanceRoundTier() {
        this.currentVisualRound++;
    }

    recordBracketResult(wing, slotIndex, winnerGlyph) {
        if (this.mode !== 'knock-out') return;
        
        // If we are recording the results of the final match tier, update the Grand Champion node
        if (this.currentVisualRound === this.maxRounds - 1) {
            this.setGrandChampion(winnerGlyph);
            return; 
        }

        // Find the node container on the next visual level step
        const nextRoundNodes = this.bracketStructure[this.currentVisualRound + 1];
        if (nextRoundNodes) {
            const lookForLeft = (wing === 'left');
            // Calculate the exact parent slot target using integer division
            const targetParentSlot = Math.floor(slotIndex / 2);
            
            // CRITICAL FIX: Ensure the wing matches perfectly so data doesn't leak to the opposing flight column
            const targetedNode = nextRoundNodes.find(n => n.isLeft === lookForLeft && n.matchSlot === targetParentSlot);
            
            if (targetedNode) {
                targetedNode.glyph = winnerGlyph;
            }
        }
    }

    setGrandChampion(winnerGlyph) {
        const finalRound = this.bracketStructure[this.maxRounds];
        if (finalRound && finalRound[0]) {
            finalRound[0].glyph = winnerGlyph;
        }
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

        // 1. Draw structural geometric connecting step lines
        for (let r = 0; r < this.maxRounds; r++) {
            const currentNodes = this.bracketStructure[r];
            const nextNodes = this.bracketStructure[r + 1];

            currentNodes.forEach(node => {
                if (!nextNodes) return;
                
                const targetNode = nextNodes.find(n => {
                    // For the grand final circle, ignore wing constraints since they both meet in the center slot
                    if (r === this.maxRounds - 1) return n.matchSlot === 0;
                    
                    // FIX: Round 0 is already paired, deeper rounds group via division
                    const targetSlot = (r === 0) ? node.matchSlot : Math.floor(node.matchSlot / 2);
                    return n.isLeft === node.isLeft && n.matchSlot === targetSlot;
                });
                
                if (targetNode) {
                    let color = 'rgba(100, 100, 100, 0.2)';
                    let lineWidth = 1.5;

                    // Only light up white if the active match belongs to the current visual round tier
                    const isNodeActive = (r === this.currentVisualRound) && node.glyph && (node.glyph.name === activeP1Name || node.glyph.name === activeP2Name);
                    const isTargetActive = targetNode.glyph && (targetNode.glyph.name === activeP1Name || targetNode.glyph.name === activeP2Name);

                    if (isNodeActive && isTargetActive) {
                        color = '#FFFFFF';
                        lineWidth = 3;
                    } else if (node.glyph && targetNode.glyph && node.glyph.name === targetNode.glyph.name) {
                        color = node.glyph.intrinsicColor || '#42f485';
                        lineWidth = 2;
                    }

                    ctx.beginPath();
                    ctx.strokeStyle = color;
                    ctx.lineWidth = lineWidth;
                    ctx.moveTo(node.x, node.y);
                    ctx.lineTo(targetNode.x, node.y);
                    ctx.lineTo(targetNode.x, targetNode.y);
                    ctx.stroke();
                }
            });
        }

        // 2. Draw nodes over top of lines
        this.bracketStructure.forEach((round, rIndex) => {
            round.forEach(node => {
                const glyphExists = node.glyph !== null;
                const isActive = glyphExists && (node.glyph.name === activeP1Name || node.glyph.name === activeP2Name);

                ctx.beginPath();
                ctx.arc(node.x, node.y, rIndex === this.maxRounds ? 14 : 6, 0, Math.PI * 2);
                
                ctx.fillStyle = glyphExists ? (node.glyph.intrinsicColor || "#42f485") : "#222222";
                ctx.strokeStyle = isActive ? "#FFFFFF" : "rgba(100, 100, 100, 0.5)";
                ctx.lineWidth = isActive ? 2 : 1;
                
                ctx.shadowBlur = isActive ? 15 : 0;
                ctx.shadowColor = glyphExists ? (node.glyph.intrinsicColor || "#FFF") : "#FFF";
                ctx.fill();
                ctx.stroke();
                ctx.shadowBlur = 0;

                if (rIndex === 0 || rIndex === this.maxRounds) {
                    if (glyphExists) {
                        ctx.fillStyle = "#FFFFFF";
                        ctx.font = "bold 10px 'Courier New'";
                        ctx.textAlign = node.isLeft ? "right" : "left";
                        
                        let offset = node.isLeft ? -14 : 14;
                        if (rIndex === this.maxRounds) {
                            ctx.textAlign = "center";
                            offset = 0;
                            ctx.fillText(node.glyph.name.substring(0, 12), node.x, node.y - 20);
                        } else {
                            ctx.fillText(node.glyph.name.substring(0, 12), node.x + offset, node.y + 4);
                        }
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
        this.totalMatchesPerGlyph = (contestants.length - 1) * 2;
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

            const progress = (data.wins + data.losses) / this.totalMatchesPerGlyph;
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