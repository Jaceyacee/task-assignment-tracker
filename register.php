
<?php

require_once __DIR__ . "/config/database.php";

$errors = [];
$success = false;

$name = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Get the information submitted by the user
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    // Validate full name
    if ($name === "") {
        $errors[] = "Full name is required.";
    } elseif (strlen($name) > 100) {
        $errors[] = "Full name must not exceed 100 characters.";
    }

    // Validate email
    if ($email === "") {
        $errors[] = "Email address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    } elseif (strlen($email) > 150) {
        $errors[] = "Email address is too long.";
    }

    // Validate password
    if (strlen($password) < 8) {
        $errors[] = "Password must contain at least 8 characters.";
    }

    // Confirm password
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // Continue if there are no validation errors
    if (empty($errors)) {

        try {

            // Check if email already exists
            $checkEmail = $pdo->prepare(
                "SELECT user_id FROM users WHERE email = :email"
            );

            $checkEmail->execute([
                ":email" => $email
            ]);

            if ($checkEmail->fetch()) {

                $errors[] = "This email address is already registered.";

            } else {

                // Hash the password before saving
                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                // Insert the new user into the database
                $insertUser = $pdo->prepare(
                    "INSERT INTO users (name, email, password)
                     VALUES (:name, :email, :password)"
                );

                $insertUser->execute([
                    ":name" => $name,
                    ":email" => $email,
                    ":password" => $hashedPassword
                ]);

                $success = true;

                // Clear the form fields
                $name = "";
                $email = "";
            }

        } catch (PDOException $e) {

            error_log("Registration error: " . $e->getMessage());

            if ($e->getCode() === "23000") {
                $errors[] = "This email address is already registered.";
            } else {
                $errors[] = "Something went wrong. Please try again.";
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Register - Task Tracker</title>

    <style>
       
/* ==========================================
   PHASE 10.5: REGISTRATION PAGE DESIGN
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
    max-width: 450px;
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
    margin-bottom: 10px;
    border-radius: 7px;
    font-size: 14px;
    line-height: 1.5;
}

.success {
    background: #dcfce7;
    color: #166534;
    padding: 12px;
    margin-bottom: 15px;
    border-radius: 7px;
    font-size: 14px;
    line-height: 1.5;
}

.login-link {
    text-align: center;
    margin-top: 25px;
    font-size: 14px;
    color: #64748b;
}

.login-link a {
    color: #2563eb;
    font-weight: bold;
    text-decoration: none;
}

.login-link a:hover {
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

    <h2>Create Your Account</h2>

<p class="subtitle">
    Register to start organizing your tasks and assignments.
</p>

    <?php if ($success): ?>

        <div class="success">
            Registration successful! Your account has been created.
        </div>

    <?php endif; ?>

    <?php foreach ($errors as $error): ?>

        <div class="error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>

    <?php endforeach; ?>

    <form method="POST" action="register.php">

        <label>Full Name</label>
        <input
            type="text"
            name="name"
            maxlength="100"
            value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
            required
            autocomplete="name"
            
        >

        <label>Email Address</label>
        <input
            type="email"
            name="email"
            maxlength="150"
            value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
            required
            autocomplete="email"
        >

        <label>Password</label>
        <input
            type="password"
            name="password"
            minlength="8"
            required
            autocomplete="new-password"
        >

        <label>Confirm Password</label>
        <input
            type="password"
            name="confirm_password"
            minlength="8"
            required
            autocomplete="new-password"
        >

        <button type="submit">
            Register
        </button>

    </form>

    <div class="login-link">
    Already have an account?
    <a href="login.php">Login here</a>
</div>

</div>

</body>
</html>
