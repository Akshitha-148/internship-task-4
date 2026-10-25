
<?php
session_start();
require_once "../config/database.php";

// Allow only logged-in admins
if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "admin"
) {
    header("Location: ../auth/login.php");
    exit;
}

// Get the user ID from the URL
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id === (int)$_SESSION["user_id"]) {
    header("Location: users.php");
    exit;
}

// Fetch the selected user
$stmt = $pdo->prepare(
    "SELECT id, name, email, role FROM users WHERE id = ?"
);
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: users.php");
    exit;
}

$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $role = $_POST["role"] ?? "";

    // Validate the name
    if ($name === "") {
        $errors[] = "Name is required.";
    }

    // Validate the email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    // Validate the role
    if (!in_array($role, ["user", "admin"], true)) {
        $errors[] = "Please select a valid role.";
    }

    // Check whether another user already has this email
    if (!$errors) {
        $check = $pdo->prepare(
            "SELECT id FROM users WHERE email = ? AND id <> ?"
        );
        $check->execute([$email, $id]);

        if ($check->fetch()) {
            $errors[] = "This email address is already registered.";
        }
    }

    // Prevent demoting the last administrator
    if (
        !$errors &&
        $user["role"] === "admin" &&
        $role !== "admin"
    ) {
        $checkAdmin = $pdo->query(
            "SELECT COUNT(*) FROM users WHERE role = 'admin'"
        );

        if ((int)$checkAdmin->fetchColumn() <= 1) {
            $errors[] = "You cannot remove the last admin account.";
        }
    }

    // Save changes
    if (!$errors) {
        $update = $pdo->prepare(
            "UPDATE users
             SET name = ?, email = ?, role = ?
             WHERE id = ?"
        );

        $update->execute([$name, $email, $role, $id]);

        header("Location: users.php?updated=1");
        exit;
    }

    // Preserve the entered values if validation fails
    $user["name"] = $name;
    $user["email"] = $email;
    $user["role"] = $role;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Edit User | BookNest</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="bg-light">

<div class="container py-5" style="max-width: 650px;">

    <div class="card shadow-sm">
        <div class="card-body p-4">

            <h2 class="mb-4">Edit User</h2>

            <?php foreach ($errors as $error): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endforeach; ?>

            <form method="POST">

                <div class="mb-3">
                    <label for="name" class="form-label">
                        Full Name
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        class="form-control"
                        value="<?= htmlspecialchars($user["name"]) ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        value="<?= htmlspecialchars($user["email"]) ?>"
                        required
                    >
                </div>

                <div class="mb-4">
                    <label for="role" class="form-label">
                        Role
                    </label>

                    <select
                        id="role"
                        name="role"
                        class="form-select"
                        required
                    >
                        <option value="user"
                            <?= $user["role"] === "user" ? "selected" : "" ?>>
                            User
                        </option>

                        <option value="admin"
                            <?= $user["role"] === "admin" ? "selected" : "" ?>>
                            Admin
                        </option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">
                    Save Changes
                </button>

                <a href="users.php" class="btn btn-secondary">
                    Cancel
                </a>

            </form>

        </div>
    </div>

</div>

</body>
</html>
