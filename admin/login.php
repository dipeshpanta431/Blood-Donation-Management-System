<?php
require_once __DIR__ . "/../includes/db_connect.php";
$pageTitle = "Admin Login";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    $stmt = $conn->prepare("SELECT admin_id, admin_name, password FROM bdms_admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $admin = $res->fetch_assoc();
        if ($password === $admin["password"]) {
            resetSessionForLogin();
            $_SESSION["admin_id"] = $admin["admin_id"];
            $_SESSION["admin_name"] = $admin["admin_name"];
            header("Location: dashboard.php");
            exit;
        }
    }
    $error = "Invalid username or password.";
}

require_once __DIR__ . "/../includes/header.php";
?>

<div class="card login-box">
    <h1>BDMS Admin Login</h1>
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
    <p class="hint">Demo credentials: admin / admin123</p>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
