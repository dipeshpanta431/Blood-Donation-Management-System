<?php
require_once __DIR__ . "/../includes/db_connect.php";
requireIaLogin("../");
$pageTitle = "IA Dashboard";

$agencyId = $_SESSION["ia_agency_id"];
$agencyName = $_SESSION["ia_agency_name"];

require_once __DIR__ . "/../includes/header.php";
?>

<div class="card">
    <h1><?php echo htmlspecialchars($agencyName); ?> — Dashboard</h1>
    <p class="subtitle">Logged in as <?php echo htmlspecialchars($_SESSION["ia_staff_name"]); ?></p>
    <?php
    $pendingCount = $conn->query("SELECT COUNT(*) c FROM donation WHERE agency_id = '" . $conn->real_escape_string($agencyId) . "' AND verification_status = 'PENDING'")->fetch_assoc()['c'];
    $totalDonated = $conn->query("SELECT COALESCE(SUM(blood_units),0) u FROM donation WHERE agency_id = '" . $conn->real_escape_string($agencyId) . "' AND verification_status = 'VERIFIED'")->fetch_assoc()['u'];
    $totalSupplied = $conn->query("SELECT COALESCE(SUM(units_requested),0) u FROM blood_request WHERE agency_id = '" . $conn->real_escape_string($agencyId) . "' AND status IN ('FULFILLED_LOCALLY','FULFILLED_BY_TRANSFER')")->fetch_assoc()['u'];
    ?>
    <div class="stat-grid" style="margin-top:10px;">
        <div class="stat-tile"><div class="stat-value"><?php echo $totalDonated; ?></div><div class="stat-label">Units Donated (Verified)</div></div>
        <div class="stat-tile"><div class="stat-value"><?php echo $totalSupplied; ?></div><div class="stat-label">Units Supplied</div></div>
        <div class="stat-tile"><div class="stat-value"><?php echo $pendingCount; ?></div><div class="stat-label">Pending Donations</div></div>
    </div>
    <a class="btn" href="verify_donations.php" style="margin-top:16px;">Verify Pending Donations <?php echo $pendingCount > 0 ? "($pendingCount)" : ""; ?></a>
</div>

<div class="card">
    <h2>Local Inventory</h2>
    <p class="subtitle"><strong>Deficit</strong> = a receiver is waiting and this blood type isn't in stock. <strong>Excess</strong> = stock is sitting here with no one currently waiting for it. Both are automatically visible to BDMS for rebalancing.</p>
    <table>
        <tr><th>Blood Type</th><th>Level</th><th>Units Available</th><th>Status</th></tr>
        <?php
        $inv = $conn->query("SELECT li.blood_type, li.units_available,
                                     EXISTS(
                                         SELECT 1 FROM blood_request br
                                         WHERE br.agency_id = li.agency_id AND br.blood_type = li.blood_type
                                         AND br.status IN ('PENDING','FORWARDED_TO_BDMS')
                                     ) AS has_demand
                              FROM local_inventory li
                              WHERE li.agency_id = '" . $conn->real_escape_string($agencyId) . "'
                              ORDER BY li.blood_type");
        while ($row = $inv->fetch_assoc()):
            $units = (int)$row['units_available'];
            $hasDemand = (int)$row['has_demand'] === 1;
            $deficit = $units === 0 && $hasDemand;
            $excess = $units > 0 && !$hasDemand;
            $pct = min(100, round($units / 10 * 100));
        ?>
        <tr>
            <td><?php echo $row['blood_type']; ?></td>
            <td>
                <div class="drop-gauge" style="--pct: <?php echo $pct; ?>%;">
                    <div class="drop-gauge-fill <?php echo $excess ? 'excess' : ''; ?>"></div>
                    <div class="drop-gauge-label"><?php echo $row['blood_type']; ?></div>
                </div>
            </td>
            <td><?php echo $units; ?></td>
            <td>
                <?php if ($deficit): ?>
                    <span class="badge badge-low">Deficit — Flagged to BDMS</span>
                <?php elseif ($excess): ?>
                    <span class="badge badge-excess">Excess — No Current Demand</span>
                <?php else: ?>
                    <span class="badge badge-ok">Adequate</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

<div class="card">
    <h2>Requests at This Agency</h2>
    <table>
        <tr><th>Request ID</th><th>Receiver</th><th>Blood Type</th><th>Units</th><th>Status</th><th>Date</th></tr>
        <?php
        $reqs = $conn->query("SELECT br.request_id, r.name, br.blood_type, br.units_requested, br.status, br.request_date
                               FROM blood_request br JOIN receiver r ON br.receiver_id = r.receiver_id
                               WHERE br.agency_id = '" . $conn->real_escape_string($agencyId) . "'
                               ORDER BY br.request_date DESC LIMIT 20");
        while ($row = $reqs->fetch_assoc()):
        ?>
        <tr>
            <td><?php echo $row['request_id']; ?></td>
            <td><?php echo htmlspecialchars($row['name']); ?></td>
            <td><?php echo $row['blood_type']; ?></td>
            <td><?php echo $row['units_requested']; ?></td>
            <td><?php echo $row['status']; ?></td>
            <td><?php echo $row['request_date']; ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

<div class="card">
    <h2>Registered Donors</h2>
    <table>
        <tr><th>Donor ID</th><th>Name</th><th>Blood Type</th><th>Contact</th></tr>
        <?php
        $donors = $conn->query("SELECT donor_id, name, blood_type, contact_info FROM donor WHERE registered_ia = '" . $conn->real_escape_string($agencyId) . "' ORDER BY donor_id DESC LIMIT 20");
        while ($row = $donors->fetch_assoc()):
        ?>
        <tr>
            <td><?php echo $row['donor_id']; ?></td>
            <td><?php echo htmlspecialchars($row['name']); ?></td>
            <td><?php echo $row['blood_type']; ?></td>
            <td><?php echo htmlspecialchars($row['contact_info']); ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
