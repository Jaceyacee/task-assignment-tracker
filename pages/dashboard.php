
<?php

// Check whether the user is logged in
require_once __DIR__ . "/../includes/auth.php";
requireLogin();

// Generate CSRF token if one does not exist
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Connect to the database
require_once __DIR__ . "/../config/database.php";

// Get the logged-in user's ID
$user_id = $_SESSION["user_id"];


// ==========================================
// PHASE 8: DASHBOARD STATISTICS
// ==========================================

$stats_stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total,
        
        COALESCE(SUM(
            CASE WHEN status = 'Pending' THEN 1 ELSE 0 END
        ), 0) AS pending,

        COALESCE(SUM(
            CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END
        ), 0) AS in_progress,

        COALESCE(SUM(
            CASE WHEN status = 'Completed' THEN 1 ELSE 0 END
        ), 0) AS completed

    FROM tasks
    WHERE user_id = :user_id
");

$stats_stmt->execute([
    ":user_id" => $user_id
]);

$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);


// Retrieve this user's assignments
try {

    
// ==========================================
// PHASE 9: SEARCH AND FILTERING
// ==========================================

// Get search keyword from the URL
$search = trim($_GET["search"] ?? "");

// Get selected status from the URL
$status_filter = $_GET["status"] ?? "";

// Start building the SQL query
$sql = "
    SELECT *
    FROM tasks
    WHERE user_id = :user_id
";

// Store SQL parameters
$params = [
    ":user_id" => $user_id
];

// Add search condition if a keyword was entered
if ($search !== "") {

    $sql .= " AND title LIKE :search";

    $params[":search"] = "%" . $search . "%";

}

// Add status condition if a filter was selected
$allowed_statuses = [
    "Pending",
    "In Progress",
    "Completed"
];

if (in_array($status_filter, $allowed_statuses, true)) {

    $sql .= " AND status = :status";

    $params[":status"] = $status_filter;

} else {

    // Invalid or empty status means show all statuses
    $status_filter = "";

}

// Arrange assignments by newest first
$sql .= " ORDER BY created_at DESC";

// Prepare and execute the query
$stmt = $pdo->prepare($sql);

$stmt->execute($params);

// Retrieve matching assignments
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);


} catch (PDOException $e) {

    error_log("Task retrieval error: " . $e->getMessage());

    $tasks = [];
    $error = "Unable to load your assignments.";

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Dashboard - StudyDash</title>

    <style>


/* ==========================================
   PHASE 10.1: IMPROVED DASHBOARD LAYOUT
========================================== */

body {
    font-family: Arial, sans-serif;
    background: #f1f5f9;
    margin: 0;
    padding: 30px;
    color: #1e293b;
}

.container {
    max-width: 1150px;
    margin: 0 auto;
}

.header {
    background: white;
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 25px;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
}

.header h1 {
    color: #1e3a8a;
    margin-top: 0;
    margin-bottom: 15px;
}

.header h3 {
    color: #334155;
    margin-bottom: 8px;
}

.header p {
    color: #64748b;
}


/* ==========================================
   PHASE 10.3: ASSIGNMENT CARD DESIGN
========================================== */

.task-card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 20px;
    border: 1px solid #e2e8f0;
    border-left: 5px solid #2563eb;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.04);
    transition: transform 0.2s, box-shadow 0.2s;
}

.task-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 7px 18px rgba(0, 0, 0, 0.09);
}

.task-card h3 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #1e3a8a;
    font-size: 21px;
}

.task-card p {
    line-height: 1.6;
    color: #475569;
}

.task-card .description {
    color: #64748b;
    margin-bottom: 18px;
    white-space: normal;
}


.empty,
.no-results {
    background: white;
    padding: 35px;
    margin-top: 20px;
    border-radius: 12px;
    text-align: center;
    color: #64748b;
    border: 1px solid #e2e8f0;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.04);
}

.empty h3,
.no-results h3 {
    color: #1e293b;
}


      
/* ==========================================
   PHASE 10.3: ASSIGNMENT STATUS BADGES
========================================== */

.status {
    display: inline-flex;
    align-items: center;
    padding: 7px 14px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: bold;
    letter-spacing: 0.3px;
}

/* Pending */

.pending {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fde68a;
}

/* In Progress */

.in-progress {
    background: #dbeafe;
    color: #1e40af;
    border: 1px solid #bfdbfe;
}

/* Completed */

.completed {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}


        .logout {
            color: #dc3545;
            text-decoration: none;
            font-weight: bold;
        }

        .empty {
            background: white;
            padding: 30px;
            text-align: center;
            border-radius: 8px;
        }

        .description {
            color: #666;
        }

    
/* ==========================================
   PHASE 8: DASHBOARD STATISTICS CARDS
========================================== */

.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin: 25px 0;
}

.stat-card {
    background: white;
    padding: 22px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    border-left: 5px solid #2563eb;
}

.stat-card h3 {
    color: #666;
    font-size: 14px;
    margin: 0 0 12px;
}

.stat-card .stat-number {
    font-size: 30px;
    font-weight: bold;
    color: #333;
    margin: 0;
}

.stat-total {
    border-left-color: #2563eb;
}

.stat-pending {
    border-left-color: #eab308;
}

.stat-progress {
    border-left-color: #3b82f6;
}

.stat-completed {
    border-left-color: #22c55e;
}

/* Responsive layout for smaller screens */

@media (max-width: 768px) {

    .dashboard-stats {
        grid-template-columns: repeat(2, 1fr);
    }

}

@media (max-width: 480px) {

    .dashboard-stats {
        grid-template-columns: 1fr;
    }

}

    

/* ==========================================
   PHASE 9: SEARCH AND FILTER DESIGN
========================================== */

.search-filter-form {
    display: flex;
    flex-wrap: wrap;
    align-items: end;
    gap: 15px;
    background: white;
    padding: 20px;
    margin: 20px 0;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.filter-group {
    flex: 1;
    min-width: 180px;
}

.filter-group label {
    display: block;
    font-weight: bold;
    margin-bottom: 8px;
    color: #333;
}

.filter-group input,
.filter-group select {
    width: 100%;
    padding: 11px;
    box-sizing: border-box;
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 14px;
}

.filter-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

.search-button {
    background: #2563eb;
    color: white;
    padding: 11px 16px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
}

.search-button:hover {
    background: #1d4ed8;
}

.reset-button {
    display: inline-block;
    background: #e5e7eb;
    color: #333;
    padding: 11px 16px;
    border-radius: 5px;
    text-decoration: none;
    font-size: 14px;
}

.reset-button:hover {
    background: #d1d5db;
}

/* Mobile responsiveness */

@media (max-width: 600px) {

    .search-filter-form {
        flex-direction: column;
        align-items: stretch;
    }

    .filter-group {
        width: 100%;
    }

    .filter-actions {
        flex-direction: column;
        width: 100%;
    }

    .search-button,
    .reset-button {
        width: 100%;
        text-align: center;
        box-sizing: border-box;
    }

}


    .no-results {
    background: white;
    padding: 30px;
    margin-top: 20px;
    border-radius: 10px;
    text-align: center;
    color: #666;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.no-results h3 {
    color: #333;
    margin-bottom: 10px;
}
    

/* ==========================================
   PHASE 10.1: RESPONSIVE DASHBOARD
========================================== */

@media (max-width: 768px) {

    body {
        padding: 18px;
    }

    .container {
        width: 100%;
    }

    .header {
        padding: 22px;
    }

    .task-card {
        padding: 20px;
    }

}

@media (max-width: 480px) {

    body {
        padding: 12px;
    }

    .header {
        padding: 18px;
    }

    .header h1 {
        font-size: 24px;
    }

    .task-card {
        padding: 16px;
    }

    .task-card h3 {
        font-size: 18px;
    }

}


/* ==========================================
   PHASE 10.2: NAVIGATION BAR DESIGN
========================================== */

.navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #1e3a8a;
    padding: 15px 25px;
    border-radius: 12px;
    margin-bottom: 25px;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
}

/* Application Name */

.nav-brand h2 {
    color: white;
    margin: 0;
    font-size: 23px;
}

/* Navigation Links Container */

.nav-links {
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Individual Navigation Links */

.nav-link {
    color: white;
    text-decoration: none;
    padding: 10px 15px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: bold;
    transition: background 0.2s;
}

.nav-link:hover {
    background: #3b5ba5;
}

/* Active Navigation Link */

.nav-link.active {
    background: #3b5ba5;
}

/* Logout Link */

.logout-link {
    background: #dc2626;
}

.logout-link:hover {
    background: #b91c1c;
}

/* Welcome Section */

.welcome-section {
    background: white;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 25px;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.04);
}

.welcome-section h1 {
    color: #1e3a8a;
    margin-top: 0;
}

.welcome-section p {
    color: #64748b;
    margin-bottom: 0;
}

/* Responsive Navigation */

@media (max-width: 600px) {

    .navbar {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
        padding: 20px;
    }

    .nav-links {
        width: 100%;
        flex-wrap: wrap;
    }

    .nav-link {
        text-align: center;
    }

}


/* ==========================================
   PHASE 10.3: ASSIGNMENT ACTION BUTTONS
========================================== */

.task-button {
    display: inline-block;
    padding: 10px 16px;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    font-size: 13px;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.2s, transform 0.2s;
    margin-top: 10px;
}

.task-button:hover {
    transform: translateY(-2px);
}

.edit-button {
    background: #2563eb;
    color: white;
}

.edit-button:hover {
    background: #1d4ed8;
}

.delete-button {
    background: #dc2626;
    color: white;
}

.delete-button:hover {
    background: #b91c1c;
}


/* ==========================================
   PHASE 10.4: COMPLETE RESPONSIVE DESIGN
========================================== */

/* TABLET AND SMALLER SCREENS */

@media (max-width: 768px) {

    body {
        padding: 18px;
    }

    .container {
        width: 100%;
        max-width: 100%;
    }

    /* Statistics */

    .dashboard-stats {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }

    .stat-card {
        padding: 18px;
    }

    /* Assignment cards */

    .task-card {
        padding: 20px;
    }

    /* Navigation */

    .navbar {
        flex-wrap: wrap;
    }

}

/* MOBILE PHONES */

@media (max-width: 600px) {

    body {
        padding: 12px;
    }

    /* Navigation */

    .navbar {
        flex-direction: column;
        align-items: stretch;
        padding: 18px;
        gap: 15px;
    }

    .nav-brand {
        text-align: center;
    }

    .nav-links {
        display: flex;
        flex-direction: column;
        width: 100%;
        gap: 8px;
    }

    .nav-link {
        display: block;
        width: 100%;
        box-sizing: border-box;
        text-align: center;
    }

    /* Welcome section */

    .welcome-section {
        padding: 18px;
    }

    .welcome-section h1 {
        font-size: 23px;
    }

    /* Statistics */

    .dashboard-stats {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }

    .stat-card {
        padding: 15px;
    }

    .stat-card h3 {
        font-size: 12px;
    }

    .stat-card .stat-number {
        font-size: 24px;
    }

    /* Search and filtering */

    .search-filter-form {
        flex-direction: column;
        align-items: stretch;
        padding: 16px;
    }

    .filter-group {
        width: 100%;
        min-width: 0;
    }

    .filter-actions {
        flex-direction: column;
        width: 100%;
    }

    .search-button,
    .reset-button {
        width: 100%;
        box-sizing: border-box;
        text-align: center;
    }

    /* Assignment cards */

    .task-card {
        padding: 17px;
    }

    .task-card h3 {
        font-size: 18px;
        overflow-wrap: anywhere;
    }

    /* Assignment buttons */

    .task-card .edit-button,
    .task-card .delete-button {
        display: block;
        width: 100%;
        box-sizing: border-box;
        text-align: center;
        margin: 8px 0 0;
    }

    .task-card .delete-form {
        display: block;
        width: 100%;
        margin-top: 8px;
    }

}

/* VERY SMALL MOBILE SCREENS */

@media (max-width: 380px) {

    body {
        padding: 8px;
    }

    .dashboard-stats {
        grid-template-columns: 1fr;
    }

    .header,
    .welcome-section {
        padding: 15px;
    }

    .stat-card .stat-number {
        font-size: 22px;
    }

}



    </style>

</head>

<body>

<div class="container">

 
<!-- ==========================================
     PHASE 10.2: NAVIGATION BAR
========================================== -->

<nav class="navbar">

    <!-- Application Logo / Name -->
    <div class="nav-brand">
        <h2>StudyDash</h2>
    </div>

    <!-- Navigation Links -->
    <div class="nav-links">

        <a href="dashboard.php" class="nav-link active">
            Dashboard
        </a>

        <a href="add_task.php" class="nav-link">
            Add Assignment
        </a>

        <a href="../logout.php" class="nav-link logout-link">
            Logout
        </a>

    </div>

</nav>

<!-- Welcome Section -->
<div class="welcome-section">

    <h1>
        Welcome,
        <?= htmlspecialchars($_SESSION["user_name"], ENT_QUOTES, 'UTF-8') ?>!
    </h1>

    <p>
        Manage your assignments and keep track of your progress.
    </p>

</div>



<!-- ==========================================
     PHASE 8: DASHBOARD STATISTICS DISPLAY
========================================== -->

<div class="dashboard-stats">

    <!-- TOTAL ASSIGNMENTS -->
    <div class="stat-card stat-total">

        <h3>Total Assignments</h3>

        <p class="stat-number">
            <?= number_format((int) $stats["total"]) ?>
        </p>

    </div>


    <!-- PENDING ASSIGNMENTS -->
    <div class="stat-card stat-pending">

        <h3>Pending</h3>

        <p class="stat-number">
            <?= number_format((int) $stats["pending"]) ?>
        </p>

    </div>


    <!-- IN PROGRESS ASSIGNMENTS -->
    <div class="stat-card stat-progress">

        <h3>In Progress</h3>

        <p class="stat-number">
            <?= number_format((int) $stats["in_progress"]) ?>
        </p>

    </div>


    <!-- COMPLETED ASSIGNMENTS -->
    <div class="stat-card stat-completed">

        <h3>Completed</h3>

        <p class="stat-number">
            <?= number_format((int) $stats["completed"]) ?>
        </p>

    </div>

</div>


    <h2>My Assignments</h2>


<!-- ==========================================
     PHASE 9: SEARCH AND FILTER FORM
========================================== -->

<form method="GET" action="dashboard.php" class="search-filter-form">

    <!-- Search by assignment title -->
    <div class="filter-group">

        <label for="search">Search Assignments</label>

        <input
            type="text"
            id="search"
            name="search"
            placeholder="Enter assignment title..."
            value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
        >

    </div>


    <!-- Filter by assignment status -->
    <div class="filter-group">

        <label for="status">Filter by Status</label>

        <select name="status" id="status">

            <option value="" <?= $status_filter === "" ? "selected" : "" ?>>
                All Statuses
            </option>

            <option value="Pending" <?= $status_filter === "Pending" ? "selected" : "" ?>>
                Pending
            </option>

            <option value="In Progress" <?= $status_filter === "In Progress" ? "selected" : "" ?>>
                In Progress
            </option>

            <option value="Completed" <?= $status_filter === "Completed" ? "selected" : "" ?>>
                Completed
            </option>

        </select>

    </div>


    <!-- Search button -->
    <div class="filter-actions">

        <button type="submit" class="search-button">
            Search Assignments
        </button>

        <a href="dashboard.php" class="reset-button">
            Clear Filters
        </a>

    </div>

</form>



<?php if (isset($_GET["success"]) && $_GET["success"] === "task_added"): ?>

    <div style="
        background: #d4edda;
        color: #155724;
        padding: 12px;
        border-radius: 5px;
        margin-bottom: 20px;
    ">
        Assignment added successfully!
    </div>

<?php elseif (isset($_GET["success"]) && $_GET["success"] === "task_updated"): ?>

    <div style="
        background: #d4edda;
        color: #155724;
        padding: 12px;
        border-radius: 5px;
        margin-bottom: 20px;
    ">
        Assignment updated successfully!
    </div>

<?php elseif (isset($_GET["success"]) && $_GET["success"] === "task_deleted"): ?>

    <div style="
        background: #d4edda;
        color: #155724;
        padding: 12px;
        border-radius: 5px;
        margin-bottom: 20px;
    ">
        Assignment deleted successfully!
    </div>

<?php elseif (isset($_GET["error"])): ?>

    <div style="
        background: #fee2e2;
        color: #991b1b;
        padding: 12px;
        border-radius: 5px;
        margin-bottom: 20px;
    ">

        <?php
        $messages = [
            "invalid_task" => "Invalid assignment ID.",
            "invalid_request" => "Invalid request. Please try again.",
            "task_not_found" => "Assignment not found or you do not have permission to delete it.",
            "delete_failed" => "Unable to delete the assignment. Please try again."
        ];

        $error_code = $_GET["error"];

        echo htmlspecialchars(
            $messages[$error_code] ?? "Something went wrong.",
            ENT_QUOTES,
            "UTF-8"
        );
        ?>

    </div>

<?php endif; ?>


    
<?php if (isset($error)): ?>

    <p>
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </p>

<?php elseif (empty($tasks)): ?>

    <?php if ($search !== "" || $status_filter !== ""): ?>

        <div class="no-results">
            <h3>No assignments found</h3>
            <p>
                Try another search keyword or change your status filter.
            </p>
        </div>

    <?php else: ?>

        <div class="empty">
            <h3>No assignments yet.</h3>
            <p>
                Your assignments will appear here once you add them.
            </p>
        </div>

    <?php endif; ?>

<?php else: ?>

    <?php foreach ($tasks as $task): ?>

        <div class="task-card">

            <h3>
                <?= htmlspecialchars($task["title"], ENT_QUOTES, 'UTF-8') ?>
            </h3>

            <p class="description">
                <?= nl2br(htmlspecialchars($task["description"] ?? "", ENT_QUOTES, 'UTF-8')) ?>
            </p>

            <p>
                <strong>Deadline:</strong>

                <?= $task["deadline"]
                    ? htmlspecialchars(date("F d, Y", strtotime($task["deadline"])), ENT_QUOTES, 'UTF-8')
                    : "No deadline set" ?>
            </p>

            <?php
                $status_class = strtolower(
                    str_replace(" ", "-", $task["status"])
                );
            ?>

            <p>
                <strong>Status:</strong>

                <span class="status <?= htmlspecialchars($status_class, ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($task["status"], ENT_QUOTES, 'UTF-8') ?>
                </span>
            </p>

          <a
    href="edit_task.php?task_id=<?= (int) $task['task_id'] ?>"
    class="task-button edit-button"
>
    Edit Assignment
</a>

            <form method="POST"
      action="delete_task.php"
      class="delete-form"
      onsubmit="return confirm('Are you sure you want to delete this assignment? This action cannot be undone.');">

                <input
                    type="hidden"
                    name="task_id"
                    value="<?= (int) $task['task_id'] ?>"
                >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"
                >

             <button
    type="submit"
    class="task-button delete-button"
>
    Delete Assignment
</button>

            </form>

        </div>

    <?php endforeach; ?>

<?php endif; ?>


</div>

</body>

</html>
