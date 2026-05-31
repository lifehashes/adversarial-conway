class AnalyticsEngine {
    constructor(canvasId, maxIterations) {
        this.canvas = document.getElementById(canvasId);
        this.ctx = this.canvas.getContext('2d');
        this.maxIterations = maxIterations;
        this.history = []; 
        this.prevScore = { p1: 0, p2: 0 };
        // console.log("[analytics.js] class Analytics Engine, constructor(" + canvasId + ", " + maxIterations + "): Done.");
    }

    record(p1, p2) {
        const d1 = p1 - this.prevScore.p1;
        const d2 = p2 - this.prevScore.p2;
        this.history.push({ p1, p2, d1, d2 });
        this.prevScore = { p1, p2 };
    }

    render(color1, color2) {
        const w = this.canvas.width;
        const h = this.canvas.height;
        const ctx = this.ctx;
        ctx.clearRect(0, 0, w, h);

        if (this.history.length < 2) return;

        // --- DYNAMIC SCALING CALCULATION ---
        // Find the absolute maximums currently in the buffer
        let maxScoreSeen = 10; // Avoid division by zero
        let maxDeltaSeen = 5;

        this.history.forEach(d => {
            maxScoreSeen = Math.max(maxScoreSeen, d.p1, d.p2);
            maxDeltaSeen = Math.max(maxDeltaSeen, Math.abs(d.d1), Math.abs(d.d2));
        });

        const stepX = w / this.maxIterations;
        const baselineY = h - 5;
        
        // We use 90% of height for scores and 40% for deltas to keep them distinct
        const scoreScale = (h * 0.9) / maxScoreSeen;
        const deltaScale = (h * 0.4) / maxDeltaSeen;

        [1, 2].forEach(p => {
            const color = (p === 1) ? color1 : color2;
            
            // 1. Draw Delta (Thin Ghost Line) - Auto-scaled
            ctx.strokeStyle = color;
            ctx.lineWidth = 1;
            ctx.globalAlpha = 0.3;
            ctx.beginPath();
            this.history.forEach((d, i) => {
                const val = (p === 1) ? d.d1 : d.d2;
                const x = i * stepX;
                const y = baselineY - (val * deltaScale);
                if (i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
            });
            ctx.stroke();

            // 2. Draw Total Score (Thick Primary Line) - Auto-scaled
            ctx.globalAlpha = 1.0;
            ctx.lineWidth = 3;
            ctx.shadowBlur = 4;
            ctx.shadowColor = color;
            ctx.beginPath();
            this.history.forEach((d, i) => {
                const val = (p === 1) ? d.p1 : d.p2;
                const x = i * stepX;
                const y = baselineY - (val * scoreScale);
                if (i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, i === 0 ? y : y);
                // The above ensures we start at baseline if it's the first point
                if (i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
            });
            ctx.stroke();
            ctx.shadowBlur = 0;
        });
    }

    clear() {
        this.history = [];
        this.prevScore = { p1: 0, p2: 0 };
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
    }

}

class CellProfileGraph {
    constructor(canvasId, maxIterations) {
        this.canvas = document.getElementById(canvasId);
        this.ctx = this.canvas.getContext('2d');
        this.maxIterations = maxIterations;
        this.history = [];
    }

    record(count) {
        this.history.push(count);
    }

    render(color, globalMax = null) {
        const w = this.canvas.width;
        const h = this.canvas.height;
        const ctx = this.ctx;
        ctx.clearRect(0, 0, w, h);

        if (this.history.length < 2) return;

        // --- ROBUST DYNAMIC SCALING ---
        // Use a slice to ignore the initial placement spike, 
        // but default to a sane value if the history is too short.
        let displayMax = globalMax; 
        if (!displayMax) {
            const relevantHistory = this.history.slice(5); 
            displayMax = relevantHistory.length > 0 ? Math.max(...relevantHistory) : Math.max(...this.history);
        } 
        
        // Final safety check: if displayMax is still 0 or NaN, default to 10
        if (!displayMax || displayMax < 1) displayMax = 10;

        const stepX = w / this.maxIterations; 
        const scaleY = (h - 4) / displayMax; // Use 4px padding for "headroom"

        ctx.strokeStyle = color;
        ctx.lineWidth = 1.5;
        ctx.beginPath();
        
        this.history.forEach((val, i) => {
            const x = i * stepX;
            // Calculate y and ensure it stays within canvas bounds
            let y = h - (val * scaleY);
            y = Math.min(Math.max(y, 2), h - 2); 
            
            if (i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
        });
        ctx.stroke();

        // Subtle Fill
        ctx.lineTo(this.history.length * stepX, h);
        ctx.lineTo(0, h);
        ctx.fillStyle = color;
        ctx.globalAlpha = 0.1;
        ctx.fill();
        ctx.globalAlpha = 1.0;
    }

    clear() {
        this.history = [];
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
    }

}

class RoundHistoryGraph {
    constructor(canvasId, totalRounds) {
        this.canvas = document.getElementById(canvasId);
        this.ctx = this.canvas.getContext('2d');
        this.totalRounds = totalRounds;
        this.history = []; // Array of scores for this player
        this.opponentHistory = []; // Needed to determine if we won the round
        // console.log("[analytics.js] class RoundHistoryGraph constructor(" + canvasId + ", " + totalRounds + "): Done.");
    }

    record(myScore, opponentScore) {
        this.history.push(myScore);
        this.opponentHistory.push(opponentScore);
    }

    render(color, currentLiveScore) {
        // console.log("[analytics.js] class RoundHistoryGraph render(" + color + ", " + currentLiveScore + "): Rendering...");

        const w = this.canvas.width;
        const h = this.canvas.height;
        const ctx = this.ctx;
        ctx.clearRect(0, 0, w, h);

        if (this.history.length === 0 && !currentLiveScore) return;

        const stepX = w / this.totalRounds;
        
        // Find max score across both players to normalize height safely
        const maxScore = Math.max(...this.history, ...this.opponentHistory, 10);
        
        const scaleY = (h - 5) / maxScore;

        // console.log("[analytics.js] class RoundHistoryGraph render(): maxScore = " + maxScore + ", scaleY = " + scaleY);

        this.history.forEach((score, i) => {
            const x = i * stepX;
            const barHeight = score * scaleY;
            const isWinner = score > this.opponentHistory[i];

            // Set style based on win/loss
            if (isWinner) {
                ctx.fillStyle = color;
                ctx.globalAlpha = 1.0;
                
                // Add an isolated "glow" highlight for the winner
                ctx.shadowBlur = 10;
                ctx.shadowColor = color;
            } else {
                ctx.fillStyle = "#666"; // Dimmed grey for round loss
                ctx.globalAlpha = 0.4;
                
                // 💡 FIX 02: Explicitly strip both blur context trackers 
                // to prevent glowing halos from drawing on loss bars.
                ctx.shadowBlur = 0;
                ctx.shadowColor = "transparent";
            }

            // Draw the round "pulse" 
            // Math.max guarantees a 2px flatline bar is always visible even if the score is absolute 0
            const finalBarH = Math.max(barHeight, 2);
            ctx.fillRect(x + 2, h - finalBarH, stepX - 4, finalBarH);
        });

        // Draw a faint, pulsing bar for the round currently in progress
        if (this.history.length < this.totalRounds) {
            const x = this.history.length * stepX;
            const liveBarHeight = currentLiveScore * scaleY;
            
            // Clean state isolation reset for ghost/live tracking nodes
            ctx.shadowBlur = 0; 
            ctx.shadowColor = "transparent";
            ctx.fillStyle = color;
            ctx.globalAlpha = 0.25; 
            
            const finalLiveH = Math.max(liveBarHeight, 2);
            ctx.fillRect(x + 2, h - finalLiveH, stepX - 4, finalLiveH);
        }

        // Final safe cleanup routine restoration
        ctx.globalAlpha = 1.0;
        ctx.shadowBlur = 0;
        ctx.shadowColor = "transparent";
    }

    clear() {
        this.history = [];
        this.opponentHistory = [];
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
    }
}

class ChargeHistogram {
    constructor(canvasId, bins = 10) {
        this.canvas = document.getElementById(canvasId);
        this.ctx = this.canvas.getContext('2d');
        this.bins = bins;
    }

    getDistribution(arena, playerId) {
        const distribution = new Array(this.bins).fill(0);
        let maxCount = 0;
        for (let row of arena.grid) {
            for (let cell of row) {
                if (cell.owner === playerId && Math.abs(cell.charge) > 0) {
                    const binIdx = Math.min(Math.floor(Math.abs(cell.charge) * this.bins), this.bins - 1);
                    distribution[binIdx]++;
                    if (distribution[binIdx] > maxCount) maxCount = distribution[binIdx];
                }
            }
        }
        return { distribution, maxCount };
    }

    render(arena, playerId, color, globalMax = null) {
        const w = this.canvas.width;
        const h = this.canvas.height;
        const ctx = this.ctx;
        ctx.clearRect(0, 0, w, h);

        const { distribution, maxCount } = this.getDistribution(arena, playerId);        
        const displayMax = Math.max(globalMax || maxCount, 20);

        const barWidth = w / this.bins;
        distribution.forEach((count, i) => {
            // Calculate height based on the stable displayMax
            const barHeight = (count / displayMax) * (h * 0.9); 
            const x = i * barWidth;
            
            ctx.fillStyle = color;
            // Make the bars more visible by increasing the base alpha
            ctx.globalAlpha = 0.4 + (i / this.bins) * 0.6; 
            ctx.fillRect(x + 1, h - barHeight, barWidth - 2, barHeight);
        });
        ctx.globalAlpha = 1.0;
    }

    clear() {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
    }

}