<?php
require_once __DIR__ . "/../includes/db_connect.php";
requireAdminLogin("../");
$pageTitle = "Manage Agencies";

$message = "";
$messageType = "";
$BLOOD_TYPES = ['A+','A-','B+','B-','AB+','AB-','O+','O-'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $agencyType  = $_POST["agency_type"] ?? "";
    $agencyName  = trim($_POST["agency_name"] ?? "");
    $location    = trim($_POST["location"] ?? "");
    $staffName   = trim($_POST["staff_name"] ?? "");
    $username    = trim($_POST["username"] ?? "");
    $password    = $_POST["password"] ?? "";

    if (!in_array($agencyType, ['IA','TA']) || $agencyName === "" || $staffName === "" || $username === "" || $password === "") {
        $message = "Please fill in all required fields.";
        $messageType = "error";
    } else {
        // Check username isn't already taken in whichever staff table applies
        $staffTable = $agencyType === 'IA' ? 'ia_staff' : 'ta_staff';
        $check = $conn->prepare("SELECT staff_id FROM $staffTable WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $exists = $check->get_result()->num_rows > 0;
        $check->close();

        if ($exists) {
            $message = "That username is already taken for a $agencyType staff account. Choose another.";
            $messageType = "error";
        } else {
            $conn->begin_transaction();
            try {
                $agencyId = generateId($conn, "agency", "agency_id", $agencyType === 'IA' ? "IA" : "TA");

                $stmt = $conn->prepare("INSERT INTO agency (agency_id, agency_type, agency_name, location) VALUES (?,?,?,?)");
                $stmt->bind_param("ssss", $agencyId, $agencyType, $agencyName, $location);
                $stmt->execute();
                $stmt->close();

                if ($agencyType === 'IA') {
                    $stmt = $conn->prepare("INSERT INTO intermediate_agency (agency_id) VALUES (?)");
                    $stmt->bind_param("s", $agencyId);
                    $stmt->execute();
                    $stmt->close();

                    // Seed local_inventory with all 8 blood types at 0 units
                    $stmt = $conn->prepare("INSERT INTO local_inventory (agency_id, blood_type, units_available) VALUES (?,?,0)");
                    foreach ($BLOOD_TYPES as $bt) {
                        $stmt->bind_param("ss", $agencyId, $bt);
                        $stmt->execute();
                    }
                    $stmt->close();
                } else {
                    $stmt = $conn->prepare("INSERT INTO transportation_agency (agency_id) VALUES (?)");
                    $stmt->bind_param("s", $agencyId);
                    $stmt->execute();
                    $stmt->close();
                }

                $staffId = generateId($conn, $staffTable, "staff_id", $agencyType === 'IA' ? "STF" : "TST");
                $stmt = $conn->prepare("INSERT INTO $staffTable (staff_id, agency_id, staff_name, username, password) VALUES (?,?,?,?,?)");
                $stmt->bind_param("sssss", $staffId, $agencyId, $staffName, $username, $password);
                $stmt->execute();
                $stmt->close();

                $conn->commit();
                $message = "$agencyName registered as a new " . ($agencyType === 'IA' ? 'Intermediate Agency' : 'Transportation Agency') .
                            " (ID $agencyId). Staff login created — username <strong>$username</strong>.";
                $messageType = "success";
            } catch (Exception $e) {
                $conn->rollback();
                $message = "Failed to register agency: " . $e->getMessage();
                $messageType = "error";
            }
        }
    }
}

$iaList = $conn->query("SELECT a.agency_id, a.agency_name, a.location, s.username, s.staff_name
                         FROM agency a LEFT JOIN ia_staff s ON s.agency_id = a.agency_id
                         WHERE a.agency_type = 'IA' ORDER BY a.agency_id");
$taList = $conn->query("SELECT a.agency_id, a.agency_name, a.location, s.username, s.staff_name
                         FROM agency a LEFT JOIN ta_staff s ON s.agency_id = a.agency_id
                         WHERE a.agency_type = 'TA' ORDER BY a.agency_id");

require_once __DIR__ . "/../includes/header.php";
?>

<div class="card">
    <h1>Manage Agencies</h1>
    <p class="subtitle">Add a new Intermediate Agency or Transportation Agency and set up its staff login in one step. BDMS Admin credentials themselves stay fixed and aren't created here.</p>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="POST" data-validate>
        <label>Agency Type</label>
        <select name="agency_type" required>
            <option value="IA">Intermediate Agency (IA)</option>
            <option value="TA">Transportation Agency (TA)</option>
        </select>

        <label>Display Name</label>
        <input type="text" name="agency_name" placeholder="e.g. Bhaktapur Community IA" required>

        <label>Location</label>
        <input type="text" name="location" placeholder="e.g. Bhaktapur">

        <hr class="divider">

        <label>Staff Display Name <span class="tag">Login account</span></label>
        <input type="text" name="staff_name" placeholder="e.g. Bhaktapur Staff" required>

        <label>Username</label>
        <input type="text" name="username" placeholder="e.g. ia3" required>

        <label>Password</label>
        <input type="text" name="password" placeholder="Set an initial password" required>

        <button type="submit">Create Agency &amp; Staff Login</button>
    </form>
</div>

<div class="card">
    <h2>Intermediate Agencies</h2>
    <table>
        <tr><th>ID</th><th>Name</th><th>Location</th><th>Staff Login</th></tr>
        <?php while ($a = $iaList->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($a['agency_id']); ?></td>
            <td><?php echo htmlspecialchars($a['agency_name']); ?></td>
            <td><?php echo htmlspecialchars($a['location'] ?? '—'); ?></td>
            <td><?php echo $a['username'] ? htmlspecialchars($a['staff_name']) . " (" . htmlspecialchars($a['username']) . ")" : '<em>No staff account yet</em>'; ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

<div class="card">
    <h2>Transportation Agencies</h2>
    <table>
        <tr><th>ID</th><th>Name</th><th>Location</th><th>Staff Login</th></tr>
        <?php while ($a = $taList->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($a['agency_id']); ?></td>
            <td><?php echo htmlspecialchars($a['agency_name']); ?></td>
            <td><?php echo htmlspecialchars($a['location'] ?? '—'); ?></td>
            <td><?php echo $a['username'] ? htmlspecialchars($a['staff_name']) . " (" . htmlspecialchars($a['username']) . ")" : '<em>No staff account yet</em>'; ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

<p><a href="dashboard.php">&larr; Back to Dashboard</a></p>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
