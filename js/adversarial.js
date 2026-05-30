class ArenaEngine {
    constructor(canvasId, width, height, gridSize) {
        this.canvas = document.getElementById(canvasId);
        this.ctx = this.canvas.getContext('2d');
        
        // 2. Set the VISUAL size of the canvas
        this.canvas.width = width;
        this.canvas.height = height;

        // 3. Set the LOGICAL grid dimensions
        this.rows = gridSize;
        this.cols = Math.floor(gridSize * (width / height));
        
        // Use a 2D array of objects to store Charge and Owner
        // charge: 0.0 to 1.0 (magnitude)
        // owner: 0 (none), 1 (P1), 2 (P2)
        this.grid = Array.from({ length: this.rows }, () => 
            Array.from({ length: this.cols }, () => ({ 
                charge: 0, 
                owner: 0, 
                justFlipped: 0 // Track frames remaining for the highlight effect
            }))
        );
        
        // Kinematics for the "Roaming" Glyphs
        this.p1State = { x: 0, y: 0, vx: 0, vy: 0 };
        this.p2State = { x: 0, y: 0, vx: 0, vy: 0 };
        
        this.iteration = 0;
        this.seed = 0;
        this.mode = 'combative';
    }

    // A fast, seedable 32-bit PRNG
    seededRandom() {
        this.seed |= 0; 
        this.seed = this.seed + 0x9e3779b9 | 0;
        let t = this.seed ^ this.seed >>> 16; 
        t = Math.imul(t, 0x21f0aaad);
        t = t ^ t >>> 15; 
        t = Math.imul(t, 0x735a2d97);
        return ((t = t ^ t >>> 15) >>> 0) / 4294967296;
    }

    setSeed(val) {
        this.seed = val;

        // 1. Randomize Vertical Starting Positions (Keep Horizontal at Margins)
        // We leave a small margin (5 units) from the top/bottom edges
        const verticalMargin = 5;
        const spawnXMargin = 5;
        
        this.p1State.x = spawnXMargin;
        this.p1State.y = verticalMargin + (this.seededRandom() * (this.rows - (verticalMargin * 2)));

        this.p2State.x = this.cols - spawnXMargin - 20; // -20 to account for glyph width
        this.p2State.y = verticalMargin + (this.seededRandom() * (this.rows - (verticalMargin * 2)));

        // 2. Randomize Velocities with a "Steer Toward Center" bias
        const baseSpeed = 0.4;
        const variance = 0.3;

        // Player 1: Moving Right (positive vx)
        this.p1State.vx = baseSpeed + (this.seededRandom() * variance);
        // Player 2: Moving Left (negative vx)
        this.p2State.vx = -(baseSpeed + (this.seededRandom() * variance));

        // Vertical Velocity: Calculate vector toward each other's Y position
        const yDiff = this.p2State.y - this.p1State.y;
        const steerStrength = 0.01; // Subtle nudge so they don't just fly off-screen immediately

        this.p1State.vy = (yDiff * steerStrength) + (this.seededRandom() - 0.5) * variance;
        this.p2State.vy = (-yDiff * steerStrength) + (this.seededRandom() - 0.5) * variance;
    }

    applyJitter() {
        const intensity = 0.26; 
        
        // Use seededRandom() instead of Math.random()
        if (this.seededRandom() < 0.05) {
            this.p1State.vx += (this.seededRandom() - 0.5) * intensity;
            this.p1State.vy += (this.seededRandom() - 0.5) * intensity;
        }
        if (this.seededRandom() < 0.05) {
            this.p2State.vx += (this.seededRandom() - 0.5) * intensity;
            this.p2State.vy += (this.seededRandom() - 0.5) * intensity;
        }

        // Clamp logic remains the same
        const maxV = 0.8;
        this.p1State.vx = Math.max(-maxV, Math.min(maxV, this.p1State.vx));
        this.p1State.vy = Math.max(-maxV, Math.min(maxV, this.p1State.vy));
        this.p2State.vx = Math.max(-maxV, Math.min(maxV, this.p2State.vx));
        this.p2State.vy = Math.max(-maxV, Math.min(maxV, this.p2State.vy));
    }

    // This is called every frame to "Stamp" ("project") the current GOL state onto the arena
    stamp(glyphEngine, playerNum) {
        if (!glyphEngine.isActive) return;
        const state = (playerNum === 1) ? this.p1State : this.p2State;
        const glyphGrid = glyphEngine.grid;
        const n = glyphEngine.n;

        // Roam: Update position
        state.x = (state.x + state.vx + this.cols) % this.cols;
        state.y = (state.y + state.vy + this.rows) % this.rows;

        for (let gy = 0; gy < n; gy++) {
            for (let gx = 0; gx < n; gx++) {
                if (glyphGrid[gy][gx] === 1) {
                    const ax = ((Math.floor(state.x + gx) % this.cols) + this.cols) % this.cols;
                    const ay = ((Math.floor(state.y + gy) % this.rows) + this.rows) % this.rows;
                    const cell = this.grid[ay][ax];

                    const chargePower = 0.1;

                    if (this.mode === 'combative') {
                        // COMBATIVE LOGIC: 

                        const previousOwner = cell.owner;

                        // Player 1 adds to the charge, Player 2 subtracts from it.
                        if (playerNum === 1) {
                            cell.charge += chargePower;
                        } else {
                            cell.charge -= chargePower;
                        }

                        // Clamp the total charge between -1 and 1
                        cell.charge = Math.max(-1.0, Math.min(1.0, cell.charge));

                        // Update ownership based on the sign of the charge
                        if (cell.charge > 0) {
                            cell.owner = 1;
                        } else if (cell.charge < 0) {
                            cell.owner = 2;
                        } else {
                            cell.owner = 0; // Perfectly neutral
                        }

                        if (previousOwner !== 0 && previousOwner !== cell.owner) {
                            cell.justFlipped = 8; // Highlighting for 8 frames (~0.5 seconds)
                        }

                    } else {
                        // OLD COMPETITIVE LOGIC:
                        if (cell.owner === 0 || cell.owner === playerNum) {
                            cell.owner = playerNum;
                            cell.charge = Math.min(1.0, cell.charge + chargePower);
                        }
                    }
                }
            }
        }

    }

    calculateScore() {
        let scoreP1 = 0;
        let scoreP2 = 0;

        for (let y = 0; y < this.rows; y++) {
            for (let x = 0; x < this.cols; x++) {
                const cell = this.grid[y][x];
                // Rule: Charge must be >= 0.5 to count as a point
                if (Math.abs(cell.charge) >= 0.5) {
                    if (cell.owner === 1) scoreP1++;
                    else if (cell.owner === 2) scoreP2++;
                }
            }
        }

        return { p1: scoreP1, p2: scoreP2 };
    }

    // arena cells lose their charge over time (currently not in use)
    applyDecay() {
        for (let y = 0; y < this.rows; y++) {
            for (let x = 0; x < this.cols; x++) {
                const cell = this.grid[y][x];
                if (cell.charge > 0) {
                    cell.charge -= 0.005; // Very slow fade
                    if (cell.charge <= 0) {
                        cell.charge = 0;
                        cell.owner = 0;
                    }
                }
            }
        }
    }

    render(color1, color2) {
        const cellSize = this.canvas.width / this.cols;
        const radius = (cellSize / 2) * 0.9; // 90% of half-cell width for a crisp look
        
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

        for (let y = 0; y < this.rows; y++) {
            for (let x = 0; x < this.cols; x++) {
                const cell = this.grid[y][x];
                
                if (cell.owner !== 0) {
                    const baseColor = (cell.owner === 1) ? color1 : color2;
                    
                    // Map charge to Opacity/Saturation
                    this.ctx.globalAlpha = Math.abs(cell.charge); 
                    this.ctx.fillStyle = baseColor;
                    
                    // Add glow for high-charge cells
                    if (cell.charge > 0.7) {
                        this.ctx.shadowBlur = 8;
                        this.ctx.shadowColor = baseColor;
                    } else {
                        this.ctx.shadowBlur = 0;
                    }

                    // Draw the Circle
                    this.ctx.beginPath();
                    const centerX = x * cellSize + (cellSize / 2);
                    const centerY = y * cellSize + (cellSize / 2);
                    this.ctx.arc(centerX, centerY, radius, 0, 2 * Math.PI);
                    this.ctx.fill();

                    // DRAW HIGHLIGHT
                    if (cell.justFlipped > 0) {
                        this.ctx.globalAlpha = cell.justFlipped / 8; // Fade out highlight
                        this.ctx.strokeStyle = "#FFFFFF"; // High contrast white
                        this.ctx.lineWidth = 2;
                        this.ctx.stroke();
                        
                        cell.justFlipped--; // Decay the highlight timer
                    }

                }
            }
        }
        // Reset global state for the next frame
        this.ctx.globalAlpha = 1.0;
        this.ctx.shadowBlur = 0;
    }

    reset() {
        // Clear the grid state back to neutral
        for (let y = 0; y < this.rows; y++) {
            for (let x = 0; x < this.cols; x++) {
                this.grid[y][x] = { charge: 0, owner: 0, justFlipped: 0 };
            }
        }
        // The positions and velocities will be overwritten by setSeed() in the runDuel call
        this.iteration = 0;
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
    }

}