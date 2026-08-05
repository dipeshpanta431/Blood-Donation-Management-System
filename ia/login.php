<?php
require_once __DIR__ . "/../includes/db_connect.php";
$pageTitle = "IA Staff Login";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    $stmt = $conn->prepare("SELECT s.staff_id, s.staff_name, s.password, s.agency_id, a.agency_name
                             FROM ia_staff s JOIN agency a ON s.agency_id = a.agency_id
                             WHERE s.username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $staff = $res->fetch_assoc();
        if ($password === $staff["password"]) {
            resetSessionForLogin();
            $_SESSION["ia_staff_id"] = $staff["staff_id"];
            $_SESSION["ia_staff_name"] = $staff["staff_name"];
            $_SESSION["ia_agency_id"] = $staff["agency_id"];
            $_SESSION["ia_agency_name"] = $staff["agency_name"];
            header("Location: dashboard.php");
            exit;
        }
    }
    $error = "Invalid username or password.";
}

require_once __DIR__ . "/../includes/header.php";
?>

<div class="card login-box">
    <h1>Intermediate Agency Staff Login</h1>
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    <form method="POST" data-validate>
        <label>Username</label>
        <input type="text" name="username" required>
        <label>Password</label>
        <input type="password" name="password" required>
        <button type="submit">Log In</button>
    </form>
    <p class="hint">Demo credentials: ia1 / ia123 (City Central) or ia2 / ia123 (Lalitpur)</p>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
