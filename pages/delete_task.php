
<?php

// Check whether the user is logged in
require_once __DIR__ . "/../includes/auth.php";
requireLogin();

// Connect to the database
require_once __DIR__ . "/../config/database.php";

// Only allow POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: dashboard.php");
    exit;
}

// Get the submitted assignment ID
$task_id = $_POST["task_id"] ?? "";

// Validate assignment ID
if (
    !filter_var($task_id, FILTER_VALIDATE_INT) ||
    (int) $task_id <= 0
) {
    header("Location: dashboard.php?error=invalid_task");
    exit;
}

$task_id = (int) $task_id;
$user_id = $_SESSION["user_id"];

// Verify CSRF token
$submitted_token = $_POST["csrf_token"] ?? "";
$session_token = $_SESSION["csrf_token"] ?? "";

if (
    $session_token === "" ||
    !hash_equals($session_token, $submitted_token)
) {
    header("Location: dashboard.php?error=invalid_request");
    exit;
}

try {

    // Delete only the assignment belonging to the logged-in user
    $stmt = $pdo->prepare(
        "DELETE FROM tasks
         WHERE task_id = :task_id
         AND user_id = :user_id"
    );

    $stmt->execute([
        ":task_id" => $task_id,
        ":user_id" => $user_id
    ]);

    // Check whether an assignment was actually deleted
    if ($stmt->rowCount() > 0) {

        header("Location: dashboard.php?success=task_deleted");
        exit;

    } else {

        header("Location: dashboard.php?error=task_not_found");
        exit;

    }

} catch (PDOException $e) {

    // Record the actual error in the PHP error log
    error_log("Task deletion error: " . $e->getMessage());

    header("Location: dashboard.php?error=delete_failed");
    exit;

}

?>
