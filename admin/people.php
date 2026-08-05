<?php
require_once __DIR__ . "/../includes/db_connect.php";
requireAdminLogin("../");
$pageTitle = "Donors & Receivers";

$donors = $conn->query("SELECT d.donor_id, d.name, d.age, d.gender, d.blood_type, d.contact_info,
                                d.certificate_issued, ag.agency_name
                         FROM donor d JOIN agency ag ON d.registered_ia = ag.agency_id
                         ORDER BY d.donor_id");

$receivers = $conn->query("SELECT r.receiver_id, r.name, r.blood_type_required, r.contact_info,
                                   COUNT(br.request_id) AS request_count
                            FROM receiver r LEFT JOIN blood_request br ON br.receiver_id = r.receiver_id
                            GROUP BY r.receiver_id
                            ORDER BY r.receiver_id");

require_once __DIR__ . "/../includes/header.php";
?>

<div class="card">
    <h1>Donors &amp; Receivers</h1>
    <p class="subtitle">Full directory across every Intermediate Agency — for oversight, not day-to-day management (that stays with each IA).</p>
</div>

<div class="card">
    <h2>All Donors</h2>
    <table>
        <tr><th>ID</th><th>Name</th><th>Age</th><th>Gender</th><th>Blood Type</th><th>Contact</th><th>Registered At</th><th>Certificate</th></tr>
        <?php if ($donors->num_rows === 0): ?>
        <tr><td colspan="8">No donors registered yet.</td></tr>
        <?php else: while ($d = $donors->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($d['donor_id']); ?></td>
            <td><?php echo htmlspecialchars($d['name']); ?></td>
            <td><?php echo $d['age']; ?></td>
            <td><?php echo htmlspecialchars($d['gender']); ?></td>
            <td><span class="badge badge-low"><?php echo $d['blood_type']; ?></span></td>
            <td><?php echo htmlspecialchars($d['contact_info']); ?></td>
            <td><?php echo htmlspecialchars($d['agency_name']); ?></td>
            <td><?php echo $d['certificate_issued'] ? '✅ Issued' : '—'; ?></td>
        </tr>
        <?php endwhile; endif; ?>
    </table>
</div>

<div class="card">
    <h2>All Receivers</h2>
    <table>
        <tr><th>ID</th><th>Name</th><th>Blood Type Needed</th><th>Contact</th><th>Total Requests</th></tr>
        <?php if ($receivers->num_rows === 0): ?>
        <tr><td colspan="5">No receivers registered yet.</td></tr>
        <?php else: while ($r = $receivers->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($r['receiver_id']); ?></td>
            <td><?php echo htmlspecialchars($r['name']); ?></td>
            <td><span class="badge badge-low"><?php echo $r['blood_type_required']; ?></span></td>
            <td><?php echo htmlspecialchars($r['contact_info']); ?></td>
            <td><?php echo $r['request_count']; ?></td>
        </tr>
        <?php endwhile; endif; ?>
    </table>
</div>

<p><a href="dashboard.php">&larr; Back to Dashboard</a></p>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
