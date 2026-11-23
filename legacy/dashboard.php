 <?php
require_once __DIR__ . '/auth.php';
$conn = db();

/* Run a prepared query and return all rows */
function rows($conn, $sql, $types = '', $params = []) {
    $stmt = $conn->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/* ---------- Year filter: all | nodate | 2025 ... ---------- */
$year = $_GET['year'] ?? 'all';
$where = '1=1';
$types = '';
$params = [];

if ($year === 'nodate') {
    $where = 'i.`Start Date` IS NULL';
} elseif (ctype_digit($year)) {
    $where = 'YEAR(i.`Start Date`) = ?';
    $types = 'i';
    $params = [(int)$year];
} else {
    $year = 'all';
}

/* ---------- Summary cards ---------- */
$s = rows($conn,
    "SELECT COUNT(*) AS projects,
            COALESCE(SUM(i.`Number of Beneficiaries`), 0) AS beneficiaries
     FROM innovation_hub i WHERE $where", $types, $params)[0];

$orgCount = (int)rows($conn,
    "SELECT COUNT(DISTINCT po.organization_id) AS c
     FROM innovation_hub i
     JOIN project_organisations po ON i.`project ID` = po.project_id
     WHERE $where", $types, $params)[0]['c'];

$projectsCount = (int)$s['projects'];
$beneficiaries = (int)$s['beneficiaries'];
$average = $projectsCount > 0 ? round($beneficiaries / $projectsCount) : 0;

/* ---------- Chart: all years (not filtered) ---------- */
$yearRows = rows($conn,
    "SELECT YEAR(`Start Date`) AS yr, COUNT(*) AS c,
            COALESCE(SUM(`Number of Beneficiaries`), 0) AS b
     FROM innovation_hub GROUP BY yr");

usort($yearRows, function ($a, $b) {
    if ($a['yr'] === null) return 1;
    if ($b['yr'] === null) return -1;
    return (int)$a['yr'] - (int)$b['yr'];
});

$chartLabels = [];
$chartValues = [];
$chartKeys = [];
$chartYears = [];
$chartCounts = [];
$availableYears = [];

foreach ($yearRows as $r) {
    $label = $r['yr'] === null ? 'No date' : (string)$r['yr'];
    $chartLabels[] = $label . ' (' . $r['c'] . ' project' . ($r['c'] == 1 ? '' : 's') . ')';
    $chartValues[] = (int)$r['b'];
    $chartKeys[] = $r['yr'] === null ? 'nodate' : (string)$r['yr'];
    $chartYears[] = $r['yr'] === null ? 'No date' : (string)$r['yr'];
    $chartCounts[] = (int)$r['c'];
    if ($r['yr'] !== null) {
        $availableYears[] = (string)$r['yr'];
    }
}

/* ---------- Top sponsors and largest projects (filtered) ---------- */
$sponsors = rows($conn,
    "SELECT o.organization_name AS name, COUNT(DISTINCT i.`project ID`) AS c
     FROM innovation_hub i
     JOIN project_organisations po ON i.`project ID` = po.project_id
     JOIN organization o ON po.organization_id = o.organization_id
     WHERE $where
     GROUP BY o.organization_id, o.organization_name
     ORDER BY c DESC, name ASC LIMIT 5", $types, $params);

$largest = rows($conn,
    "SELECT i.`Project Title` AS title,
            CAST(i.`Number of Beneficiaries` AS UNSIGNED) AS b
     FROM innovation_hub i WHERE $where
     ORDER BY b DESC LIMIT 5", $types, $params);

$maxYear = $chartValues ? max(1, max($chartValues)) : 1;
$maxSponsor = $sponsors ? max(array_column($sponsors, 'c')) : 1;
$maxLargest = $largest ? max(1, max(array_column($largest, 'b'))) : 1;

$yearLabel = $year === 'all' ? 'All years' : ($year === 'nodate' ? 'No date' : $year);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard | UEW Innovation Hub</title>
<style>
* { box-sizing: border-box; }
:root { --blue:#102a72; --purple:#6d28d9; --dark:#111827; --text:#374151; --muted:#6b7280; --border:#e5e7eb; --shadow:0 10px 30px rgba(15,23,42,.08); }
body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f5f7fb; color: var(--text); }

.navbar { position: sticky; top: 0; z-index: 10; min-height: 76px; padding: 10px 5%; display: flex; align-items: center; justify-content: space-between; gap: 20px; color: #fff; background: linear-gradient(135deg, var(--blue), var(--purple)); box-shadow: 0 5px 20px rgba(0,0,0,.15); }
.brand { display: flex; align-items: center; gap: 13px; }
.brand img { width: 52px; height: 52px; object-fit: contain; background: #fff; border-radius: 12px; padding: 4px; }
.brand h2 { margin: 0; font-size: 20px; }
.brand p { margin: 3px 0 0; font-size: 13px; opacity: .85; }
.nav-links { display: flex; gap: 6px; flex-wrap: wrap; }
.nav-links a { color: #fff; text-decoration: none; padding: 11px 16px; border-radius: 9px; font-size: 15px; }
.nav-links a:hover { background: rgba(255,255,255,.15); }
.nav-links .active { background: rgba(255,255,255,.2); }

.wrap { width: 90%; max-width: 1180px; margin: 30px auto 60px; }
.top { display: flex; justify-content: space-between; align-items: center; gap: 14px; flex-wrap: wrap; margin-bottom: 20px; }
.top h1 { margin: 0; color: var(--blue); font-size: 28px; }
.tools { display: flex; gap: 10px; }
select, .btn { padding: 11px 14px; border: 1px solid #d1d5db; border-radius: 9px; font-size: 15px; background: #fff; }
.btn { cursor: pointer; font-weight: bold; color: var(--purple); background: #eef2ff; border: none; }

.stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; margin-bottom: 22px; }
.stat { background: #fff; border: 1px solid var(--border); border-radius: 15px; padding: 22px; box-shadow: var(--shadow); }
.stat .l { color: var(--muted); font-size: 14px; margin-bottom: 8px; }
.stat .v { color: var(--dark); font-size: 30px; font-weight: 800; }

.panel { background: #fff; border: 1px solid var(--border); border-radius: 15px; padding: 22px; box-shadow: var(--shadow); margin-bottom: 22px; }
.panel h3 { margin: 0 0 4px; color: var(--blue); font-size: 18px; }
.panel .sub { margin: 0 0 16px; color: var(--muted); font-size: 13px; }
.two { display: grid; grid-template-columns: 1fr 1fr; gap: 22px; }
.two .panel { margin-bottom: 0; }

.bar-row { display: grid; grid-template-columns: 190px 1fr 40px; align-items: center; gap: 12px; margin-bottom: 11px; font-size: 14px; }
.bar-row .n { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--dark); }
.track { height: 12px; background: #f1f5f9; border-radius: 4px; overflow: hidden; }
.fill { height: 100%; background: #2a78d6; border-radius: 0 4px 4px 0; }
.val { text-align: right; color: var(--muted); font-weight: bold; }
.empty { color: var(--muted); font-size: 14px; padding: 10px 0; }

.vchart { display: flex; gap: 18px; align-items: flex-end; }
.vcol { flex: 1; text-align: center; min-width: 0; }
.vplot { height: 230px; display: flex; flex-direction: column; justify-content: flex-end; align-items: center; }
.vbar { width: 100%; max-width: 80px; border-radius: 4px 4px 0 0; min-height: 3px; }
.vval { font-size: 14px; font-weight: bold; color: var(--dark); margin-bottom: 5px; }
.vlab { margin-top: 8px; font-size: 14px; color: var(--dark); font-weight: bold; }
.vlab small { display: block; color: var(--muted); font-weight: normal; font-size: 12px; margin-top: 2px; }
@media (max-width: 850px) {
    .stats { grid-template-columns: 1fr 1fr; }
    .two { grid-template-columns: 1fr; }
    .navbar { flex-direction: column; align-items: stretch; }
    .bar-row { grid-template-columns: 120px 1fr 36px; }
}
@media print {
    .navbar, .tools { display: none; }
    body { background: #fff; }
    .panel, .stat { box-shadow: none; }
}
</style>
</head>
<body>

<nav class="navbar">
    <div class="brand">
        <img src="innovation_hub_logo.jpg" alt="UEW Innovation Hub">
        <div>
            <h2>UEW Innovation Hub</h2>
            <p>Project Management System</p>
        </div>
    </div>
    <div class="nav-links">
        <a href="home.php">Home</a>
        <a href="index.php">Projects</a>
        <a href="dashboard.php" class="active">Dashboard</a>
        <?php if (is_admin()): ?><a href="users.php">Users</a><?php endif; ?>
        <?php if (is_logged_in()): ?><a href="logout.php">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a><?php else: ?><a href="login.php">Admin Login</a><?php endif; ?>
    </div>
</nav>

<div class="wrap">

    <div class="top">
        <h1>Dashboard</h1>
        <div class="tools">
            <select onchange="location.href='dashboard.php?year=' + this.value" aria-label="Filter by year">
                <option value="all"<?php echo $year === 'all' ? ' selected' : ''; ?>>All years</option>
                <?php foreach (array_reverse($availableYears) as $y): ?>
                    <option value="<?php echo $y; ?>"<?php echo $year === $y ? ' selected' : ''; ?>><?php echo $y; ?></option>
                <?php endforeach; ?>
                <option value="nodate"<?php echo $year === 'nodate' ? ' selected' : ''; ?>>No date</option>
            </select>
            <a class="btn" href="export.php?year=<?php echo urlencode($year); ?>" style="text-decoration:none;display:inline-block;">⬇ Export Excel</a>
            <button class="btn" type="button" onclick="window.print()">🖨 Print</button>
        </div>
    </div>

    <section class="stats">
        <div class="stat"><div class="l">Projects</div><div class="v"><?php echo number_format($projectsCount); ?></div></div>
        <div class="stat"><div class="l">Organisations involved</div><div class="v"><?php echo number_format($orgCount); ?></div></div>
        <div class="stat"><div class="l">Beneficiaries</div><div class="v"><?php echo number_format($beneficiaries); ?></div></div>
        <div class="stat"><div class="l">Average per project</div><div class="v"><?php echo number_format($average); ?></div></div>
    </section>

    <section class="panel">
        <h3>Beneficiaries by year</h3>
        <p class="sub">All years shown. <?php echo $year === 'all' ? '' : 'The selected period (' . htmlspecialchars($yearLabel) . ') is highlighted.'; ?></p>
        <?php if (!$chartValues): ?>
            <div class="empty">No projects yet.</div>
        <?php else: ?>
        <div class="vchart">
            <?php foreach ($chartValues as $k => $val): ?>
                <?php $active = ($year === 'all' || $year === $chartKeys[$k]); ?>
                <div class="vcol">
                    <div class="vplot">
                        <div class="vval"><?php echo number_format($val); ?></div>
                        <div class="vbar" style="height:<?php echo round($val / $maxYear * 180); ?>px;background:<?php echo $active ? '#2a78d6' : '#c3c2b7'; ?>"></div>
                    </div>
                    <div class="vlab">
                        <?php echo htmlspecialchars($chartYears[$k]); ?>
                        <small><?php echo $chartCounts[$k]; ?> project<?php echo $chartCounts[$k] == 1 ? '' : 's'; ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>

    <section class="two">

        <div class="panel">
            <h3>Top sponsors</h3>
            <p class="sub">Number of projects linked · <?php echo htmlspecialchars($yearLabel); ?></p>
            <?php if (!$sponsors): ?>
                <div class="empty">No linked organisations for this period.</div>
            <?php endif; ?>
            <?php foreach ($sponsors as $sp): ?>
                <div class="bar-row">
                    <span class="n" title="<?php echo htmlspecialchars($sp['name']); ?>"><?php echo htmlspecialchars($sp['name']); ?></span>
                    <div class="track"><div class="fill" style="width:<?php echo round($sp['c'] / $maxSponsor * 100); ?>%"></div></div>
                    <span class="val"><?php echo (int)$sp['c']; ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="panel">
            <h3>Largest projects</h3>
            <p class="sub">By number of beneficiaries · <?php echo htmlspecialchars($yearLabel); ?></p>
            <?php if (!$largest): ?>
                <div class="empty">No projects for this period.</div>
            <?php endif; ?>
            <?php foreach ($largest as $p): ?>
                <div class="bar-row">
                    <span class="n" title="<?php echo htmlspecialchars($p['title']); ?>"><?php echo htmlspecialchars($p['title']); ?></span>
                    <div class="track"><div class="fill" style="width:<?php echo round($p['b'] / $maxLargest * 100); ?>%"></div></div>
                    <span class="val"><?php echo number_format((int)$p['b']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>

    </section>

</div>



</body>
</html>