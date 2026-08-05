<?php
require_once __DIR__ . "/../includes/db_connect.php";
$pageTitle = "Donor / Receiver Login";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    $stmt = $conn->prepare("SELECT person_id, name, password FROM person WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $person = $res->fetch_assoc();
        if ($password === $person["password"]) {
            resetSessionForLogin();
            $_SESSION["person_id"] = $person["person_id"];
            $_SESSION["person_name"] = $person["name"];
            header("Location: dashboard.php");
            exit;
        }
    }
    $error = "Invalid username or password.";
}

require_once __DIR__ . "/../includes/header.php";
?>

<div class="card login-box">
    <h1>Donor / Receiver Login</h1>
    <p class="subtitle">One account for both donating and requesting blood.</p>
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
    <p class="hint">New here? <a href="register.php">Create an account</a></p>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
