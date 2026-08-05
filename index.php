<?php
require_once __DIR__ . "/includes/db_connect.php";
$pageTitle = "Blood Donation Management System";

$isAdmin  = !empty($_SESSION["admin_id"]);
$isIa     = !empty($_SESSION["ia_staff_id"]);
$isTa     = !empty($_SESSION["ta_staff_id"]);
$isPerson = !empty($_SESSION["person_id"]);
$loggedIn = $isAdmin || $isIa || $isTa || $isPerson;

$totalDonated = $conn->query("SELECT COALESCE(SUM(blood_units),0) u FROM donation WHERE verification_status = 'VERIFIED'")->fetch_assoc()["u"];
$totalSupplied = $conn->query("SELECT COALESCE(SUM(units_requested),0) u FROM blood_request
                                WHERE status IN ('FULFILLED_LOCALLY','FULFILLED_BY_TRANSFER')")->fetch_assoc()["u"];

// Same deficit/excess rule used system-wide
$conditions = $conn->query("SELECT li.blood_type, ag.agency_name, li.units_available,
                                    EXISTS(
                                        SELECT 1 FROM blood_request br
                                        WHERE br.agency_id = li.agency_id AND br.blood_type = li.blood_type
                                        AND br.status IN ('PENDING','FORWARDED_TO_BDMS')
                                    ) AS has_demand
                             FROM local_inventory li JOIN agency ag ON li.agency_id = ag.agency_id");
$deficits = [];
$excesses = [];
while ($row = $conditions->fetch_assoc()) {
    $units = (int)$row['units_available'];
    $hasDemand = (int)$row['has_demand'] === 1;
    if ($units === 0 && $hasDemand) $deficits[] = $row;
    if ($units > 0 && !$hasDemand) $excesses[] = $row;
}
$chartTotal = (int)$totalDonated + (int)$totalSupplied;
$donatedPct = $chartTotal > 0 ? $totalDonated / $chartTotal : 0;
$chartRadius = 70;
$chartCircumference = 2 * M_PI * $chartRadius;
$donatedDash = $chartCircumference * $donatedPct;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $pageTitle; ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="site-header">
    <div class="logo"><a href="index.php" style="text-decoration:none;color:white;">🩸 BDMS</a></div>
    <nav>
        <?php if ($loggedIn): ?>
            <?php if ($isAdmin): ?>
                <a href="admin/dashboard.php">Admin Dashboard</a>
                <span class="nav-user">Admin: <?php echo htmlspecialchars($_SESSION["admin_name"]); ?></span>
                <a href="admin/logout.php">Logout</a>
            <?php elseif ($isIa): ?>
                <a href="ia/dashboard.php">IA Dashboard</a>
                <span class="nav-user">IA: <?php echo htmlspecialchars($_SESSION["ia_staff_name"]); ?></span>
                <a href="ia/logout.php">Logout</a>
            <?php elseif ($isTa): ?>
                <a href="transport/dashboard.php">Transport Dashboard</a>
                <span class="nav-user">Transport: <?php echo htmlspecialchars($_SESSION["ta_staff_name"]); ?></span>
                <a href="transport/logout.php">Logout</a>
            <?php elseif ($isPerson): ?>
                <a href="person/dashboard.php">My Account</a>
                <span class="nav-user"><?php echo htmlspecialchars($_SESSION["person_name"]); ?></span>
                <a href="person/logout.php">Logout</a>
            <?php endif; ?>
        <?php else: ?>
            <a href="person/login.php" class="active">Login</a>
        <?php endif; ?>
    </nav>
</header>

<main class="container">
    <div class="hero">
        <div class="hero-copy">
            
            <h1>Donate Blood. Save a Life.</h1>
            <p>One account lets you donate or request blood whenever you need to.</p>

            <?php if (!$loggedIn): ?>
            <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
                <a class="btn" href="person/login.php">Login</a>
                <a class="btn btn-outline" href="person/register.php">Create Account</a>
            </div>
            <p class="hint" style="margin-top:22px; text-align:center;">
                Staff portal &mdash; <a href="ia/login.php">Intermediate Agency</a> &middot; <a href="transport/login.php">Transport Agency</a> &middot; <a href="admin/login.php">Admin</a>
            </p>
            <?php endif; ?>
        </div>
    </div>

    
    <div class="card">
        <h2>System-Wide Statistics</h2>
        <p class="subtitle">Total blood received from donors and supplied to receivers, all-time.</p>
        <div class="stat-grid" style="margin-bottom:20px;">
            <div class="stat-tile"><div class="stat-value"><?php echo (int)$totalDonated; ?></div><div class="stat-label">Units Received from Donors</div></div>
            <div class="stat-tile"><div class="stat-value"><?php echo (int)$totalSupplied; ?></div><div class="stat-label">Units Supplied to Receivers</div></div>
        </div>
       <?php if ($chartTotal === 0): ?>
            <div class="alert alert-success" style="text-align:center;"><i class="fa-solid fa-circle-info"></i> No donation or supply activity recorded yet.</div>
        <?php else: ?>
        <div style="display:flex; align-items:center; justify-content:center; gap:28px; flex-wrap:wrap;">
            <svg viewBox="0 0 180 180" width="200" height="200" role="img" aria-label="Pie chart of blood received vs supplied">
                <circle cx="90" cy="90" r="<?php echo $chartRadius; ?>" fill="none" stroke="#0F6E66" stroke-width="30"></circle>
                <circle cx="90" cy="90" r="<?php echo $chartRadius; ?>" fill="none" stroke="#C81E3A" stroke-width="30"
                        stroke-dasharray="<?php echo $donatedDash; ?> <?php echo $chartCircumference; ?>"
                        stroke-dashoffset="0" transform="rotate(-90 90 90)"></circle>
                <text x="90" y="84" text-anchor="middle" font-family="Fraunces, Georgia, serif" font-size="22" fill="#2A2622"><?php echo $chartTotal; ?></text>
                <text x="90" y="102" text-anchor="middle" font-family="Inter, sans-serif" font-size="10" fill="#7A736C" letter-spacing="0.05em">TOTAL UNITS</text>
            </svg>
            <div style="text-align:left;">
                <p style="margin:6px 0; display:flex; align-items:center; gap:8px;"><span style="width:12px;height:12px;border-radius:50%;background:#C81E3A;display:inline-block;"></span> Received from Donors — <?php echo (int)$totalDonated; ?></p>
                <p style="margin:6px 0; display:flex; align-items:center; gap:8px;"><span style="width:12px;height:12px;border-radius:50%;background:#0F6E66;display:inline-block;"></span> Supplied to Receivers — <?php echo (int)$totalSupplied; ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Current Deficits</h2>
        <p class="subtitle">A receiver is waiting right now and this blood type isn't in stock at their agency.</p>
        <?php if (empty($deficits)): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> No deficits right now.</div>
        <?php else: ?>
            <div class="campaign-grid">
                <?php foreach ($deficits as $d): ?>
                <div class="card campaign-card" style="margin-bottom:0;">
                    <h3><?php echo $d['blood_type']; ?> Needed</h3>
                    <p><?php echo htmlspecialchars($d['agency_name']); ?> has none in stock and a receiver is waiting.</p>
                    <p class="urge-text">🩸 Please consider donating today.</p>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Current Excess</h2>
        <p class="subtitle">Stock sitting at an agency with no one currently waiting on it &mdash; a candidate for rebalancing to where it's needed.</p>
        <?php if (empty($excesses)): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> No unclaimed excess right now.</div>
        <?php else: ?>
            <div class="table-responsive">
            <table>
                <tr><th>Agency</th><th>Blood Type</th><th>Units</th></tr>
                <?php foreach ($excesses as $e): ?>
                <tr>
                    <td><?php echo htmlspecialchars($e['agency_name']); ?></td>
                    <td><span class="badge badge-excess"><?php echo $e['blood_type']; ?></span></td>
                    <td><?php echo $e['units_available']; ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            </div>
        <?php endif; ?>
    </div>
</main>

<footer class="site-footer">
    <div class="footer-inner">
        <div>
            <div class="footer-brand">🩸 BDMS</div>
            <p>Blood Donation Management System connects donors, receivers, intermediate agencies, and transport staff on one platform &mdash; so the right blood type reaches the right person, faster.</p>
            <p class="footer-contact"><i class="fa-solid fa-location-dot"></i>&nbsp; Kathmandu, Nepal</p>
            <p class="footer-contact"><i class="fa-solid fa-envelope"></i>&nbsp; support@bdms.example.com</p>
        </div>
    
        <div>
            <h4>About</h4>
            <ul>
                <li><i class="fa-solid fa-hospital"></i>&nbsp; Intermediate Agencies</li>
                <li><i class="fa-solid fa-truck-medical"></i>&nbsp; Transport Network</li>
                <li><i class="fa-solid fa-droplet"></i>&nbsp; Blood Donation Drives</li>
                <li><i class="fa-solid fa-chart-simple"></i>&nbsp; Live Statistics</li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        &copy; <?php echo date("Y"); ?> Blood Donation Management System — Student Project
    </div>
</footer>

</body>
</html>
