<?php
include_once __DIR__ . '/../../priv/db_conf_laniakea.php';

// Assuming your db_conf_laniakea.php file initializes a $pdo connection variable.
// If it only defines constants, uncomment the line below to establish the PDO connection:
// $pdo = new PDO("mysql:host=" . MYSQL_HOST . ";dbname=" . MYSQL_DATABASE . ";charset=utf8mb4", MYSQL_USER, MYSQL_PASSWORD, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

// 1. Parameters (Search, Sort, Pagination)
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'ATTEMPT';
$order = isset($_GET['order']) ? $_GET['order'] : 'DESC';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 24; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Expanded allowed columns for sorting
$allowed_columns = ['ATTEMPT', 'ITERATIONS', 'TERMINAL', 'BATTLE_NAME', 'MIN', 'MAX', 'PEAK', 'SEEKTIME', 'EXPLORETIME', 'OWNER'];
if (!in_array($sort_by, $allowed_columns)) { $sort_by = 'ATTEMPT'; }
$order_sql = ($order === 'ASC') ? 'ASC' : 'DESC';

// 2. Count total for Pagination math
$where_clause = $search !== '' ? "WHERE BATTLE_NAME LIKE ? OR OWNER LIKE ?" : "";
$count_sql = "SELECT COUNT(*) as total FROM GLYPHREG $where_clause";
$c_stmt = $pdo->prepare($count_sql);

if ($search !== '') {
    $search_param = "%$search%";
    $c_stmt->execute([$search_param, $search_param]);
} else {
    $c_stmt->execute();
}
$total_rows = $c_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_rows / $limit);

// 3. Fetch Data
$sql = "SELECT BATTLE_NAME, ATTEMPT, BIN, HASH, ITERATIONS, TERMINAL, 
               MIN, MAX, PEAK, SEEKTIME, EXPLORETIME, OWNER 
        FROM GLYPHREG $where_clause 
        ORDER BY $sort_by $order_sql LIMIT ? OFFSET ?";

$stmt = $pdo->prepare($sql);

// PDO handles integer parameters for LIMIT/OFFSET perfectly when we explicitly bind them,
// or if PDO::ATTR_EMULATE_PREPARES is set to false. To remain bulletproof across setups:
if ($search !== '') {
    $stmt->bindValue(1, $search_param, PDO::PARAM_STR);
    $stmt->bindValue(2, $search_param, PDO::PARAM_STR);
    $stmt->bindValue(3, $limit, PDO::PARAM_INT);
    $stmt->bindValue(4, $offset, PDO::PARAM_INT);
} else {
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
}
$stmt->execute();
$glyphs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Glyph Registry</title>
    <style>
        :root { 
            --bg-dark: #1a2426; --panel-bg: #242f31; --border-gray: #3a474a; 
            --text-main: #d1d9db; --accent: #4CAF50; 
        }
        body { background-color: var(--bg-dark); color: var(--text-main); font-family: 'Segoe UI', sans-serif; margin: 0; padding: 15px; }
        
        .controls-wrapper { 
            max-width: 1400px; margin: 0 auto 15px; background: #161e20; 
            padding: 10px 15px; border: 1px solid var(--border-gray); 
            display: flex; justify-content: space-between; align-items: center; font-size: 0.8em; 
        }
        select, input, .btn-nav { 
            background: var(--panel-bg); color: #fff; border: 1px solid var(--border-gray); 
            padding: 4px 10px; border-radius: 3px; cursor: pointer; text-decoration: none;
        }
        .btn-nav:hover { border-color: var(--accent); }
        .btn-nav.disabled { opacity: 0.3; pointer-events: none; }

        .catalog-grid { 
            display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
            gap: 12px; max-width: 1400px; margin: 0 auto; 
        }

        .glyph-card { 
            background: var(--panel-bg); border: 1px solid var(--border-gray); 
            border-radius: 3px; overflow: hidden; display: flex; flex-direction: column; 
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            position: relative; z-index: 1;
        }
        
        .glyph-card:hover { 
            transform: translateY(-5px) scale(1.02); 
            box-shadow: 0 10px 20px rgba(0,0,0,0.6);
            border-color: #5a676a;
            z-index: 10;
        }
        
        .visual-box { background: #000; aspect-ratio: 1/1; display: flex; align-items: center; justify-content: center; padding: 8px; }
        canvas { image-rendering: pixelated; width: 100%; max-width: 130px; }
        
        .info-box { padding: 10px; font-size: 0.72em; flex-grow: 1; }
        .name-label { font-weight: bold; color: #fff; display: block; margin-bottom: 6px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; border-bottom: 1px solid #333; padding-bottom: 4px; }
        .stat-line { display: flex; justify-content: space-between; margin-bottom: 3px; color: #8a9698; }
        .stat-group { margin: 6px 0; padding: 4px 0; border-top: 1px solid #333; }
        
        .terminal-STATIC { color: #ffca28; }
        .terminal-2-CYCLE { color: #66bb6a; }
        .terminal-VOID { color: #ef5350; }
        .owner-label { color: #4fc3f7; font-weight: 500; }

        .hash-code { font-family: monospace; font-size: 0.75em; background: #161e20; padding: 4px; display: block; margin: 8px 0; color: #6a7678; text-align: center; }
        .copy-trigger { width: 100%; border: 1px solid var(--accent); color: var(--accent); background: none; padding: 4px; cursor: pointer; text-transform: uppercase; font-size: 0.65em; font-weight: bold; }
        .copy-trigger:hover { background: var(--accent); color: #fff; }

        .pagination-footer { 
            max-width: 1400px; 
            margin: 30px auto; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            gap: 20px; 
            padding-bottom: 40px; 
        }
        .page-indicator {
            color: #8a9698;
            font-size: 0.9em;
            font-weight: 500;
        }

        /* Modal Styling */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(10, 15, 16, 0.85);
            backdrop-filter: blur(4px);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease;
        }
        .modal-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }
        .modal-content {
            background: var(--panel-bg);
            border: 1px solid var(--border-gray);
            border-radius: 4px;
            width: 90%;
            max-width: 650px;
            padding: 20px;
            position: relative;
            box-shadow: 0 20px 40px rgba(0,0,0,0.8);
            transform: translateY(10px);
            transition: transform 0.2s ease;
        }
        .modal-overlay.active .modal-content {
            transform: translateY(0);
        }
        .modal-close {
            position: absolute;
            top: 10px; right: 15px;
            background: none; border: none;
            color: #8a9698; font-size: 1.8em;
            cursor: pointer;
        }
        .modal-close:hover { color: #fff; }
        .modal-header {
            border-bottom: 1px solid var(--border-gray);
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .modal-header h3 { margin: 0 0 4px 0; color: #fff; letter-spacing: 1px; }
        .modal-body {
            display: flex;
            gap: 20px;
        }
        .modal-visual-pane {
            flex: 1;
            max-width: 200px;
            display: flex;
            flex-direction: column;
            align-items: center;
            background: #000;
            padding: 15px;
            border: 1px solid #161e20;
            border-radius: 3px;
        }
        #modal-canvas {
            image-rendering: pixelated;
            width: 100%;
        }
        .modal-stats-pane {
            flex: 2;
            font-size: 0.85em;
        }
        .stat-group-title {
            color: var(--accent);
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.8em;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            border-bottom: 1px solid #333;
            padding-bottom: 2px;
        }
        .modal-stat-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            color: #8a9698;
        }

    </style>
</head>
<body>

<div class="controls-wrapper">
    <div style="display:flex; align-items:center; gap:15px;">
        <h2 style="margin:0; font-size:1em; letter-spacing:1px;">REGISTRY</h2>
        <form method="GET" style="display:flex; gap:8px;">
            <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>" style="width:100px;">
            <select name="sort" onchange="this.form.submit()">
                <option value="ATTEMPT" <?= $sort_by=='ATTEMPT'?'selected':'' ?>>Attempt</option>
                <option value="ITERATIONS" <?= $sort_by=='ITERATIONS'?'selected':'' ?>>Generations</option>
                <option value="TERMINAL" <?= $sort_by=='TERMINAL'?'selected':'' ?>>Terminal State</option>
                <option value="PEAK" <?= $sort_by=='PEAK'?'selected':'' ?>>Peak</option>
                <option value="EXPLORETIME" <?= $sort_by=='EXPLORETIME'?'selected':'' ?>>Recent</option>
                <option value="OWNER" <?= $sort_by=='OWNER'?'selected':'' ?>>Owner</option>
            </select>
            <select name="order" onchange="this.form.submit()">
                <option value="DESC" <?= $order=='DESC'?'selected':'' ?>>DESC</option>
                <option value="ASC" <?= $order=='ASC'?'selected':'' ?>>ASC</option>
            </select>
            <select name="limit" onchange="this.form.submit()">
                <option value="24" <?= $limit==24?'selected':'' ?>>24</option>
                <option value="48" <?= $limit==48?'selected':'' ?>>48</option>
                <option value="96" <?= $limit==96?'selected':'' ?>>96</option>
            </select>
        </form>
    </div>
    <a href="index.html" style="color:var(--accent); text-decoration:none;">← EXIT</a>
</div>

<div class="catalog-grid">
    <?php foreach ($glyphs as $row): 
        $color = "#" . substr($row['HASH'], 3, 6);
        $shortHash = substr($row['HASH'], 0, 6) . "..." . substr($row['HASH'], -6);
        
        $seekDate = date("Y-m-d", strtotime($row['SEEKTIME']));
        $exploreDate = date("Y-m-d", strtotime($row['EXPLORETIME']));
        $ownerName = !empty($row['OWNER']) ? htmlspecialchars($row['OWNER']) : "ANONYMOUS";
    ?>
        <div class="glyph-card" 
            style="border-top: 2px solid <?= $color ?>; cursor: pointer;" 
            onclick="openModal(this)"
            data-name="<?= $row['BATTLE_NAME'] ? htmlspecialchars($row['BATTLE_NAME']) : '#'.$row['ATTEMPT'] ?>"
            data-owner="<?= $ownerName ?>"
            data-bin="<?= $row['BIN'] ?>"
            data-color="<?= $color ?>"
            data-hash="<?= $shortHash ?>"
            data-iterations="<?= $row['ITERATIONS'] ?>"
            data-terminal="<?= $row['TERMINAL'] ?>"
            data-range="<?= $row['MIN'] . ' - ' . $row['MAX'] ?>"
            data-peak="<?= $row['PEAK'] ?>">
        <div class="visual-box">
            <canvas class="g-canvas" data-bin="<?= $row['BIN'] ?>" data-color="<?= $color ?>"></canvas>
        </div>
        <div class="info-box">
            <span class="name-label"><?= $row['BATTLE_NAME'] ? htmlspecialchars($row['BATTLE_NAME']) : "#".$row['ATTEMPT'] ?></span>
            
            <div class="stat-line"><span>Generations</span><span><?= $row['ITERATIONS'] ?></span></div>
            <div class="stat-line"><span>Terminal State</span><span class="terminal-<?= $row['TERMINAL'] ?>"><?= $row['TERMINAL'] ?></span></div>
            <div class="stat-line"><span>Owner</span><span class="owner-label"><?= $ownerName ?></span></div>
            
            <div class="stat-line"><span>Min - Max</span><span><?= $row['MIN'] ?> - <?= $row['MAX'] ?></span></div>
            <div class="stat-line"><span>Peak</span><span style="color:#fff;"><?= $row['PEAK'] ?></span></div>
            
            <div class="stat-group">
                <div class="stat-line" title="Seek Date"><span>Seek Time</span><span><?= $seekDate ?></span></div>
                <div class="stat-line" title="Explore Date"><span>Explore Time</span><span><?= $exploreDate ?></span></div>
            </div>

            <span class="hash-code"><?= $shortHash ?></span>
            <button class="copy-trigger" onclick="event.stopPropagation(); copyBin('<?= $row['BIN'] ?>', this)">Copy BIN</button>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="pagination-footer">
    <?php 
        $base_url = "?search=".urlencode($search)."&sort=$sort_by&order=$order&limit=$limit";
    ?>
    <a href="<?= $base_url ?>&page=<?= max(1, $page-1) ?>" 
       class="btn-nav <?= $page <= 1 ? 'disabled' : '' ?>">« Previous</a>
    
    <span class="page-indicator">Page <?= $page ?> of <?= $total_pages ?></span>

    <a href="<?= $base_url ?>&page=<?= min($total_pages, $page+1) ?>" 
       class="btn-nav <?= $page >= $total_pages ? 'disabled' : '' ?>">Next »</a>
</div>

<script>
document.querySelectorAll('.g-canvas').forEach(canvas => {
    const ctx = canvas.getContext('2d');
    const bin = canvas.dataset.bin;
    const color = canvas.dataset.color;

    const scale = 8; 
    canvas.width = 16 * scale; 
    canvas.height = 16 * scale;
    const radius = 0.45 * scale;

    for(let i=0; i<256; i++) {
        if(bin[i] === '1') {
            const x = (i % 16) * scale + (scale / 2);
            const y = Math.floor(i / 16) * scale + (scale / 2);
            
            ctx.beginPath();
            ctx.arc(x, y, radius, 0, Math.PI * 2);
            ctx.fillStyle = color;
            ctx.fill();
        }
    }
});

// Added security escape for inline strings in HTML attributes
function copyBin(str, btn) {
    navigator.clipboard.writeText(str).then(() => {
        const oldText = btn.innerText;
        btn.innerText = "COPIED";
        setTimeout(() => btn.innerText = oldText, 1000);
    });
}

function openModal(card) {
    const data = card.dataset;
    
    // 1. Assign Local Text Content Immediately
    document.getElementById('modal-glyph-name').innerText = data.name;
    document.getElementById('modal-glyph-owner').innerText = "Owner: " + data.owner;
    document.getElementById('modal-glyph-hash').innerText = data.hash;
    document.getElementById('modal-stat-iterations').innerText = data.iterations;
    document.getElementById('modal-stat-range').innerText = data.range;
    document.getElementById('modal-stat-peak').innerText = data.peak;
    
    // Set up Terminal status color classes dynamically
    const terminalEl = document.getElementById('modal-stat-terminal');
    terminalEl.innerText = data.terminal;
    terminalEl.className = 'terminal-' + data.terminal;

    // 2. Clear old dynamic query text & show a processing state
    const dynamicContainer = document.getElementById('modal-dynamic-stats');
    dynamicContainer.innerHTML = `<p style="color:#6a7678; font-style:italic; font-size:0.9em; margin:5px 0;">[ Querying combat archives... ]</p>`;

    // 3. Draw the high-res representation in the modal
    const canvas = document.getElementById('modal-canvas');
    const ctx = canvas.getContext('2d');
    const scale = 12; 
    canvas.width = 16 * scale; 
    canvas.height = 16 * scale;
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    const radius = 0.45 * scale;
    for(let i=0; i<256; i++) {
        if(data.bin[i] === '1') {
            const x = (i % 16) * scale + (scale / 2);
            const y = Math.floor(i / 16) * scale + (scale / 2);
            ctx.beginPath();
            ctx.arc(x, y, radius, 0, Math.PI * 2);
            ctx.fillStyle = data.color;
            ctx.fill();
        }
    }

    // 4. Activate Overlay layout
    document.getElementById('statsModal').classList.add('active');

    // 5. Fire off Deep Asynchronous Query
    // We pass data.name (which falls back to #ATTEMPT if unnamed)
    fetch(`php/get_glyph_stats.php?name=${encodeURIComponent(data.name)}`)
        .then(response => response.json())
        .then(res => {
            if (res.success) {
                let efficiencyColor = '#8a9698'; // Default muted gray
                if (res.efficiency_index >= 3.0) {
                    efficiencyColor = '#ff5722'; // Neon Orange/Red for "Elite Threat"
                } else if (res.efficiency_index >= 1.5) {
                    efficiencyColor = '#cfd8dc'; // Bright white/silver for "Optimal"
                }
                // Construct clean tech-readout style items inside advanced analytics including the new efficiency coefficient
                dynamicContainer.innerHTML = `
                    <div class="modal-stat-line"><span>Matches Filed:</span><strong>${res.matches_played}</strong></div>
                    <div class="modal-stat-line"><span>Rounds Processed:</span><strong>${res.rounds_played}</strong></div>
                    <div class="modal-stat-line"><span>Cumulative Points:</span><strong>${res.total_score.toLocaleString()}</strong></div>
                    <div class="modal-stat-line" style="margin-top: 8px; border-top: 1px dashed #3a474a; padding-top: 6px;">
                        <span title="Points / (Rounds × Generations)">Pts / (Rnd × Gen):</span>
                        <strong style="color: ${efficiencyColor}; font-family: monospace;">${res.efficiency_index}</strong>
                    </div>
                `;
            } else {
                dynamicContainer.innerHTML = `<p style="color:#ef5350; font-size:0.9em;">[ Error retrieving advanced metrics ]</p>`;
            }
        })
        .catch(err => {
            console.error(err);
            dynamicContainer.innerHTML = `<p style="color:#ef5350; font-size:0.9em;">[ Connection error ]</p>`;
        });
}

function closeModal(event) {
    document.getElementById('statsModal').classList.remove('active');
}

</script>

<!-- MODAL STRUCTURE FOR INDIVIDUAL GLYPH STATISTICS -->
<div id="statsModal" class="modal-overlay" onclick="closeModal(event)">
    <div class="modal-content" onclick="event.stopPropagation()">
        <button class="modal-close" onclick="closeModal()">×</button>
        <div class="modal-header">
            <h3 id="modal-glyph-name">GLYPH STATS</h3>
            <span id="modal-glyph-owner" class="owner-label"></span>
        </div>
        <div class="modal-body">
            <div class="modal-visual-pane">
                <canvas id="modal-canvas"></canvas>
                <span id="modal-glyph-hash" class="hash-code"></span>
            </div>
            <div class="modal-stats-pane">
                <div class="stat-group-title">Core Telemetry</div>
                <div class="modal-stat-line"><span>Generations:</span><strong id="modal-stat-iterations"></strong></div>
                <div class="modal-stat-line"><span>Terminal State:</span><strong id="modal-stat-terminal"></strong></div>
                <div class="modal-stat-line"><span>Min / Max Range:</span><strong id="modal-stat-range"></strong></div>
                <div class="modal-stat-line"><span>Peak Value:</span><strong id="modal-stat-peak" style="color:#fff;"></strong></div>
                
                <div class="stat-group-title" style="margin-top:15px;">Advanced Analytics</div>
                <div id="modal-dynamic-stats">
                    <p style="color:#6a7678; font-style:italic; font-size:0.9em; margin:5px 0;">
                        [ Database hook ready. Future deep queries will populate advanced data vectors here. ]
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>