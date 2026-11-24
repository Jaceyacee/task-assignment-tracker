
<?php

// Start the session if one is not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check whether the user is logged in
function isLoggedIn() {
    return isset($_SESSION["user_id"]);
}

// Protect pages that require login
function requireLogin() {

    if (!isLoggedIn()) {
        header("Location: /task_tracker/login.php");
        exit;
    }

}

?>
