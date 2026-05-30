class TournamentManager {
    constructor(contestants, mode = 'round-robin') {
        this.contestants = contestants; // Array of Glyph objects from your PHP fetch
        this.mode = mode;
        this.matchQueue = [];
        this.results = [];
        this.currentMatchIndex = 0;
        this.standings = {}; // Track wins/losses/points

        this.init();
    }

    init() {
        if (this.mode === 'round-robin') {
            this.generateRoundRobin();
        } else {
            this.generateBracket();
        }
        // Initialize standings
        this.contestants.forEach(c => {
            this.standings[c.BATTLE_NAME] = { wins: 0, losses: 0, points: 0 };
        });
    }

    generateRoundRobin() {
        for (let i = 0; i < this.contestants.length; i++) {
            for (let j = 0; j < this.contestants.length; j++) {
                if (i !== j) {
                    // Play everyone twice (Home/Away style)
                    this.matchQueue.push({ p1: this.contestants[i], p2: this.contestants[j] });
                }
            }
        }
    }

    generateBracket() {
        // Simple Single Elimination: Shuffle and pair
        let shuffled = [...this.contestants].sort(() => 0.5 - Math.random());
        for (let i = 0; i < shuffled.length; i += 2) {
            if (shuffled[i+1]) {
                this.matchQueue.push({ p1: shuffled[i], p2: shuffled[i+1] });
            } else {
                // Bye round logic if odd number
                this.results.push({ winner: shuffled[i], loser: null, status: 'bye' });
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

        // Aggregate point tracking
        p1.points += p1Score;
        p2.points += p2Score;
        
        p1.played++;
        p2.played++;

        this.currentMatchIndex++;
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
        const radius = Math.min(cx, cy) - 30; // Padding for glyph icons

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
        // Sort IDs so the key is consistent regardless of who is P1 or P2
        const key = [idA, idB].sort().join('_');
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
                const key = [n1.glyph.BATTLE_NAME, n2.glyph.BATTLE_NAME].sort().join('_');
                const status = this.edgeStatus[key] || 0;

                // Determine Color
                let color = 'rgba(100, 100, 100, 0.3)'; // Grey (Pending)
                let lineWidth = 2;

                if ((n1.glyph.BATTLE_NAME === activeP1Name && n2.glyph.BATTLE_NAME === activeP2Name) ||
                    (n1.glyph.BATTLE_NAME === activeP2Name && n2.glyph.BATTLE_NAME === activeP1Name)) {
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

        // 2. Draw Nodes (The Glyphs)
        this.nodes.forEach(node => {
            const isActive = node.glyph.BATTLE_NAME === activeP1Name || node.glyph.BATTLE_NAME === activeP2Name;
            
            // Draw a small circle with the Glyph's intrinsic color
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
        
        // Match settings (to calculate completion %)
        this.totalMatchesPerGlyph = (contestants.length - 1) * 2;
    }

    render(standings) {
        const ctx = this.ctx;
        const w = this.canvas.width;
        const h = this.canvas.height;
        ctx.clearRect(0, 0, w, h);

        // Header
        ctx.font = "10px 'Courier New'";
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
            const glyphObj = this.contestants.find(c => c.BATTLE_NAME === name);

            // 1. Completion Bar (Background)
            ctx.fillStyle = "rgba(255,255,255,0.05)";
            ctx.fillRect(10, y + 5, w - 20, 15);

            // 2. Completion Bar (Progress - using intrinsic color)
            const progress = (data.wins + data.losses) / this.totalMatchesPerGlyph;
            ctx.fillStyle = glyphObj.color || "#42f485";
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