<?php
    include_once __DIR__ . '/../../priv/db_conf_laniakea.php';

    $glyph = null;
    $glyph_name = $_GET['glyph_name'] ?? null;

    if ($glyph_name){
        $query = $pdo->prepare("SELECT 
            BATTLE_NAME, 
            BIN, 
            ITERATIONS as GENERATIONS, 
            PEAK, 
            MAX, 
            MIN, 
            HASH, 
            TERMINAL, 
            OWNER 
        FROM GLYPHREG
        WHERE BATTLE_NAME = ?;");
        $query->execute([$glyph_name]);
        $glyph = $query->fetchAll(PDO::FETCH_ASSOC);
    }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BUREAU OF ENTROPY // CHAMPION_DISPLAY</title>
    <style>
        :root {
            --terminal-green: #39ff14;
            --terminal-dark: #050f05;
            --terminal-panel: #0a1c0a;
            --terminal-border: #143d14;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body, html {
            width: 100%;
            height: 100%;
            overflow: hidden;
            background-color: var(--terminal-dark);
            font-family: 'Courier New', Courier, monospace;
            color: var(--terminal-green);
        }

        #canvas-container {
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0;
            left: 0;
            z-index: 1;
        }

        /* Scanline Overlay Effect */
        .scanlines {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(rgba(18, 16, 16, 0) 50%, rgba(0, 0, 0, 0.25) 50%), linear-gradient(90deg, rgba(255, 0, 0, 0.06), rgba(0, 255, 0, 0.02), rgba(0, 0, 255, 0.06));
            background-size: 100% 4px, 6px 100%;
            z-index: 10;
            pointer-events: none;
        }

        /* Control Panel UI */
        .ui-panel {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 20;
            background-color: rgba(10, 28, 10, 0.85);
            border: 1px solid var(--terminal-green);
            padding: 20px;
            width: 340px;
            box-shadow: 0 0 15px rgba(57, 255, 20, 0.2);
        }

        .ui-header {
            font-weight: bold;
            font-size: 14px;
            border-bottom: 1px dashed var(--terminal-green);
            padding-bottom: 8px;
            margin-bottom: 15px;
            text-transform: uppercase;
        }

        .input-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            font-size: 11px;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        input[type="text"] {
            width: 100%;
            background: #000;
            border: 1px solid var(--terminal-border);
            color: var(--terminal-green);
            padding: 8px;
            font-family: monospace;
            font-size: 12px;
        }

        input[type="text"]:focus {
            outline: none;
            border-color: var(--terminal-green);
            box-shadow: 0 0 5px rgba(57, 255, 20, 0.5);
        }

        .btn-container {
            display: flex;
            gap: 10px;
        }

        button {
            flex: 1;
            background: var(--terminal-border);
            border: 1px solid var(--terminal-green);
            color: var(--terminal-green);
            padding: 10px;
            cursor: pointer;
            font-family: monospace;
            font-weight: bold;
            text-transform: uppercase;
            transition: all 0.2s ease;
        }

        button:hover {
            background: var(--terminal-green);
            color: #000;
            box-shadow: 0 0 10px var(--terminal-green);
        }

        .status-ticker {
            font-size: 11px;
            margin-top: 15px;
            color: #1b7a0b;
        }

        /* Error/Info messages */
        #msg {
            color: #ff3333;
            font-size: 11px;
            margin-top: 5px;
            min-height: 14px;
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
</head>
<body>

    <div class="scanlines"></div>
    <div id="canvas-container"></div>

    <div class="ui-panel">
        <div class="ui-header">CHAMPION CONWAY GLYPH</div>
        
        <div class="input-group">
            <label for="glyphName">Glyph Variant Name</label>
            <input type="text" id="glyphName" value="CATCH-2222" placeholder="e.g. BLACK SWAN">
        </div>

        <div class="input-group">
            <label for="owner">Owner</label>
            <input type="text" id="owner" maxlength="256" placeholder="Name...">
        </div>

        <div class="input-group">
            <label for="hexColor">Active Hex Color (RGB)</label>
            <input type="text" id="hexColor" value="#39ff14" placeholder="e.g. #00ffff or ff33aa">
        </div>

        <div class="input-group">
            <label for="binaryString">Binary Seed (256 Bits)</label>
            <input type="text" id="binaryString" maxlength="256" placeholder="Enter 256 ones and zeros...">
            <div id="msg"></div>
        </div>

        <div class="btn-container">
            <button onclick="loadConfiguration()">Initialize</button>
            <button onclick="generateRandom()">Random Seed</button>
        </div>

        <div class="status-ticker">> GEN: <span id="gen">N/A</span>, PEAK: <span id="peak">N/A</span>
            <br>> MAX: <span id="max">N/A</span>, MIN: <span id="min">N/A</span>
        </div>

    </div>

    <script>

        let champion = null;

        // Ignition Execution on Dom Boot
        window.onload = () => {

            champion = <?php echo json_encode($glyph); ?>;
            // console.log(champion);

            init3D();

            if (champion == null || champion.length === 0){
                console.log(`[champion.php] window.onload(): No Glyph selected, generating one at random.`);                
                generateRandom();                
            } else {
                console.log(`[champion.php] window.onload(): Selected Glyph ${champion[0].BATTLE_NAME} is the tournament champion.`);
                populate();
            }

            animate(0);

        };

        // --- Game of Life State Logic ---
        const SIZE = 16;
        let currentGrid = new Array(SIZE * SIZE).fill(0);
        let nextGrid = new Array(SIZE * SIZE).fill(0);
        let lastSimTime = 0;
        const simInterval = 200; // Evolve every 200ms

        // Safe Torus Index helper
        function getIndex(x, y) {
            const tx = (x + SIZE) % SIZE;
            const ty = (y + SIZE) % SIZE;
            return ty * SIZE + tx;
        }

        function countNeighbors(x, y) {
            let count = 0;
            for (let i = -1; i <= 1; i++) {
                for (let j = -1; j <= 1; j++) {
                    if (i === 0 && j === 0) continue;
                    if (currentGrid[getIndex(x + i, y + j)] === 1) {
                        count++;
                    }
                }
            }
            return count;
        }

        function gameOfLifeStep() {
            for (let y = 0; y < SIZE; y++) {
                for (let x = 0; x < SIZE; x++) {
                    const idx = y * SIZE + x;
                    const neighbors = countNeighbors(x, y);
                    const isAlive = currentGrid[idx] === 1;

                    if (isAlive) {
                        nextGrid[idx] = (neighbors === 2 || neighbors === 3) ? 1 : 0;
                    } else {
                        nextGrid[idx] = (neighbors === 3) ? 1 : 0;
                    }
                }
            }
            // Swap grids
            let temp = currentGrid;
            currentGrid = nextGrid;
            nextGrid = temp;
        }

        // --- Three.js Rendering Setup ---
        let scene, camera, renderer, cardGroup;
        let cellsArray = [];
        let nameCanvas, nameTexture;
        let wireframeLine, activeMat, deadMat;
        const cardWidth = 7;
        const cardHeight = 9;

        function init3D() {
            const container = document.getElementById('canvas-container');
            
            scene = new THREE.Scene();
            scene.fog = new THREE.FogExp2(0x050f05, 0.03);

            camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 1000);
            camera.position.set(0, 0, 12);

            renderer = new THREE.WebGLRenderer({ antialias: true });
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.setClearColor(scene.fog.color);
            container.appendChild(renderer.domElement);

            // Add lighting
            const ambientLight = new THREE.AmbientLight(0x0a2c0a);
            scene.add(ambientLight);

            const pointLight = new THREE.PointLight(0xffffff, 1.2, 30);
            pointLight.position.set(5, 5, 5);
            scene.add(pointLight);

            // Base group for rotation
            cardGroup = new THREE.Group();
            scene.add(cardGroup);

            buildStatsCard();
            window.addEventListener('resize', onWindowResize, false);
        }

        function buildStatsCard() {
            // Main backing card slab
            const cardGeo = new THREE.BoxGeometry(cardWidth, cardHeight, 0.2);
            const cardMat = new THREE.MeshPhongMaterial({
                color: 0x071707,
                specular: 0x143d14,
                shininess: 30,
                side: THREE.DoubleSide
            });
            const cardMesh = new THREE.Mesh(cardGeo, cardMat);
            cardGroup.add(cardMesh);

            // Wireframe accent border
            const edgeGeo = new THREE.EdgesGeometry(cardGeo);
            const edgeMat = new THREE.LineBasicMaterial({ color: 0x39ff14, linewidth: 2 });
            wireframeLine = new THREE.LineSegments(edgeGeo, edgeMat);
            cardGroup.add(wireframeLine);

            // Dynamic Canvas Texture for Text Display
            nameCanvas = document.createElement('canvas');
            nameCanvas.width = 512;
            nameCanvas.height = 128;
            nameTexture = new THREE.CanvasTexture(nameCanvas);
            
            const textGeo = new THREE.PlaneGeometry(cardWidth - 0.8, 1.5);
            const textMat = new THREE.MeshBasicMaterial({ map: nameTexture, transparent: true });
            const textMesh = new THREE.Mesh(textGeo, textMat);
            textMesh.position.set(0, -cardHeight/2 + 1.1, 0.11);
            cardGroup.add(textMesh);

            // Setup Conways Cells Grid Materials
            activeMat = new THREE.MeshPhongMaterial({ color: 0x39ff14, emissive: 0x22aa0a });
            deadMat = new THREE.MeshPhongMaterial({ color: 0x0a220a, transparent: true, opacity: 0.15 });

            const cellSpacer = 0.35;
            const cellSize = 0.28;
            const cellGeo = new THREE.BoxGeometry(cellSize, cellSize, 0.15);
            
            const startX = -((SIZE - 1) * cellSpacer) / 2;
            const startY = ((SIZE - 1) * cellSpacer) / 2 + 0.8;

            for (let y = 0; y < SIZE; y++) {
                for (let x = 0; x < SIZE; x++) {
                    const mesh = new THREE.Mesh(cellGeo, deadMat);
                    mesh.position.set(startX + (x * cellSpacer), startY - (y * cellSpacer), 0.11);
                    cardGroup.add(mesh);
                    cellsArray.push({ mesh: mesh });
                }
            }
        }

        // Update Text Texture & Dynamic Canvas Text Color
        function updateTextDisplay(name, hexColorStr) {
            const ctx = nameCanvas.getContext('2d');
            ctx.clearRect(0, 0, nameCanvas.width, nameCanvas.height);
            
            ctx.strokeStyle = hexColorStr;
            ctx.lineWidth = 4;
            ctx.strokeRect(10, 10, nameCanvas.width - 20, nameCanvas.height - 20);

            ctx.fillStyle = hexColorStr;
            ctx.font = 'bold 36px Courier New';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(name.toUpperCase(), nameCanvas.width / 2, nameCanvas.height / 2);
            
            nameTexture.needsUpdate = true;
        }

        // Update visual block states from matrix loop
        function updateVisualGrid() {
            for (let i = 0; i < cellsArray.length; i++) {
                if (currentGrid[i] === 1) {
                    cellsArray[i].mesh.material = activeMat;
                    cellsArray[i].mesh.scale.set(1, 1, 1.8);
                } else {
                    cellsArray[i].mesh.material = deadMat;
                    cellsArray[i].mesh.scale.set(1, 1, 1);
                }
            }
        }

        // --- Interface Inputs and Parsing Handles ---
        function loadConfiguration() {
            const nameInput = document.getElementById('glyphName').value || "UNKNOWN UNT";
            let stringInput = document.getElementById('binaryString').value.trim();
            let colorInput = document.getElementById('hexColor').value.trim();
            const msgDiv = document.getElementById('msg');

            // Sanitize and validate Hex Color
            if (!colorInput.startsWith('#')) {
                colorInput = '#' + colorInput;
            }
            // Standard HEX validation regex
            const isHex = /^#[0-9A-F]{6}$/i.test(colorInput);
            if (!isHex) {
                msgDiv.textContent = "Error: Invalid Hex Color Format";
                return;
            }

            // Sanitize string down to exact values
            stringInput = stringInput.replace(/[^01]/g, '');

            if (stringInput.length !== 256) {
                msgDiv.textContent = `Error: Must be exactly 256 bits (Current: ${stringInput.length})`;
                return;
            }
            msgDiv.textContent = "";

            // Update materials dynamically based on the input color
            const threeColor = new THREE.Color(colorInput);
            activeMat.color.copy(threeColor);
            
            // Set emissive to a slightly darkened version of the input color for nice depth
            activeMat.emissive.copy(threeColor).multiplyScalar(0.6); 
            wireframeLine.material.color.copy(threeColor);

            // Synchronize Page CSS Accent Theme Styles smoothly
            document.documentElement.style.setProperty('--terminal-green', colorInput);

            // Convert string sequence directly to processing matrix array
            for (let i = 0; i < 256; i++) {
                currentGrid[i] = parseInt(stringInput[i], 10);
            }

            updateTextDisplay(nameInput, colorInput);
            updateVisualGrid();
        }

        function generateRandom() {
            let randomString = "";
            for (let i = 0; i < 256; i++) {
                randomString += Math.random() > 0.65 ? "1" : "0";
            }
            document.getElementById('binaryString').value = randomString;
            loadConfiguration();
        }

        function populate() {

            let hash = champion[0].HASH;
            let hex = hash.slice(3,9);

            document.getElementById('glyphName').value = champion[0].BATTLE_NAME;
            document.getElementById('hexColor').value = hex;
            document.getElementById('binaryString').value = champion[0].BIN;
            document.getElementById('owner').value = champion[0].OWNER;

            document.getElementById('gen').innerText = champion[0].GENERATIONS;
            document.getElementById('peak').innerText = champion[0].PEAK;
            document.getElementById('max').innerText = champion[0].MAX;
            document.getElementById('min').innerText = champion[0].MIN;

            loadConfiguration();

        }

        function onWindowResize() {
            camera.aspect = window.innerWidth / window.innerHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(window.innerWidth, window.innerHeight);
        }

        // --- Core Animation Loops ---
        function animate(timestamp) {
            requestAnimationFrame(animate);

            // Tick Conway automation steps
            if (timestamp - lastSimTime > simInterval) {
                gameOfLifeStep();
                updateVisualGrid();
                lastSimTime = timestamp;
            }

            // Steady rotation axis
            if (cardGroup) {
                cardGroup.rotation.y = Math.sin(timestamp * 0.0004) * 0.4;
                cardGroup.rotation.x = Math.cos(timestamp * 0.0002) * 0.08;
            }

            renderer.render(scene, camera);
        }

    </script>
</body>
</html>