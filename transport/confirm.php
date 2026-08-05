<?php
require_once __DIR__ . "/../includes/db_connect.php";
requireTaLogin("../");
$pageTitle = "Update Transport Task";

$transportId = intval($_GET["transport_id"] ?? 0);
$agencyId = $_SESSION["ta_agency_id"];
$message = "";
$messageType = "";

$statusFlow = ["ASSIGNED" => "COLLECTED", "COLLECTED" => "IN_TRANSIT", "IN_TRANSIT" => "DELIVERED", "DELIVERED" => "CONFIRMED"];
$stepLabel = [
    "ASSIGNED" => "Mark as Collected from source agency",
    "COLLECTED" => "Mark as In Transit",
    "IN_TRANSIT" => "Mark as Delivered to destination agency",
    "DELIVERED" => "Confirm Delivery & Notify BDMS",
];

$res = $conn->query("SELECT * FROM transport_assignment WHERE transport_id = $transportId");
$task = $res ? $res->fetch_assoc() : null;

if (!$task || $task["ta_agency_id"] !== $agencyId) {
    require_once __DIR__ . "/../includes/header.php";
    echo '<div class="card"><div class="alert alert-error">Transport task not found or not assigned to your agency.</div></div>';
    require_once __DIR__ . "/../includes/footer.php";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $currentStatus = $task["status"];
    if (isset($statusFlow[$currentStatus])) {
        $newStatus = $statusFlow[$currentStatus];

        $conn->begin_transaction();
        try {
            if ($newStatus === "CONFIRMED") {
                $conn->query("UPDATE transport_assignment SET status = 'CONFIRMED', delivered_at = NOW() WHERE transport_id = $transportId");

                // Add units into destination IA local inventory, then immediately release to the receiver
                $conn->query("INSERT INTO local_inventory (agency_id, blood_type, units_available)
                              VALUES ('" . $conn->real_escape_string($task['dest_agency_id']) . "', '" . $conn->real_escape_string($task['blood_type']) . "', 0)
                              ON DUPLICATE KEY UPDATE units_available = units_available");

                // Blood is handed to the receiver at the destination agency, so central inventory decreases
                $conn->query("UPDATE central_inventory SET units_available = GREATEST(units_available - {$task['units']}, 0)
                              WHERE system_id = 'BDMS01' AND blood_type = '" . $conn->real_escape_string($task['blood_type']) . "'");

                // Log the transport record (multivalued transportRecords attribute)
                $stmt = $conn->prepare("INSERT INTO transport_record (agency_id, record_note) VALUES (?, ?)");
                $note = "Delivered {$task['units']} unit(s) of {$task['blood_type']} to destination agency and confirmed.";
                $stmt->bind_param("ss", $agencyId, $note);
                $stmt->execute();
                $stmt->close();

                // Mark the oldest matching open request at the destination as fulfilled
                $conn->query("UPDATE blood_request SET status = 'FULFILLED_BY_TRANSFER'
                              WHERE agency_id = '" . $conn->real_escape_string($task['dest_agency_id']) . "'
                              AND blood_type = '" . $conn->real_escape_string($task['blood_type']) . "'
                              AND status IN ('PENDING','FORWARDED_TO_BDMS')
                              ORDER BY request_date ASC LIMIT 1");

                $message = "Delivery confirmed and BDMS notified. Central inventory updated.";
            } else {
                $conn->query("UPDATE transport_assignment SET status = '$newStatus' WHERE transport_id = $transportId");
                $message = "Task status updated to $newStatus.";
            }
            $conn->commit();
            $messageType = "success";
            $task["status"] = $newStatus;
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Update failed: " . $e->getMessage();
            $messageType = "error";
        }
    }
}

require_once __DIR__ . "/../includes/header.php";
?>

<div class="card">
    <h1>Transport Task #<?php echo $transportId; ?></h1>
    <p class="subtitle">
        <?php echo $task['units']; ?> unit(s) of <?php echo $task['blood_type']; ?> &mdash;
        current status: <strong><?php echo $task['status']; ?></strong>
    </p>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <?php if (isset($statusFlow[$task['status']])): ?>
        <form method="POST">
            <button type="submit"><?php echo $stepLabel[$task['status']]; ?></button>
        </form>
    <?php else: ?>
        <div class="alert alert-success">This delivery has been confirmed. No further action needed.</div>
    <?php endif; ?>
</div>

<p><a href="dashboard.php">&larr; Back to Dashboard</a></p>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
