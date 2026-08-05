<?php
require_once __DIR__ . "/../includes/db_connect.php";
requireAdminLogin("../");
$pageTitle = "Handle Request";

$requestId = intval($_GET["request_id"] ?? ($_POST["request_id"] ?? 0));
$message = "";
$messageType = "";

$reqRes = $conn->query("SELECT br.*, r.name AS receiver_name FROM blood_request br
                         JOIN receiver r ON br.receiver_id = r.receiver_id
                         WHERE br.request_id = $requestId");
$request = $reqRes ? $reqRes->fetch_assoc() : null;

if (!$request) {
    require_once __DIR__ . "/../includes/header.php";
    echo '<div class="card"><div class="alert alert-error">Request not found.</div></div>';
    require_once __DIR__ . "/../includes/footer.php";
    exit;
}

// Handle "launch donation campaign" action (no agency currently has stock).
// Campaigns aren't a separate table — any PENDING/FORWARDED_TO_BDMS request
// already shows up automatically on campaigns.php, so this just confirms
// the request stays visible there and points the admin to it.
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["launch_campaign"])) {
    $bloodType = $request["blood_type"];
    $message = "This shortage is now listed on the Donation Campaigns page for $bloodType. Share it to notify potential donors.";
    $messageType = "success";
}

// Handle transport assignment
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["source_agency_id"])) {
    $sourceAgency = $_POST["source_agency_id"];
    $taAgency     = $_POST["ta_agency_id"];
    $units        = $request["units_requested"];
    $bloodType    = $request["blood_type"];
    $destAgency   = $request["agency_id"];

    $conn->begin_transaction();
    try {
        // 5b/6b equivalent: Assign transportation agency
        $stmt = $conn->prepare("INSERT INTO transport_assignment (system_id, ta_agency_id, source_agency_id, dest_agency_id, blood_type, units)
                                 VALUES ('BDMS01', ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $taAgency, $sourceAgency, $destAgency, $bloodType, $units);
        $stmt->execute();
        $stmt->close();

        // Deduct from the source IA's local inventory immediately (blood is now in transit)
        $conn->query("UPDATE local_inventory SET units_available = units_available - $units
                      WHERE agency_id = '" . $conn->real_escape_string($sourceAgency) . "' AND blood_type = '" . $conn->real_escape_string($bloodType) . "'");

        $conn->query("UPDATE blood_request SET status = 'FULFILLED_BY_TRANSFER' WHERE request_id = $requestId");

        $conn->commit();
        $message = "Transport assigned. Blood is now in transit to the requesting agency.";
        $messageType = "success";
        $request["status"] = "FULFILLED_BY_TRANSFER";
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Failed to assign transport: " . $e->getMessage();
        $messageType = "error";
    }
}

// Search other IAs holding this blood type (excluding the requesting IA)
$candidates = $conn->query("SELECT li.agency_id, ag.agency_name, li.units_available
                             FROM local_inventory li JOIN agency ag ON li.agency_id = ag.agency_id
                             WHERE li.blood_type = '" . $conn->real_escape_string($request['blood_type']) . "'
                             AND li.units_available >= " . intval($request['units_requested']) . "
                             AND li.agency_id != '" . $conn->real_escape_string($request['agency_id']) . "'");

$transportAgencies = $conn->query("SELECT agency_id, agency_name FROM agency WHERE agency_type = 'TA'");

require_once __DIR__ . "/../includes/header.php";
?>

<div class="card">
    <h1>Handle Request #<?php echo $requestId; ?></h1>
    <p class="subtitle">
        <?php echo htmlspecialchars($request['receiver_name']); ?> needs
        <strong><?php echo $request['units_requested']; ?> unit(s) of <?php echo $request['blood_type']; ?></strong>.
        Current status: <strong><?php echo $request['status']; ?></strong>
    </p>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <?php if ($request['status'] === 'FORWARDED_TO_BDMS'): ?>
        <h2>Step 1 — Agencies with Available Stock</h2>
        <?php if ($candidates->num_rows === 0): ?>
            <div class="alert alert-warning">
                No other agency currently holds enough <?php echo $request['blood_type']; ?> stock.
                Launch a donation campaign to notify matching donors.
            </div>
            <form method="POST">
                <input type="hidden" name="request_id" value="<?php echo $requestId; ?>">
                <input type="hidden" name="launch_campaign" value="1">
                <button type="submit">Launch Donation Campaign for <?php echo $request['blood_type']; ?></button>
            </form>
            <h2 style="margin-top:22px;">Donors Who Would Be Notified</h2>
            <table>
                <tr><th>Donor ID</th><th>Name</th><th>Contact</th><th>Registered At</th></tr>
                <?php
                $matchDonors = $conn->query("SELECT d.donor_id, d.name, d.contact_info, ag.agency_name
                                              FROM donor d JOIN agency ag ON d.registered_ia = ag.agency_id
                                              WHERE d.blood_type = '" . $conn->real_escape_string($request['blood_type']) . "'");
                while ($d = $matchDonors->fetch_assoc()):
                ?>
                <tr>
                    <td><?php echo $d['donor_id']; ?></td>
                    <td><?php echo htmlspecialchars($d['name']); ?></td>
                    <td><?php echo htmlspecialchars($d['contact_info']); ?></td>
                    <td><?php echo htmlspecialchars($d['agency_name']); ?></td>
                </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
        <form method="POST">
            <input type="hidden" name="request_id" value="<?php echo $requestId; ?>">
            <label>Source Agency (has stock)</label>
            <select name="source_agency_id" required>
                <?php while ($c = $candidates->fetch_assoc()): ?>
                    <option value="<?php echo $c['agency_id']; ?>">
                        <?php echo htmlspecialchars($c['agency_name']); ?> (<?php echo $c['units_available']; ?> units available)
                    </option>
                <?php endwhile; ?>
            </select>

            <label>Transportation Agency</label>
            <select name="ta_agency_id" required>
                <?php while ($t = $transportAgencies->fetch_assoc()): ?>
                    <option value="<?php echo $t['agency_id']; ?>"><?php echo htmlspecialchars($t['agency_name']); ?></option>
                <?php endwhile; ?>
            </select>

            <button type="submit">Assign Transport</button>
        </form>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-success">This request has already been handled.</div>
    <?php endif; ?>
</div>

<p><a href="dashboard.php">&larr; Back to Dashboard</a></p>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
