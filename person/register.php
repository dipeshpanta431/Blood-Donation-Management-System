<?php
require_once __DIR__ . "/../includes/db_connect.php";
$pageTitle = "Register";

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name        = trim($_POST["name"] ?? "");
    $age         = intval($_POST["age"] ?? 0);
    $gender      = $_POST["gender"] ?? "";
    $bloodType   = $_POST["blood_type"] ?? "";
    $contactInfo = trim($_POST["contact_info"] ?? "");
    $agencyId    = $_POST["agency_id"] ?? "";
    $username    = trim($_POST["username"] ?? "");
    $password    = $_POST["password"] ?? "";

    if ($name === "" || $age < 1 || $gender === "" || $bloodType === "" || $contactInfo === "" || $agencyId === "" || $username === "" || $password === "") {
        $message = "Please fill in all fields.";
        $messageType = "error";
    } else {
        $check = $conn->prepare("SELECT person_id FROM person WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $exists = $check->get_result()->num_rows > 0;
        $check->close();

        if ($exists) {
            $message = "That username is already taken. Please choose another.";
            $messageType = "error";
        } else {
            $personId = generateId($conn, "person", "person_id", "PSN");
            $stmt = $conn->prepare("INSERT INTO person (person_id, name, age, gender, blood_type, contact_info, registered_ia, username, password)
                                     VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("ssissssss", $personId, $name, $age, $gender, $bloodType, $contactInfo, $agencyId, $username, $password);

            if ($stmt->execute()) {
                $_SESSION["person_id"] = $personId;
                $_SESSION["person_name"] = $name;
                $stmt->close();
                header("Location: dashboard.php");
                exit;
            } else {
                $message = "Registration failed: " . $conn->error;
                $messageType = "error";
                $stmt->close();
            }
        }
    }
}

$agencies = $conn->query("SELECT agency_id, agency_name FROM agency WHERE agency_type = 'IA'");

require_once __DIR__ . "/../includes/header.php";
?>

<div class="card login-box">
    <h1>Create Your Account</h1>
    <p class="subtitle">One account lets you both donate blood and request blood whenever you need to.</p>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="POST" data-validate>
        <label>Full Name</label>
        <input type="text" name="name" required>

        <label>Age</label>
        <input type="number" name="age" min="1" max="120" required>

        <label>Gender</label>
        <select name="gender" required>
            <option value="">-- Select --</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
            <option value="Other">Other</option>
        </select>

        <label>Blood Type</label>
        <select name="blood_type" required>
            <option value="">-- Select --</option>
            <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bt): ?>
                <option value="<?php echo $bt; ?>"><?php echo $bt; ?></option>
            <?php endforeach; ?>
        </select>

        <label>Contact Info</label>
        <input type="text" name="contact_info" placeholder="Phone number" required>

        <label>Nearest Intermediate Agency</label>
        <select name="agency_id" required>
            <option value="">-- Select IA --</option>
            <?php while ($a = $agencies->fetch_assoc()): ?>
                <option value="<?php echo $a['agency_id']; ?>"><?php echo htmlspecialchars($a['agency_name']); ?></option>
            <?php endwhile; ?>
        </select>

        <hr class="divider">

        <label>Username</label>
        <input type="text" name="username" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <button type="submit">Create Account</button>
    </form>
    <p class="hint">Already have an account? <a href="login.php">Log in</a></p>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
