<?php
require_once __DIR__ . "/../includes/db_connect.php";
requirePersonLogin("../");
$pageTitle = "My Certificate";

$personId = $_SESSION["person_id"];
$res = $conn->query("SELECT dc.*, d.name, d.blood_type FROM donor_certificate dc
                      JOIN donor d ON dc.donor_id = d.donor_id
                      WHERE dc.donor_id = '" . $conn->real_escape_string($personId) . "'
                      ORDER BY dc.issue_date DESC LIMIT 1");
$cert = $res && $res->num_rows > 0 ? $res->fetch_assoc() : null;

require_once __DIR__ . "/../includes/header.php";
?>

<div class="card">
    <h1>My Certificate</h1>
    <?php if (!$cert): ?>
        <div class="alert alert-warning">No certificate yet — donate blood first to earn one. <a href="donate.php">Donate now</a>.</div>
    <?php endif; ?>
</div>

<?php if ($cert): ?>
<div class="card" style="text-align:center; border-top: 4px solid var(--crimson);">
    <h2>Certificate of Appreciation</h2>
    <p style="margin:14px 0;">This certifies that</p>
    <h1 style="margin-bottom:4px;"><?php echo htmlspecialchars($cert['name']); ?></h1>
    <p>has generously donated blood (<?php echo $cert['blood_type']; ?>) through the Blood Donation Management System.</p>
    <table style="margin-top:20px;">
        <tr><th>Certificate No.</th><td><?php echo htmlspecialchars($cert['certificate_no']); ?></td></tr>
        <tr><th>Issue Date</th><td><?php echo $cert['issue_date']; ?></td></tr>
        <tr><th>Valid Till</th><td><?php echo $cert['valid_till']; ?></td></tr>
    </table>
    <button class="btn" onclick="window.print()" style="margin-top:16px;">Print Certificate</button>
</div>
<?php endif; ?>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
