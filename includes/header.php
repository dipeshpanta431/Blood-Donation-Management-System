<?php
// This file is always included from a page one folder below bdms/ root
// (donor/, receiver/, ia/, admin/, transport/, person/), so "../" reaches the root.
if (!isset($pageTitle)) { $pageTitle = "Blood Donation Management System"; }
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$isAdmin  = !empty($_SESSION["admin_id"]);
$isIa     = !empty($_SESSION["ia_staff_id"]);
$isTa     = !empty($_SESSION["ta_staff_id"]);
$isPerson = !empty($_SESSION["person_id"]);
$loggedIn = $isAdmin || $isIa || $isTa || $isPerson;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $pageTitle; ?> | BDMS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<header class="site-header">
    <div class="logo"><a href="../index.php" style="color:#fff;text-decoration:none;">🩸 BDMS</a></div>
    <nav>
        <?php if ($loggedIn): ?>
            <a href="../index.php">🏠 Return to Home</a>
            <?php if ($isAdmin): ?>
                <a href="../admin/dashboard.php">Dashboard</a>
                <a href="../admin/manage_agencies.php">Agencies</a>
                <a href="../admin/people.php">People</a>
                <a href="../admin/reports.php">Reports</a>
                <span class="nav-user">Admin: <?php echo htmlspecialchars($_SESSION["admin_name"]); ?></span>
                <a href="../admin/logout.php">Logout</a>
            <?php elseif ($isIa): ?>
                <a href="../ia/dashboard.php">Dashboard</a>
                <span class="nav-user">IA: <?php echo htmlspecialchars($_SESSION["ia_staff_name"]); ?></span>
                <a href="../ia/logout.php">Logout</a>
            <?php elseif ($isTa): ?>
                <a href="../transport/dashboard.php">Dashboard</a>
                <span class="nav-user">Transport: <?php echo htmlspecialchars($_SESSION["ta_staff_name"]); ?></span>
                <a href="../transport/logout.php">Logout</a>
            <?php elseif ($isPerson): ?>
                <a href="../person/dashboard.php">My Account</a>
                <span class="nav-user"><?php echo htmlspecialchars($_SESSION["person_name"]); ?></span>
                <a href="../person/logout.php">Logout</a>
            <?php endif; ?>
        <?php else: ?>
            <a href="../index.php">Home</a>
            <!-- <a href="../person/login.php">Donor / Receiver</a> -->
            <!-- <a href="../ia/login.php">IA Staff</a>
            <a href="../transport/login.php">Transport</a>
            <a href="../admin/login.php">Admin</a> -->
        <?php endif; ?>
    </nav>
</header>
<main class="container">
