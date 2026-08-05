<?php
require_once __DIR__ . "/../includes/db_connect.php";
requirePersonLogin("../");
$pageTitle = "Donate Blood";

$personId = $_SESSION["person_id"];
$person = $conn->query("SELECT * FROM person WHERE person_id = '" . $conn->real_escape_string($personId) . "'")->fetch_assoc();

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $units   = intval($_POST["blood_units"] ?? 1);
    $remarks = trim($_POST["remarks"] ?? "");

    if ($person["age"] < 18 || $person["age"] > 65) {
        $message = "Sorry, donors must be between 18 and 65 years old. Your registered age is " . $person["age"] . ".";
        $messageType = "error";
    } elseif ($units < 1 || $units > 2) {
        $message = "Please enter a valid number of units (1-2).";
        $messageType = "error";
    } else {
        $agencyId = $person["registered_ia"];
        $bloodType = $person["blood_type"];

        $stmt = $conn->prepare("INSERT IGNORE INTO donor (donor_id, name, age, gender, blood_type, contact_info, registered_ia) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("ssissss", $personId, $person["name"], $person["age"], $person["gender"], $bloodType, $person["contact_info"], $agencyId);
        $stmt->execute();
        $stmt->close();

        // Recorded as PENDING — IA staff must confirm it actually happened
        // before it counts toward local/central stock or earns a certificate.
        $stmt = $conn->prepare("INSERT INTO donation (donor_id, agency_id, blood_units, remarks, verification_status) VALUES (?,?,?,?, 'PENDING')");
        $stmt->bind_param("ssis", $personId, $agencyId, $units, $remarks);
        $stmt->execute();
        $stmt->close();

        $message = "Thank you! Your donation of $units unit(s) of $bloodType has been logged as pending. Once your IA confirms it took place, it'll count toward inventory and you'll be able to print your certificate.";
        $messageType = "success";
    }
}

require_once __DIR__ . "/../includes/header.php";
?>

<div class="card">
    <h1>Donate Blood</h1>
    <p class="subtitle">Donating as <strong><?php echo htmlspecialchars($person["name"]); ?></strong> (<?php echo $person["blood_type"]; ?>). Your Intermediate Agency will confirm this donation before it's added to inventory.</p>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="POST" data-validate>
        <label>Units Donated</label>
        <input type="number" name="blood_units" value="1" min="1" max="2" required>

        <label>Remarks (optional)</label>
        <textarea name="remarks" rows="3"></textarea>

        <button type="submit">Submit Donation</button>
    </form>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
