
<?php
session_start();
require_once "../config/database.php";

// Only admins can access this page
if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "admin"
) {
    header("Location: ../auth/login.php");
    exit;
}

$message = "";

// Display success message after editing a user
if (isset($_GET["updated"]) && $_GET["updated"] === "1") {
    $message = "User details updated successfully.";
}

// Delete a user using POST
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["action"] ?? "") === "delete"
) {
    $deleteId = (int)($_POST["user_id"] ?? 0);

    if ($deleteId === (int)$_SESSION["user_id"]) {
        $message = "You cannot delete your own admin account.";
    } elseif ($deleteId <= 0) {
        $message = "Invalid user selected.";
    } else {
        // Fetch the user before attempting deletion
        $checkUser = $pdo->prepare(
            "SELECT id, role FROM users WHERE id = ?"
        );
        $checkUser->execute([$deleteId]);
        $targetUser = $checkUser->fetch();

        if (!$targetUser) {
            $message = "User not found.";
        } elseif ($targetUser["role"] === "admin") {
            $message = "Admin accounts cannot be deleted here.";
        } else {
            // Preserve order history
            $checkOrders = $pdo->prepare(
                "SELECT COUNT(*) FROM orders WHERE user_id = ?"
            );
            $checkOrders->execute([$deleteId]);

            if ((int)$checkOrders->fetchColumn() > 0) {
                $message = "This user has orders. The account cannot be deleted.";
            } else {
                $delete = $pdo->prepare(
                    "DELETE FROM users WHERE id = ?"
                );
                $delete->execute([$deleteId]);

                $message = $delete->rowCount() > 0
                    ? "User deleted successfully."
                    : "User not found.";
            }
        }
    }
}

// Get all registered users
$stmt = $pdo->query(
    "SELECT id, name, email, role, created_at
     FROM users
     ORDER BY id DESC"
);
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Manage Users | BookNest</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="bg-light">

<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h2>👥 Manage Users</h2>

        <a href="dashboard.php" class="btn btn-dark">
            Admin Dashboard
        </a>
    </div>

    <?php if ($message !== ""): ?>
        <div class="alert alert-info">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body p-4">

            <h4 class="mb-3">
                Registered Users (<?= count($users) ?>)
            </h4>

            <div class="table-responsive">

                <table class="table table-bordered table-hover align-middle">

                    <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Registered On</th>
                        <th>Actions</th>
                    </tr>
                    </thead>

                    <tbody>

                    <?php if (empty($users)): ?>

                        <tr>
                            <td colspan="6" class="text-center">
                                No registered users found.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($users as $user): ?>

                            <tr>
                                <td>
                                    <?= (int)$user["id"] ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($user["name"]) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($user["email"]) ?>
                                </td>

                                <td>
                                    <span class="badge text-bg-<?= $user["role"] === "admin" ? "primary" : "secondary" ?>">
                                        <?= htmlspecialchars(ucfirst($user["role"])) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= htmlspecialchars($user["created_at"] ?? "") ?>
                                </td>

                                <td>
                                    <?php if ((int)$user["id"] === (int)$_SESSION["user_id"]): ?>

                                        <span class="text-muted">
                                            Current account
                                        </span>

                                    <?php elseif ($user["role"] === "admin"): ?>

                                        <span class="text-muted">
                                            Admin account
                                        </span>

                                    <?php else: ?>

                                        <div class="d-flex gap-2 flex-wrap">

                                            <a
                                                href="edit_user.php?id=<?= (int)$user["id"] ?>"
                                                class="btn btn-warning btn-sm"
                                            >
                                                Edit
                                            </a>

                                            <form
                                                method="POST"
                                                onsubmit="return confirm('Are you sure you want to delete this user?');"
                                            >
                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="delete"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="user_id"
                                                    value="<?= (int)$user["id"] ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="btn btn-danger btn-sm"
                                                >
                                                    Delete
                                                </button>
                                            </form>

                                        </div>

                                    <?php endif; ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>
                </table>

            </div>
        </div>
    </div>

    <a href="../index.php" class="btn btn-outline-primary mt-3">
        Back to BookNest
    </a>

</div>

</body>
</html>
