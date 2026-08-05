<?php
require_once __DIR__ . "/../includes/db_connect.php";
requirePersonLogin("../");
$pageTitle = "Request Blood";

$personId = $_SESSION["person_id"];
$person = $conn->query("SELECT * FROM person WHERE person_id = '" . $conn->real_escape_string($personId) . "'")->fetch_assoc();

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $bloodType = $_POST["blood_type"] ?? "";
    $agencyId  = $_POST["agency_id"] ?? "";
    $units     = intval($_POST["units_requested"] ?? 1);

    if ($bloodType === "" || $agencyId === "" || $units < 1) {
        $message = "Please fill in all fields.";
        $messageType = "error";
    } else {
        $forwarded = false;
        $conn->begin_transaction();
        try {
            // Make sure a receiver record exists for this person (created on first request)
            $stmt = $conn->prepare("INSERT IGNORE INTO receiver (receiver_id, name, blood_type_required, contact_info) VALUES (?,?,?,?)");
            $stmt->bind_param("ssss", $personId, $person["name"], $person["blood_type"], $person["contact_info"]);
            $stmt->execute();
            $stmt->close();

            // Check local inventory at chosen IA
            $invRes = $conn->query("SELECT units_available FROM local_inventory
                                     WHERE agency_id = '" . $conn->real_escape_string($agencyId) . "'
                                     AND blood_type = '" . $conn->real_escape_string($bloodType) . "'");
            $available = $invRes && $invRes->num_rows > 0 ? $invRes->fetch_assoc()["units_available"] : 0;

            if ($available >= $units) {
                $stmt = $conn->prepare("INSERT INTO blood_request (receiver_id, agency_id, blood_type, units_requested, status)
                                         VALUES (?,?,?,?, 'FULFILLED_LOCALLY')");
                $stmt->bind_param("sssi", $personId, $agencyId, $bloodType, $units);
                $stmt->execute();
                $stmt->close();

                $conn->query("UPDATE local_inventory SET units_available = units_available - $units
                              WHERE agency_id = '" . $conn->real_escape_string($agencyId) . "' AND blood_type = '" . $conn->real_escape_string($bloodType) . "'");
                $conn->query("UPDATE central_inventory SET units_available = units_available - $units
                              WHERE system_id = 'BDMS01' AND blood_type = '" . $conn->real_escape_string($bloodType) . "'");

                $message = "Good news — blood was available locally and has been supplied to you.";
                $messageType = "success";
            } else {
                $stmt = $conn->prepare("INSERT INTO blood_request (receiver_id, agency_id, blood_type, units_requested, status)
                                         VALUES (?,?,?,?, 'FORWARDED_TO_BDMS')");
                $stmt->bind_param("sssi", $personId, $agencyId, $bloodType, $units);
                $stmt->execute();
                $stmt->close();

                $message = "Blood type $bloodType was not available at this agency right now. Your request has been forwarded to the central BDMS system, which will search other agencies.";
                $messageType = "warning";
                $forwarded = true;
            }

            $conn->commit();

            if ($forwarded) {
                // A new deficit just appeared — see if an excess elsewhere can cover it immediately
                $autoMatches = runAutoMatch($conn);
                if (!empty($autoMatches)) {
                    $message .= " Good news — a match was found: " . end($autoMatches);
                }
            }
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Request failed: " . $e->getMessage();
            $messageType = "error";
        }
    }
}

$agencies = $conn->query("SELECT agency_id, agency_name FROM agency WHERE agency_type = 'IA'");

require_once __DIR__ . "/../includes/header.php";
?>

<div class="card">
    <h1>Request Blood</h1>
    <p class="subtitle">Requesting as <strong><?php echo htmlspecialchars($person["name"]); ?></strong>. Choose the blood type actually needed — it doesn't have to match your own, in case you're requesting on behalf of someone else.</p>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="POST" data-validate>
        <label>Blood Type Needed</label>
        <select name="blood_type" required>
            <option value="">-- Select --</option>
            <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bt): ?>
                <option value="<?php echo $bt; ?>" <?php echo $bt === $person['blood_type'] ? 'selected' : ''; ?>><?php echo $bt; ?></option>
            <?php endforeach; ?>
        </select>

        <label>Units Needed</label>
        <input type="number" name="units_requested" value="1" min="1" max="10" required>

        <label>Request at (Intermediate Agency)</label>
        <select name="agency_id" required>
            <option value="">-- Select IA --</option>
            <?php while ($a = $agencies->fetch_assoc()): ?>
                <option value="<?php echo $a['agency_id']; ?>" <?php echo $a['agency_id'] === $person['registered_ia'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($a['agency_name']); ?></option>
            <?php endwhile; ?>
        </select>

        <button type="submit">Submit Request</button>
    </form>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
