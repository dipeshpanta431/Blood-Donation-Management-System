<?php
require_once __DIR__ . "/../includes/db_connect.php";
requireAdminLogin("../");
$pageTitle = "Agency Activity";

$agencies = $conn->query("SELECT agency_id, agency_name, location FROM agency WHERE agency_type = 'IA' ORDER BY agency_id");
$iaList = [];
while ($a = $agencies->fetch_assoc()) { $iaList[] = $a; }

require_once __DIR__ . "/../includes/header.php";
?>

<div class="card">
    <h1>Agency Activity</h1>
    <p class="subtitle">Blood received and supplied at every Intermediate Agency, regardless of whether it currently shows a shortage or excess.</p>
</div>

<?php foreach ($iaList as $ia): ?>
<?php
$agencyId = $ia['agency_id'];
$totalDonated = $conn->query("SELECT COALESCE(SUM(blood_units),0) u FROM donation WHERE agency_id = '" . $conn->real_escape_string($agencyId) . "'")->fetch_assoc()['u'];
$totalSupplied = $conn->query("SELECT COALESCE(SUM(units_requested),0) u FROM blood_request
                                WHERE agency_id = '" . $conn->real_escape_string($agencyId) . "'
                                AND status IN ('FULFILLED_LOCALLY','FULFILLED_BY_TRANSFER')")->fetch_assoc()['u'];
$stock = $conn->query("SELECT li.blood_type, li.units_available,
                               EXISTS(
                                   SELECT 1 FROM blood_request br
                                   WHERE br.agency_id = li.agency_id AND br.blood_type = li.blood_type
                                   AND br.status IN ('PENDING','FORWARDED_TO_BDMS')
                               ) AS has_demand
                        FROM local_inventory li
                        WHERE li.agency_id = '" . $conn->real_escape_string($agencyId) . "'
                        ORDER BY li.blood_type");
?>
<div class="card">
    <h2><?php echo htmlspecialchars($ia['agency_name']); ?> <span class="tag"><?php echo htmlspecialchars($agencyId); ?></span></h2>
    <div class="stat-grid" style="margin-bottom:16px;">
        <div class="stat-tile"><div class="stat-value"><?php echo $totalDonated; ?></div><div class="stat-label">Units Donated (All Time)</div></div>
        <div class="stat-tile"><div class="stat-value"><?php echo $totalSupplied; ?></div><div class="stat-label">Units Supplied (All Time)</div></div>
    </div>
    <table>
        <tr><th>Blood Type</th><th>Units Available</th><th>Status</th></tr>
        <?php while ($row = $stock->fetch_assoc()):
            $units = (int)$row['units_available'];
            $hasDemand = (int)$row['has_demand'] === 1;
            $shortage = $units === 0 && $hasDemand;
            $excess = $units > 0 && !$hasDemand;
        ?>
        <tr>
            <td><?php echo $row['blood_type']; ?></td>
            <td><?php echo $units; ?></td>
            <td>
                <?php if ($shortage): ?>
                    <span class="badge badge-low">Deficit</span>
                <?php elseif ($excess): ?>
                    <span class="badge badge-excess">Excess</span>
                <?php else: ?>
                    <span class="badge badge-ok">Adequate</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>
<?php endforeach; ?>

<p><a href="dashboard.php">&larr; Back to Dashboard</a></p>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
