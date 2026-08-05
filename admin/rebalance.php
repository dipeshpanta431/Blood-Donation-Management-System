<?php
require_once __DIR__ . "/../includes/db_connect.php";
requireAdminLogin("../");
$pageTitle = "Auto-Match Rebalancing";

$runResults = [];
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["run_now"])) {
    $runResults = runAutoMatch($conn);
}

// Current snapshot of deficit/excess pairs system-wide (same rule used everywhere)
$BLOOD_TYPES = ['A+','A-','B+','B-','AB+','AB-','O+','O-'];
$res = $conn->query("SELECT li.agency_id, ag.agency_name, li.blood_type, li.units_available,
                             EXISTS(
                                 SELECT 1 FROM blood_request br
                                 WHERE br.agency_id = li.agency_id AND br.blood_type = li.blood_type
                                 AND br.status IN ('PENDING','FORWARDED_TO_BDMS')
                             ) AS has_demand
                      FROM local_inventory li JOIN agency ag ON li.agency_id = ag.agency_id");
$deficits = [];
$excesses = [];
while ($row = $res->fetch_assoc()) {
    $units = (int)$row['units_available'];
    $hasDemand = (int)$row['has_demand'] === 1;
    if ($units === 0 && $hasDemand) $deficits[] = $row;
    if ($units > 0 && !$hasDemand) $excesses[] = $row;
}

$recentTransfers = $conn->query("SELECT ta.*, src.agency_name AS source_name, dest.agency_name AS dest_name
                                  FROM transport_assignment ta
                                  JOIN agency src ON ta.source_agency_id = src.agency_id
                                  JOIN agency dest ON ta.dest_agency_id = dest.agency_id
                                  ORDER BY ta.assigned_at DESC LIMIT 10");

require_once __DIR__ . "/../includes/header.php";
?>

<div class="card">
    <h1>Auto-Match Rebalancing</h1>
    <p class="subtitle">
        <strong>Deficit</strong> — a receiver is waiting at an IA and that blood type isn't in stock there.
        <strong>Excess</strong> — an IA is holding stock of a type nobody there is currently waiting on.
        This runs automatically the moment a donation is verified or a request is forwarded — the button below re-runs it on demand.
    </p>
    <form method="POST" style="margin:0;">
        <button type="submit" name="run_now" value="1">Run Auto-Match Now</button>
    </form>

    <?php if (!empty($runResults)): ?>
        <?php foreach ($runResults as $r): ?>
            <div class="alert alert-success" style="margin-top:14px;">🔄 <?php echo htmlspecialchars($r); ?></div>
        <?php endforeach; ?>
    <?php elseif ($_SERVER["REQUEST_METHOD"] === "POST"): ?>
        <div class="alert alert-success" style="margin-top:14px;">No matches were needed — no unresolved deficit currently has a matching excess.</div>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Current Deficits</h2>
    <?php if (empty($deficits)): ?>
        <div class="alert alert-success">No deficits right now.</div>
    <?php else: ?>
        <table>
            <tr><th>Agency</th><th>Blood Type</th></tr>
            <?php foreach ($deficits as $d): ?>
            <tr><td><?php echo htmlspecialchars($d['agency_name']); ?></td><td><span class="badge badge-low"><?php echo $d['blood_type']; ?></span></td></tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Current Excess</h2>
    <?php if (empty($excesses)): ?>
        <div class="alert alert-success">No unclaimed excess right now.</div>
    <?php else: ?>
        <table>
            <tr><th>Agency</th><th>Blood Type</th><th>Units</th></tr>
            <?php foreach ($excesses as $e): ?>
            <tr><td><?php echo htmlspecialchars($e['agency_name']); ?></td><td><span class="badge badge-excess"><?php echo $e['blood_type']; ?></span></td><td><?php echo $e['units_available']; ?></td></tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Recent Transfers</h2>
    <table>
        <tr><th>Blood Type</th><th>From</th><th>To</th><th>Units</th><th>Status</th><th>Assigned</th></tr>
        <?php if ($recentTransfers->num_rows === 0): ?>
        <tr><td colspan="6">No transfers yet.</td></tr>
        <?php else: while ($t = $recentTransfers->fetch_assoc()): ?>
        <tr>
            <td><?php echo $t['blood_type']; ?></td>
            <td><?php echo htmlspecialchars($t['source_name']); ?></td>
            <td><?php echo htmlspecialchars($t['dest_name']); ?></td>
            <td><?php echo $t['units']; ?></td>
            <td><?php echo $t['status']; ?></td>
            <td><?php echo $t['assigned_at']; ?></td>
        </tr>
        <?php endwhile; endif; ?>
    </table>
</div>

<p><a href="dashboard.php">&larr; Back to Dashboard</a></p>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
