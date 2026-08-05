<?php
require_once __DIR__ . "/../includes/db_connect.php";
requireAdminLogin("../");
$pageTitle = "BDMS Admin Dashboard";

require_once __DIR__ . "/../includes/header.php";
?>

<div class="card">
    <h1>BDMS Admin Dashboard</h1>
    <p class="subtitle">Logged in as <?php echo htmlspecialchars($_SESSION["admin_name"]); ?>. Monitor the central inventory and manage requests forwarded by Intermediate Agencies.</p>
    <a class="btn" href="reports.php">View Reports &amp; Statistics</a>
    <a class="btn btn-secondary" href="manage_agencies.php">Manage Agencies</a>
    <a class="btn btn-secondary" href="people.php">Donors &amp; Receivers</a>
    <a class="btn btn-secondary" href="agency_activity.php">Agency Activity</a>
    <a class="btn" href="certificate.php">Look Up a Certificate</a>
    <a class="btn" href="campaigns.php">Donation Campaigns</a>
    <a class="btn" href="rebalance.php">Auto-Match Rebalancing</a>
</div>

<div class="card">
    <h2>Central Inventory (System-Wide Buffer)</h2>
    <p class="subtitle">This is the aggregate buffer across all agencies — a general health check, separate from the per-IA shortage/excess conditions shown on the Auto-Match page.</p>
    <table>
        <tr><th>Blood Type</th><th>Level</th><th>Units Available</th><th>Status</th></tr>
        <?php
        $inv = $conn->query("SELECT blood_type, units_available FROM central_inventory WHERE system_id = 'BDMS01' ORDER BY blood_type");
        while ($row = $inv->fetch_assoc()):
            $low = $row['units_available'] < 5;
            $pct = min(100, round($row['units_available'] / 20 * 100));
        ?>
        <tr>
            <td><?php echo $row['blood_type']; ?></td>
            <td>
                <div class="drop-gauge" style="--pct: <?php echo $pct; ?>%;">
                    <div class="drop-gauge-fill <?php echo $low ? '' : 'excess'; ?>"></div>
                    <div class="drop-gauge-label"><?php echo $row['blood_type']; ?></div>
                </div>
            </td>
            <td><?php echo $row['units_available']; ?></td>
            <td><span class="badge <?php echo $low ? 'badge-low' : 'badge-ok'; ?>"><?php echo $low ? 'Below Buffer' : 'Healthy'; ?></span></td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

<div class="card">
    <h2>Requests Forwarded to BDMS</h2>
    <p class="subtitle">These requests could not be fulfilled locally. Search other agencies and assign transport.</p>
    <table>
        <tr><th>Request ID</th><th>Receiver</th><th>Blood Type</th><th>Units</th><th>Requesting IA</th><th>Action</th></tr>
        <?php
        $reqs = $conn->query("SELECT br.request_id, r.name, br.blood_type, br.units_requested, br.agency_id, ag.agency_name
                               FROM blood_request br
                               JOIN receiver r ON br.receiver_id = r.receiver_id
                               JOIN agency ag ON br.agency_id = ag.agency_id
                               WHERE br.status = 'FORWARDED_TO_BDMS'
                               ORDER BY br.request_date DESC");
        while ($row = $reqs->fetch_assoc()):
        ?>
        <tr>
            <td><?php echo $row['request_id']; ?></td>
            <td><?php echo htmlspecialchars($row['name']); ?></td>
            <td><?php echo $row['blood_type']; ?></td>
            <td><?php echo $row['units_requested']; ?></td>
            <td><?php echo htmlspecialchars($row['agency_name']); ?></td>
            <td><a class="btn" href="handle_request.php?request_id=<?php echo $row['request_id']; ?>">Handle</a></td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
