<?php
require_once __DIR__ . "/../includes/db_connect.php";
requireAdminLogin("../");
$pageTitle = "Reports & Statistics";

$totalDonors = $conn->query("SELECT COUNT(*) c FROM donor")->fetch_assoc()["c"];
$totalDonations = $conn->query("SELECT COUNT(*) c, COALESCE(SUM(blood_units),0) u FROM donation WHERE verification_status = 'VERIFIED'")->fetch_assoc();
$totalReceivers = $conn->query("SELECT COUNT(*) c FROM receiver")->fetch_assoc()["c"];
$totalRequests = $conn->query("SELECT COUNT(*) c FROM blood_request")->fetch_assoc()["c"];
$fulfilled = $conn->query("SELECT COUNT(*) c FROM blood_request WHERE status IN ('FULFILLED_LOCALLY','FULFILLED_BY_TRANSFER')")->fetch_assoc()["c"];
$shortages = $conn->query("SELECT blood_type, units_available FROM central_inventory WHERE units_available < 5 ORDER BY units_available ASC");
$byBloodType = $conn->query("SELECT d.blood_type, SUM(dn.blood_units) units FROM donation dn
                              JOIN donor d ON dn.donor_id = d.donor_id
                              WHERE dn.verification_status = 'VERIFIED'
                              GROUP BY d.blood_type ORDER BY d.blood_type");

// Total units actually handed over to receivers so far (locally + via transfer)
$totalSupplied = $conn->query("SELECT COALESCE(SUM(units_requested),0) u FROM blood_request
                                WHERE status IN ('FULFILLED_LOCALLY','FULFILLED_BY_TRANSFER')")->fetch_assoc()["u"];
$totalDonated = $totalDonations['u'];

require_once __DIR__ . "/../includes/header.php";
?>

<div class="card">
    <h1>Reports &amp; Statistics</h1>
    <p class="subtitle">Live, auto-generated system-wide statistics.</p>

    <div class="stat-grid">
        <div class="stat-tile"><div class="stat-value"><?php echo $totalDonors; ?></div><div class="stat-label">Registered Donors</div></div>
        <div class="stat-tile"><div class="stat-value"><?php echo $totalReceivers; ?></div><div class="stat-label">Registered Receivers</div></div>
        <div class="stat-tile"><div class="stat-value"><?php echo $totalDonated; ?></div><div class="stat-label">Units Donated (All Time)</div></div>
        <div class="stat-tile"><div class="stat-value"><?php echo $totalSupplied; ?></div><div class="stat-label">Units Supplied (All Time)</div></div>
        <div class="stat-tile"><div class="stat-value"><?php echo $fulfilled; ?>/<?php echo $totalRequests; ?></div><div class="stat-label">Requests Fulfilled</div></div>
    </div>
</div>
<div class="card">
    <h2>Donations by Blood Type</h2>
    <table>
        <tr><th>Blood Type</th><th>Units Donated</th></tr>
        <?php while ($row = $byBloodType->fetch_assoc()): ?>
        <tr><td><?php echo $row['blood_type']; ?></td><td><?php echo $row['units']; ?></td></tr>
        <?php endwhile; ?>
    </table>
</div>

<div class="card">
    <h2>Current Shortages (Central Inventory &lt; 5 units)</h2>
    <?php if ($shortages->num_rows === 0): ?>
        <p>No shortages at the moment.</p>
    <?php else: ?>
    <table>
        <tr><th>Blood Type</th><th>Units Available</th></tr>
        <?php while ($row = $shortages->fetch_assoc()): ?>
        <tr><td><?php echo $row['blood_type']; ?></td><td><?php echo $row['units_available']; ?></td></tr>
        <?php endwhile; ?>
    </table>
    <?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('donorReceiverChart'), {
    type: 'pie',
    data: {
        labels: ['Donors', 'Receivers'],
        datasets: [{
            data: [<?php echo (int)$totalDonors; ?>, <?php echo (int)$totalReceivers; ?>],
            backgroundColor: ['#C81E3A', '#0F6E66'],
            borderColor: '#FBF6F2',
            borderWidth: 3
        }]
    },
    options: {
        plugins: {
            legend: { position: 'bottom', labels: { font: { family: 'Inter', size: 13 } } }
        }
    }
});
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
