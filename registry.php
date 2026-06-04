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
    <div class="glyph-card" style="border-top: 2px solid <?= $color ?>">
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
            <button class="copy-trigger" onclick="copyBin('<?= $row['BIN'] ?>', this)">Copy BIN</button>
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
</script>
</body>
</html>