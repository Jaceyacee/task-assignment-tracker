
<?php

require_once __DIR__ . "/includes/auth.php";

// Remove all session variables
session_unset();

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit;

?>
