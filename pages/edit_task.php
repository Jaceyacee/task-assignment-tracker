
<?php

// Check whether the user is logged in
require_once __DIR__ . "/../includes/auth.php";
requireLogin();

// Connect to the database
require_once __DIR__ . "/../config/database.php";

// Generate CSRF token
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$error = "";
$task = null;

// Get the task ID from the URL or submitted form
$task_id = $_SERVER["REQUEST_METHOD"] === "POST"
    ? ($_POST["task_id"] ?? "")
    : ($_GET["task_id"] ?? "");

// Validate task ID
if (
    !filter_var($task_id, FILTER_VALIDATE_INT) ||
    (int) $task_id <= 0
) {

    $error = "Invalid assignment ID.";

} else {

    $task_id = (int) $task_id;
    $user_id = $_SESSION["user_id"];

    try {

        // Retrieve the assignment belonging to this user
        $stmt = $pdo->prepare(
            "SELECT * FROM tasks
             WHERE task_id = :task_id
             AND user_id = :user_id"
        );

        $stmt->execute([
            ":task_id" => $task_id,
            ":user_id" => $user_id
        ]);

        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$task) {

            $error = "Assignment not found or you do not have permission to edit it.";

        } elseif ($_SERVER["REQUEST_METHOD"] === "POST") {

            // Get submitted form values
            $title = trim($_POST["title"] ?? "");
            $description = trim($_POST["description"] ?? "");
            $deadline = trim($_POST["deadline"] ?? "");
            $status = $_POST["status"] ?? "";

            // Verify CSRF token
            $submitted_token = $_POST["csrf_token"] ?? "";

            if (
                !hash_equals($_SESSION["csrf_token"], $submitted_token)
            ) {

                $error = "Invalid form submission. Please try again.";

            } elseif ($title === "") {

                $error = "Assignment title is required.";

            } elseif (strlen($title) > 150) {

                $error = "Assignment title must not exceed 150 characters.";

            } elseif (!in_array($status, ["Pending", "In Progress", "Completed"], true)) {

                $error = "Invalid assignment status.";

            } elseif (
                $deadline !== "" &&
                !preg_match('/^\d{4}-\d{2}-\d{2}$/', $deadline)
            ) {

                $error = "Please enter a valid deadline.";

            } elseif (
                $deadline !== "" &&
                !checkdate(
                    (int) substr($deadline, 5, 2),
                    (int) substr($deadline, 8, 2),
                    (int) substr($deadline, 0, 4)
                )
            ) {

                $error = "Please enter a valid calendar date.";

            } else {

                // Update the existing assignment
                $update = $pdo->prepare(
                    "UPDATE tasks
                     SET title = :title,
                         description = :description,
                         deadline = :deadline,
                         status = :status
                     WHERE task_id = :task_id
                     AND user_id = :user_id"
                );

                $update->execute([
                    ":title" => $title,
                    ":description" => $description !== "" ? $description : null,
                    ":deadline" => $deadline !== "" ? $deadline : null,
                    ":status" => $status,
                    ":task_id" => $task_id,
                    ":user_id" => $user_id
                ]);

                // Generate a new CSRF token
                $_SESSION["csrf_token"] = bin2hex(random_bytes(32));

                // Return to dashboard
                header("Location: dashboard.php?success=task_updated");
                exit;

            }

            // Preserve submitted values if validation fails
            if ($error !== "") {
                $task["title"] = $title;
                $task["description"] = $description;
                $task["deadline"] = $deadline;
                $task["status"] = $status;
            }

        }

    } catch (PDOException $e) {

        error_log("Task update error: " . $e->getMessage());

        $error = "Unable to update your assignment. Please try again.";

    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Assignment - Task Tracker</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 30px;
        }

        .container {
            max-width: 600px;
            margin: 30px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        h1 {
            color: #333;
            margin-top: 0;
        }

        p {
            color: #666;
        }

        label {
            display: block;
            margin-top: 18px;
            margin-bottom: 7px;
            font-weight: bold;
        }

        input, textarea, select {
            width: 100%;
            padding: 12px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-family: Arial, sans-serif;
        }

        textarea {
            resize: vertical;
        }

        button {
            width: 100%;
            padding: 13px;
            margin-top: 25px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 15px;
        }

        button:hover {
            background: #1d4ed8;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .back {
            display: inline-block;
            margin-top: 20px;
            color: #2563eb;
            text-decoration: none;
        }

    </style>

</head>

<body>

<div class="container">

    <h1>Edit Assignment</h1>

    <p>Update the details of your assignment below.</p>

    <?php if ($error !== ""): ?>

        <div class="error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>

    <?php endif; ?>

    <?php if ($task): ?>

        <form method="POST" action="edit_task.php">

            <input
                type="hidden"
                name="task_id"
                value="<?= (int) $task["task_id"] ?>"
            >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, 'UTF-8') ?>"
            >

            <label>Assignment Title *</label>

            <input
                type="text"
                name="title"
                maxlength="150"
                value="<?= htmlspecialchars($task["title"], ENT_QUOTES, 'UTF-8') ?>"
                required
            >

            <label>Description</label>

            <textarea
                name="description"
                rows="4"
            ><?= htmlspecialchars($task["description"] ?? "", ENT_QUOTES, 'UTF-8') ?></textarea>

            <label>Deadline</label>

            <input
                type="date"
                name="deadline"
                value="<?= htmlspecialchars($task["deadline"] ?? "", ENT_QUOTES, 'UTF-8') ?>"
            >

            <label>Status</label>

            <select name="status">

                <option value="Pending"
                    <?= $task["status"] === "Pending" ? "selected" : "" ?>>
                    Pending
                </option>

                <option value="In Progress"
                    <?= $task["status"] === "In Progress" ? "selected" : "" ?>>
                    In Progress
                </option>

                <option value="Completed"
                    <?= $task["status"] === "Completed" ? "selected" : "" ?>>
                    Completed
                </option>

            </select>

            <button type="submit">
                Update Assignment
            </button>

        </form>

    <?php endif; ?>

    <a class="back" href="dashboard.php">
        ← Back to Dashboard
    </a>

</div>

</body>

</html>
