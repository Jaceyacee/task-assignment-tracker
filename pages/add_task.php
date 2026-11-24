
<?php

// Check whether the user is logged in
require_once __DIR__ . "/../includes/auth.php";
requireLogin();

// Connect to the database
require_once __DIR__ . "/../config/database.php";

// Create a CSRF token for form security
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$error = "";
$title = "";
$description = "";
$deadline = "";
$status = "Pending";

// Process the submitted form
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Get form values
    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $deadline = trim($_POST["deadline"] ?? "");
    $status = $_POST["status"] ?? "Pending";

    // Verify the CSRF token
    $submitted_token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_token"], $submitted_token)) {

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

        try {

            // Get the ID of the logged-in user
            $user_id = $_SESSION["user_id"];

            // Insert the assignment into the database
            $stmt = $pdo->prepare(
                "INSERT INTO tasks
                (user_id, title, description, deadline, status)
                VALUES
                (:user_id, :title, :description, :deadline, :status)"
            );

            $stmt->execute([
                ":user_id" => $user_id,
                ":title" => $title,
                ":description" => $description !== "" ? $description : null,
                ":deadline" => $deadline !== "" ? $deadline : null,
                ":status" => $status
            ]);

            // Generate a new token after successful submission
            $_SESSION["csrf_token"] = bin2hex(random_bytes(32));

            // Redirect to dashboard
            header("Location: dashboard.php?success=task_added");
            exit;

        } catch (PDOException $e) {

            error_log("Task creation error: " . $e->getMessage());

            $error = "Unable to save your assignment. Please try again.";

        }

    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Assignment - Task Tracker</title>

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

    <h1>Add New Assignment</h1>

    <p>Enter the details of your new assignment below.</p>

    <?php if ($error !== ""): ?>

        <div class="error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>

    <?php endif; ?>

    <form method="POST" action="add_task.php">

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
            value="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>"
            placeholder="Enter assignment title"
            required
        >

        <label>Description</label>

        <textarea
            name="description"
            rows="4"
            placeholder="Enter assignment details"
        ><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></textarea>

        <label>Deadline</label>

        <input
            type="date"
            name="deadline"
            value="<?= htmlspecialchars($deadline, ENT_QUOTES, 'UTF-8') ?>"
        >

        <label>Status</label>

        <select name="status">

            <option value="Pending"
                <?= $status === "Pending" ? "selected" : "" ?>>
                Pending
            </option>

            <option value="In Progress"
                <?= $status === "In Progress" ? "selected" : "" ?>>
                In Progress
            </option>

            <option value="Completed"
                <?= $status === "Completed" ? "selected" : "" ?>>
                Completed
            </option>

        </select>

        <button type="submit">
            Save Assignment
        </button>

    </form>

    <a class="back" href="dashboard.php">
        ← Back to Dashboard
    </a>

</div>

</body>

</html>
