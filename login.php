
<?php

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/config/database.php";

$error = "";
$email = "";

// Redirect users who are already logged in
if (isLoggedIn()) {
    header("Location: pages/dashboard.php");
    exit;
}

// Process login form
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    // Check if fields are empty
    if ($email === "" || $password === "") {

        $error = "Please enter your email and password.";

    } else {

        try {

            // Find the user by email
            $stmt = $pdo->prepare(
                "SELECT user_id, name, email, password
                 FROM users
                 WHERE email = :email"
            );

            $stmt->execute([
                ":email" => $email
            ]);

            $user = $stmt->fetch();

            // Verify the password
            if ($user && password_verify($password, $user["password"])) {

                // Create a new session ID
                session_regenerate_id(true);

                // Store user information in the session
                $_SESSION["user_id"] = $user["user_id"];
                $_SESSION["user_name"] = $user["name"];
                $_SESSION["user_email"] = $user["email"];

                // Redirect to dashboard
                header("Location: pages/dashboard.php");
                exit;

            } else {

                $error = "Invalid email or password.";

            }

        } catch (PDOException $e) {

            error_log("Login error: " . $e->getMessage());

            $error = "Something went wrong. Please try again.";

        }

    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - Task Tracker</title>

    <style>

        
/* ==========================================
   PHASE 10.5: LOGIN PAGE DESIGN
========================================== */

* {
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    background: #f4f6f9;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    margin: 0;
    padding: 20px;
    color: #1f2937;
}

.container {
    background: white;
    width: 100%;
    max-width: 420px;
    padding: 35px;
    border-radius: 14px;
    box-shadow: 0 5px 25px rgba(0, 0, 0, 0.08);
}

h2 {
    text-align: center;
    color: #1e293b;
    font-size: 26px;
    margin: 0 0 10px;
}

.subtitle {
    text-align: center;
    color: #64748b;
    font-size: 14px;
    line-height: 1.5;
    margin: 0 0 25px;
}

label {
    display: block;
    font-size: 14px;
    font-weight: bold;
    color: #374151;
    margin-top: 18px;
    margin-bottom: 7px;
}

input {
    width: 100%;
    padding: 13px;
    border: 1px solid #d1d5db;
    border-radius: 7px;
    font-size: 14px;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
}

input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

button {
    width: 100%;
    padding: 13px;
    margin-top: 25px;
    background: #2563eb;
    color: white;
    border: none;
    border-radius: 7px;
    font-size: 15px;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.2s;
}

button:hover {
    background: #1d4ed8;
}

.error {
    background: #fee2e2;
    color: #991b1b;
    padding: 12px;
    margin-bottom: 15px;
    border-radius: 7px;
    font-size: 14px;
    line-height: 1.5;
}

.register-link {
    text-align: center;
    margin-top: 25px;
    font-size: 14px;
    color: #64748b;
}

.register-link a {
    color: #2563eb;
    font-weight: bold;
    text-decoration: none;
}

.register-link a:hover {
    text-decoration: underline;
}

/* MOBILE RESPONSIVENESS */

@media (max-width: 480px) {
    body {
        padding: 15px;
    }

    .container {
        padding: 25px 20px;
    }

    h2 {
        font-size: 23px;
    }
}



    </style>

</head>

<body>

<div class="container">

    <h2>Welcome Back!</h2>

<p class="subtitle">
    Sign in to continue managing your assignments.
</p>

    <?php if ($error !== ""): ?>

        <div class="error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>

    <?php endif; ?>

    <form method="POST" action="login.php">

        <label>Email Address</label>

       <input
    type="email"
    name="email"
    value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
    autocomplete="email"
    required
>

        <label>Password</label>

       <input
    type="password"
    name="password"
    autocomplete="current-password"
    required
>

        <button type="submit">
            Login
        </button>

    </form>

    <div class="register-link">
        Don't have an account?
        <a href="register.php">Register here</a>
    </div>

</div>

</body>
</html>
