<?php
require_once __DIR__ . "/../includes/db_connect.php";
requirePersonLogin("../");
$pageTitle = "My Request History";

$personId = $_SESSION["person_id"];
$receiver = $conn->query("SELECT * FROM receiver WHERE receiver_id = '" . $conn->real_escape_string($personId) . "'")->fetch_assoc();

require_once __DIR__ . "/../includes/header.php";
?>

<div class="card">
    <h1>My Request History</h1>

    <?php if (!$receiver): ?>
        <div class="alert alert-warning">You haven't requested blood yet. <a href="request.php">Make a request</a>.</div>
    <?php else: ?>
        <table>
            <tr><th>Request ID</th><th>Agency</th><th>Blood Type</th><th>Units</th><th>Status</th><th>Date</th></tr>
            <?php
            $requests = $conn->query("SELECT br.*, ag.agency_name FROM blood_request br
                                       JOIN agency ag ON br.agency_id = ag.agency_id
                                       WHERE br.receiver_id = '" . $conn->real_escape_string($personId) . "'
                                       ORDER BY br.request_date DESC");
            if ($requests->num_rows === 0): ?>
            <tr><td colspan="6">No requests recorded yet.</td></tr>
            <?php else: while ($r = $requests->fetch_assoc()): ?>
            <tr>
                <td><?php echo $r['request_id']; ?></td>
                <td><?php echo htmlspecialchars($r['agency_name']); ?></td>
                <td><?php echo $r['blood_type']; ?></td>
                <td><?php echo $r['units_requested']; ?></td>
                <td><?php echo $r['status']; ?></td>
                <td><?php echo $r['request_date']; ?></td>
            </tr>
            <?php endwhile; endif; ?>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
