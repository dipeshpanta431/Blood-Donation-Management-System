<?php
require_once __DIR__ . "/../includes/db_connect.php";
requirePersonLogin("../");
$pageTitle = "My Account";

$personId = $_SESSION["person_id"];
$person = $conn->query("SELECT * FROM person WHERE person_id = '" . $conn->real_escape_string($personId) . "'")->fetch_assoc();

$donationCount = $conn->query("SELECT COUNT(*) c FROM donation WHERE donor_id = '" . $conn->real_escape_string($personId) . "'")->fetch_assoc()["c"];
$requestCount = $conn->query("SELECT COUNT(*) c FROM blood_request WHERE receiver_id = '" . $conn->real_escape_string($personId) . "'")->fetch_assoc()["c"];

require_once __DIR__ . "/../includes/header.php";
?>

<div class="card">
    <h1>Welcome, <?php echo htmlspecialchars($person["name"]); ?></h1>
    <p class="subtitle">Blood type <?php echo $person["blood_type"]; ?> &middot; ID <?php echo htmlspecialchars($personId); ?></p>

    <div class="hero-grid">
        <a href="../donor/donate.php">🩸 Donate Blood</a>
        <a href="../receiver/request.php">🏥 Request Blood</a>
        <a href="../donor/my_donations.php">📜 My Donation History <span class="hint">(<?php echo $donationCount; ?>)</span></a>
        <a href="../receiver/my_requests.php">📄 My Request History <span class="hint">(<?php echo $requestCount; ?>)</span></a>
        <a href="../donor/certificate.php">🎖️ Print My Certificate</a>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
