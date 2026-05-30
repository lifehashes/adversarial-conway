class LifeEngine {
    constructor(canvasId, gridSize) {
        this.canvas = document.getElementById(canvasId);
        this.ctx = this.canvas.getContext('2d');

        const size = this.canvas.offsetWidth; 
        this.canvas.width = size;
        this.canvas.height = size;

        this.n = gridSize;
        this.grid = this.createGrid();
        this.iteration = 0;

        this.originHash = "";
        this.currentHash = "";
        this.intrinsicColor = "#42f485"; // Default green until hash is loaded

        // Store all previous hashes to detect cycles
        this.history = new Set();
        this.isActive = true;

    }

    getBinaryString() {
        return this.grid.flat().join('');
    }

    // Initialize an empty 2D array
    createGrid() {
        return Array.from({ length: this.n }, () => Array(this.n).fill(0));
    }

    // Fill the grid from your 256-bit binary string (or any length n*n)
    loadFromBinary(binaryString) {
        for (let i = 0; i < binaryString.length; i++) {
            const x = i % this.n;
            const y = Math.floor(i / this.n);
            if (y < this.n) {
                this.grid[y][x] = parseInt(binaryString[i]);
            }
        }

        this.originHash = sha256(binaryString);
        this.currentHash = this.originHash;

        const hexColor = this.originHash.substring(3, 9);
        this.intrinsicColor = "#" + hexColor;

        this.history.clear();
        this.history.add(this.originHash);
        this.isActive = true;

        this.render();
    }

    // The core GOL logic with Toroidal wrapping
    computeNextGeneration() {
        let nextGrid = this.createGrid();

        for (let y = 0; y < this.n; y++) {
            for (let x = 0; x < this.n; x++) {
                const neighbors = this.countNeighbors(x, y);
                const currentState = this.grid[y][x];

                if (currentState === 1 && (neighbors === 2 || neighbors === 3)) {
                    nextGrid[y][x] = 1; // Survival
                } else if (currentState === 0 && neighbors === 3) {
                    nextGrid[y][x] = 1; // Birth
                } else {
                    nextGrid[y][x] = 0; // Death
                }
            }
        }

        // Calculate hash of the potential next state
        const nextBinary = nextGrid.flat().join('');
        const nextHash = sha256(nextBinary);

        // Check if this state has appeared before (Cycle Detection)
        if (this.history.has(nextHash)) {
            this.isActive = false;
            // console.log(`Unit at ${this.canvas.id} halted: State already exists in history.`);
            return false; 
        }

        // State is unique: Proceed with update
        this.grid = nextGrid;
        this.iteration++;
        this.currentHash = nextHash;
        this.history.add(nextHash);
        return true;
    }

    countNeighbors(x, y) {
        let count = 0;
        for (let i = -1; i <= 1; i++) {
            for (let j = -1; j <= 1; j++) {
                if (i === 0 && j === 0) continue;
                
                // Torus wrapping logic: (coord + max) % max
                const nx = (x + j + this.n) % this.n;
                const ny = (y + i + this.n) % this.n;
                count += this.grid[ny][nx];
            }
        }
        return count;
    }

    render() {
        const cellSize = this.canvas.width / this.n;
        const radius = (cellSize / 2) * 0.8; // 80% of half-cell width for spacing
        
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        
        for (let y = 0; y < this.n; y++) {
            for (let x = 0; x < this.n; x++) {
                if (this.grid[y][x] === 1) {
                    this.ctx.beginPath();
                    
                    // Calculate center point of the cell
                    const centerX = x * cellSize + (cellSize / 2);
                    const centerY = y * cellSize + (cellSize / 2);
                    
                    this.ctx.arc(centerX, centerY, radius, 0, 2 * Math.PI);
                    this.ctx.fillStyle = this.intrinsicColor;
                    this.ctx.fill();
                    
                    // Optional: Add a slight glow effect to the circles
                    this.ctx.shadowBlur = 5;
                    this.ctx.shadowColor = this.intrinsicColor;
                }
            }
        }
        // Reset shadow so it doesn't affect other drawing operations
        this.ctx.shadowBlur = 0;
    }

    getPopulationCount() {
        let count = 0;
        // Iterate through the rows
        for (let y = 0; y < this.n; y++) {
            // Iterate through the columns
            for (let x = 0; x < this.n; x++) {
                if (this.grid[y][x] === 1) count++;
            }
        }
        return count;
    }

    resetToOrigin(binaryString) {
        this.iteration = 0;
        this.history.clear();
        this.isActive = true;
        
        // Reload the grid from the binary string we started with
        for (let i = 0; i < binaryString.length; i++) {
            const x = i % this.n;
            const y = Math.floor(i / this.n);
            if (y < this.n) {
                this.grid[y][x] = parseInt(binaryString[i]);
            }
        }
        
        this.currentHash = this.originHash;
        this.history.add(this.originHash);
        this.render();
    }

}