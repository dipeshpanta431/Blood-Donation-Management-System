<?php
require_once __DIR__ . "/../includes/db_connect.php";
requirePersonLogin("../");
$pageTitle = "My Donation History";

$personId = $_SESSION["person_id"];
$donor = $conn->query("SELECT * FROM donor WHERE donor_id = '" . $conn->real_escape_string($personId) . "'")->fetch_assoc();

require_once __DIR__ . "/../includes/header.php";
?>

<div class="card">
    <h1>My Donation History</h1>

    <?php if (!$donor): ?>
        <div class="alert alert-warning">You haven't donated yet. <a href="donate.php">Make your first donation</a>.</div>
    <?php else: ?>
        <p class="subtitle"><?php echo htmlspecialchars($donor['name']); ?> (<?php echo $donor['blood_type']; ?>)</p>
        <table>
            <tr><th>Date</th><th>Units</th><th>Remarks</th><th>Verification</th><th>Certificate</th></tr>
            <?php
            $donations = $conn->query("SELECT dn.*, dc.certificate_no FROM donation dn
                                        LEFT JOIN donor_certificate dc ON dc.donation_id = dn.donation_id
                                        WHERE dn.donor_id = '" . $conn->real_escape_string($personId) . "'
                                        ORDER BY dn.donation_date DESC");
            if ($donations->num_rows === 0): ?>
            <tr><td colspan="5">No donations recorded yet.</td></tr>
            <?php else: while ($d = $donations->fetch_assoc()): ?>
            <tr>
                <td><?php echo $d['donation_date']; ?></td>
                <td><?php echo $d['blood_units']; ?></td>
                <td><?php echo htmlspecialchars($d['remarks']); ?></td>
                <td>
                    <?php if ($d['verification_status'] === 'VERIFIED'): ?>
                        <span class="badge badge-ok">Verified</span>
                    <?php else: ?>
                        <span class="badge badge-excess">Pending IA Confirmation</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($d['certificate_no']): ?>
                        <?php echo htmlspecialchars($d['certificate_no']); ?>
                        &nbsp;<a href="certificate.php">Print</a>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; endif; ?>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
