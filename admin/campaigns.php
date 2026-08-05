<?php
require_once __DIR__ . "/../includes/db_connect.php";
$pageTitle = "Active Campaigns";

// General blood donation awareness page to promote via Facebook share
$awarenessUrl = "https://www.who.int/campaigns/world-blood-donor-day";

$sql = "SELECT 
            br.request_id,
            r.name AS receiver_name,
            r.blood_type_required AS blood_group,
            a.agency_name AS ia_name,
            a.location AS ia_location,
            br.units_requested,
            br.request_date,
            br.status
        FROM blood_request br
        JOIN receiver r ON br.receiver_id = r.receiver_id
        JOIN intermediate_agency ia ON br.agency_id = ia.agency_id
        JOIN agency a ON ia.agency_id = a.agency_id
        WHERE br.status IN ('PENDING', 'FORWARDED_TO_BDMS')
        ORDER BY br.request_date ASC";

$result = $conn->query($sql);

require_once __DIR__ . "/../includes/header.php";
?>

<h1>Active Campaigns</h1>
<p class="hint">Blood shortage requests currently awaiting donors.</p>

<?php if ($result->num_rows === 0): ?>
    <div class="alert alert-success">No active shortages right now — all requests are fulfilled.</div>
<?php else: ?>
    <div class="campaign-grid">
        <?php while ($row = $result->fetch_assoc()):
            $shareText = urlencode(
                "Urgent: " . $row["blood_group"] . " blood needed for " . $row["receiver_name"] .
                " near " . $row["ia_name"] . ". Please consider donating blood today!"
            );
            $shareUrl = "https://www.facebook.com/sharer/sharer.php?u=" . urlencode($awarenessUrl) . "&quote=" . $shareText;
        ?>
            <div class="card campaign-card">
                <h3><?php echo htmlspecialchars($row["blood_group"]); ?> Needed</h3>
                <p><strong>Receiver:</strong> <?php echo htmlspecialchars($row["receiver_name"]); ?></p>
                <p><strong>Nearest IA:</strong> <?php echo htmlspecialchars($row["ia_name"]); ?>
                    <?php if ($row["ia_location"]): ?>
                        (<?php echo htmlspecialchars($row["ia_location"]); ?>)
                    <?php endif; ?>
                </p>
                <p><strong>Units Requested:</strong> <?php echo (int)$row["units_requested"]; ?></p>
                <p><strong>Requested On:</strong> <?php echo date("d M Y", strtotime($row["request_date"])); ?></p>
                <p class="urge-text">🩸 Every drop counts — your donation could save a life today.</p>
                <a class="btn btn-fb" target="_blank" rel="noopener"
                   href="<?php echo $shareUrl; ?>">
                    Share on Facebook
                </a>
            </div>
        <?php endwhile; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>