<?php
require_once __DIR__ . "/../includes/db_connect.php";
requireTaLogin("../");
$pageTitle = "Transport Dashboard";

$taId = $_SESSION["ta_agency_id"];

require_once __DIR__ . "/../includes/header.php";
?>

<div class="card">
    <h1><?php echo htmlspecialchars($_SESSION["ta_agency_name"]); ?> — Dashboard</h1>
    <p class="subtitle">Logged in as <?php echo htmlspecialchars($_SESSION["ta_staff_name"]); ?>. View and confirm assigned blood transport tasks.</p>
</div>

<div class="card">
    <h2>Assigned Transport Tasks</h2>
    <table>
        <tr><th>ID</th><th>From</th><th>To</th><th>Blood Type</th><th>Units</th><th>Status</th><th>Action</th></tr>
        <?php
        $tasks = $conn->query("SELECT ta.transport_id, ta.status, ta.blood_type, ta.units,
                                       src.agency_name AS source_name, dst.agency_name AS dest_name
                                FROM transport_assignment ta
                                JOIN agency src ON ta.source_agency_id = src.agency_id
                                JOIN agency dst ON ta.dest_agency_id = dst.agency_id
                                WHERE ta.ta_agency_id = '" . $conn->real_escape_string($taId) . "'
                                ORDER BY ta.assigned_at DESC");
        while ($t = $tasks->fetch_assoc()):
        ?>
        <tr>
            <td><?php echo $t['transport_id']; ?></td>
            <td><?php echo htmlspecialchars($t['source_name']); ?></td>
            <td><?php echo htmlspecialchars($t['dest_name']); ?></td>
            <td><?php echo $t['blood_type']; ?></td>
            <td><?php echo $t['units']; ?></td>
            <td><?php echo $t['status']; ?></td>
            <td>
                <?php if ($t['status'] !== 'CONFIRMED'): ?>
                    <a class="btn" href="confirm.php?transport_id=<?php echo $t['transport_id']; ?>">Update</a>
                <?php else: ?>
                    &mdash;
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
