<?php
require_once __DIR__ . "/../includes/db_connect.php";
destroySession();
header("Location: login.php");
exit;
