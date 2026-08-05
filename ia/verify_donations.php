<?php
require_once __DIR__ . "/../includes/db_connect.php";
requireIaLogin("../");
$pageTitle = "Verify Donations";

$agencyId = $_SESSION["ia_agency_id"];
$message = "";
$messageType = "";
$autoMatches = [];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["donation_id"])) {
    $donationId = intval($_POST["donation_id"]);

    $res = $conn->query("SELECT * FROM donation WHERE donation_id = $donationId AND agency_id = '" . $conn->real_escape_string($agencyId) . "' AND verification_status = 'PENDING'");
    $donation = $res ? $res->fetch_assoc() : null;

    if (!$donation) {
        $message = "That donation wasn't found or was already handled.";
        $messageType = "error";
    } else {
        $donorRes = $conn->query("SELECT blood_type FROM donor WHERE donor_id = '" . $conn->real_escape_string($donation['donor_id']) . "'");
        $bloodType = $donorRes->fetch_assoc()['blood_type'];
        $units = (int)$donation['blood_units'];

        $conn->begin_transaction();
        try {
            $conn->query("UPDATE donation SET verification_status = 'VERIFIED' WHERE donation_id = $donationId");

            $stmt = $conn->prepare("INSERT INTO local_inventory (agency_id, blood_type, units_available)
                                     VALUES (?,?,?)
                                     ON DUPLICATE KEY UPDATE units_available = units_available + VALUES(units_available)");
            $stmt->bind_param("ssi", $agencyId, $bloodType, $units);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO central_inventory (system_id, blood_type, units_available)
                                     VALUES ('BDMS01', ?, ?)
                                     ON DUPLICATE KEY UPDATE units_available = units_available + VALUES(units_available)");
            $stmt->bind_param("si", $bloodType, $units);
            $stmt->execute();
            $stmt->close();

            $certNo = "CERT-" . date("Y") . "-" . str_pad($donationId, 4, "0", STR_PAD_LEFT);
            $stmt = $conn->prepare("INSERT INTO donor_certificate (donor_id, donation_id, valid_till, certificate_no)
                                     VALUES (?, ?, DATE_ADD(CURDATE(), INTERVAL 1 YEAR), ?)");
            $stmt->bind_param("sis", $donation['donor_id'], $donationId, $certNo);
            $stmt->execute();
            $stmt->close();

            $conn->query("UPDATE donor SET certificate_issued = TRUE WHERE donor_id = '" . $conn->real_escape_string($donation['donor_id']) . "'");

            $conn->commit();
            $message = "Donation confirmed. Certificate $certNo issued to the donor, and inventory updated.";
            $messageType = "success";

            // New stock just landed — see if it resolves a deficit elsewhere
            $autoMatches = runAutoMatch($conn);
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Failed to confirm donation: " . $e->getMessage();
            $messageType = "error";
        }
    }
}

$pending = $conn->query("SELECT dn.donation_id, dn.donor_id, dn.blood_units, dn.donation_date, dn.remarks, d.name, d.blood_type
                          FROM donation dn JOIN donor d ON dn.donor_id = d.donor_id
                          WHERE dn.agency_id = '" . $conn->real_escape_string($agencyId) . "' AND dn.verification_status = 'PENDING'
                          ORDER BY dn.donation_date ASC");

require_once __DIR__ . "/../includes/header.php";
?>

<div class="card">
    <h1>Verify Donations</h1>
    <p class="subtitle">Confirm a donation only once it has actually taken place at your agency. Confirming adds the units to inventory and issues the donor's certificate.</p>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php foreach ($autoMatches as $m): ?>
        <div class="alert alert-success">🔄 Auto-match: <?php echo htmlspecialchars($m); ?></div>
    <?php endforeach; ?>

    <table>
        <tr><th>Donor</th><th>Blood Type</th><th>Units</th><th>Date</th><th>Remarks</th><th></th></tr>
        <?php if ($pending->num_rows === 0): ?>
        <tr><td colspan="6">No pending donations to verify.</td></tr>
        <?php else: while ($d = $pending->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($d['name']); ?> <span class="hint">(<?php echo htmlspecialchars($d['donor_id']); ?>)</span></td>
            <td><span class="badge badge-low"><?php echo $d['blood_type']; ?></span></td>
            <td><?php echo $d['blood_units']; ?></td>
            <td><?php echo $d['donation_date']; ?></td>
            <td><?php echo htmlspecialchars($d['remarks']); ?></td>
            <td>
                <form method="POST" style="margin:0;">
                    <input type="hidden" name="donation_id" value="<?php echo $d['donation_id']; ?>">
                    <button type="submit" style="margin:0;">Confirm Donation</button>
                </form>
            </td>
        </tr>
        <?php endwhile; endif; ?>
    </table>
</div>

<p><a href="dashboard.php">&larr; Back to Dashboard</a></p>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
