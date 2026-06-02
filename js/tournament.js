class Tournament {
    constructor(contestants, mode = 'round-robin') {
        this.contestants = contestants; 
        this.mode = mode;
        this.matchQueue = [];
        this.results = [];
        this.currentMatchIndex = 0;
        this.standings = {};
        this.dbTourneyId = null;

        this.init();
    }

    init() {
        if (this.mode === 'round-robin') {
            this.generateRoundRobin();
        } 
        if (this.mode === 'knock-out'){
            this.generateBracket();
        }
        // Initialize standings
        this.contestants.forEach(c => {
            this.standings[c.name] = { wins: 0, losses: 0, points: 0 };
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

    generateBracket() {
        let shuffled = [...this.contestants].sort(() => 0.5 - Math.random());
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

        if (p1Score > p2Score) { p1.wins++; p2.losses++; }
        else if (p2Score > p1Score) { p2.wins++; p1.losses++; }

        p1.points += p1Score;
        p2.points += p2Score;
        
        p1.played++;
        p2.played++;

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
                    mode: 'bracket' // or pull from a select dropdown if you add one
                })
            });

            const result = await response.json();

            if (result.success) {
                // 2. Capture the real, auto-incremented ID from your DB schema
                this.dbTourneyId = result.tournament_id;
                console.log("[tournament.js] Database tournament registered successfully. ID: " + this.dbTourneyId);
                
                // 3a. Initialize Visuals
                tourneyVisualizer = new TournamentVisualizer("tournamentPolygon", this.contestants);
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
            tourneyLeaderboard.render(TourneyHalleck.standings);

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
            tourneyLeaderboard.render(TourneyHalleck.standings);

            resetUI();
            this.currentMatchIndex += 1;
            this.runTournament(); 
    
        } else {

            console.log("[tournament.js] Tournament{}: Tournament completed.");

        }

    }

}

class TournamentVisualizer {
    constructor(canvasId, contestants) {
        this.canvas = document.getElementById(canvasId);
        this.ctx = this.canvas.getContext('2d');
        this.contestants = contestants;
        this.nodes = [];
        this.edgeStatus = {}; // Key: "id1_id2", Value: count (0, 1, 2)
        
        this.initNodes();
    }

    initNodes() {
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

    updateEdge(idA, idB) {
        const key = [idA, idB].sort().join('_'); // Sort IDs so the key is consistent regardless of who is P1 or P2
        this.edgeStatus[key] = (this.edgeStatus[key] || 0) + 1;
    }

    render(activeP1Name, activeP2Name) {
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